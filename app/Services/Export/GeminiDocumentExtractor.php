<?php

namespace App\Services\Export;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls Google Gemini with an uploaded scan and returns structured fields
 * for Export Document checklist rows (B/L, LEO, container/seal, …).
 *
 * Supported checklist type codes are listed in SUPPORTED_TYPES — anything
 * else throws so the UI can hide the Extract button cleanly.
 */
class GeminiDocumentExtractor
{
    /**
     * Checklist type codes that have an extraction schema.
     *
     * @var list<string>
     */
    public const SUPPORTED_TYPES = [
        'bl_draft',
        'bl_final',
        'leo_copy',
        'container_seal_no',
        'assessed_copy',
        'firc',
        'bank_certificate',
        'payment_received',
    ];

    public function isConfigured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public function supports(string $typeCode): bool
    {
        return in_array($typeCode, self::SUPPORTED_TYPES, true);
    }

    /**
     * @return array{reference_no: ?string, remarks: ?string, fields: array<string, mixed>}
     */
    public function extract(UploadedFile $file, string $typeCode): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Gemini is not configured. Set GEMINI_API_KEY in .env.');
        }

        if (! $this->supports($typeCode)) {
            throw new RuntimeException("OCR is not available for checklist type \"{$typeCode}\".");
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

        if (! in_array($mime, $allowed, true)) {
            throw new RuntimeException('Upload a PDF or image (JPEG, PNG, WebP, GIF).');
        }

        $schema = $this->schemaFor($typeCode);
        $prompt = $this->promptFor($typeCode, $schema);

        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $key = config('services.gemini.key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::timeout(60)
            ->withQueryParameters(['key' => $key])
            ->post($url, [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mime,
                                'data'      => base64_encode($file->get()),
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => [
                    'temperature'      => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Gemini OCR failed: '.$message);
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $fields = $this->parseJsonObject($text);

        return $this->mapToChecklistFields($typeCode, $fields);
    }

    /**
     * @return array<string, string>
     */
    private function schemaFor(string $typeCode): array
    {
        return match ($typeCode) {
            'bl_draft', 'bl_final' => [
                'bl_number' => 'Bill of Lading number',
                'bl_date'   => 'B/L date as YYYY-MM-DD',
            ],
            'leo_copy' => [
                'leo_number' => 'LEO / Let Export Order number',
                'leo_date'   => 'LEO date as YYYY-MM-DD',
            ],
            'container_seal_no' => [
                'container_number' => 'Container number',
                'seal_number'      => 'Seal number',
            ],
            'assessed_copy' => [
                'reference_no' => 'Assessment / reference number if printed',
                'date'         => 'Document date as YYYY-MM-DD',
            ],
            'firc', 'bank_certificate', 'payment_received' => [
                'reference_no' => 'Primary reference / certificate / SWIFT number',
                'date'         => 'Document date as YYYY-MM-DD',
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, string>  $schema
     */
    private function promptFor(string $typeCode, array $schema): string
    {
        $keys = collect($schema)
            ->map(fn (string $desc, string $key) => "- {$key}: {$desc}")
            ->implode("\n");

        return <<<PROMPT
You are reading an export-shipping document scan for a garment exporter (checklist type: {$typeCode}).

Extract these fields. Return ONLY a JSON object with exactly these keys.
Use null when a value is not clearly present on the document.
Do not invent values.

{$keys}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonObject(string $text): array
    {
        $text = trim($text);

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned a response that was not valid JSON.');
        }

        return $decoded;
    }

    /**
     * Map raw Gemini fields onto checklist form inputs (reference_no + remarks).
     *
     * @param  array<string, mixed>  $fields
     * @return array{reference_no: ?string, remarks: ?string, fields: array<string, mixed>}
     */
    private function mapToChecklistFields(string $typeCode, array $fields): array
    {
        $nullIfBlank = static function (mixed $value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            return is_scalar($value) ? trim((string) $value) : null;
        };

        $reference = null;
        $remarksParts = [];

        switch ($typeCode) {
            case 'bl_draft':
            case 'bl_final':
                $reference = $nullIfBlank($fields['bl_number'] ?? null);
                if ($date = $nullIfBlank($fields['bl_date'] ?? null)) {
                    $remarksParts[] = 'B/L date: '.$date;
                }
                break;

            case 'leo_copy':
                $reference = $nullIfBlank($fields['leo_number'] ?? null);
                if ($date = $nullIfBlank($fields['leo_date'] ?? null)) {
                    $remarksParts[] = 'LEO date: '.$date;
                }
                break;

            case 'container_seal_no':
                $container = $nullIfBlank($fields['container_number'] ?? null);
                $seal = $nullIfBlank($fields['seal_number'] ?? null);
                $reference = collect([$container, $seal])->filter()->implode(' / ') ?: null;
                break;

            default:
                $reference = $nullIfBlank($fields['reference_no'] ?? null);
                if ($date = $nullIfBlank($fields['date'] ?? null)) {
                    $remarksParts[] = 'Date: '.$date;
                }
                break;
        }

        return [
            'reference_no' => $reference,
            'remarks'      => $remarksParts === [] ? null : implode(' · ', $remarksParts),
            'fields'       => $fields,
        ];
    }
}
