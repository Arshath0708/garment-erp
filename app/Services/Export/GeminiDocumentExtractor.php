<?php

namespace App\Services\Export;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Gemini OCR for Export Document uploads.
 *
 * Phase 1 focuses on sheet row #4 — CHA Checklist (`cha_checklist`) — and only
 * the "Uploaded" document types from the client sheet. Generated formats are
 * out of scope here.
 */
class GeminiDocumentExtractor
{
    /**
     * Uploaded checklist types we will enable over time.
     * Phase 1 UI only exposes PHASE1_TYPES.
     *
     * @var list<string>
     */
    public const UPLOADED_TYPES = [
        'cha_checklist',      // #4 Checklist (CHA)
        'e_sanchit_docs',     // #5
        'assessed_copy',      // #9
        'leo_copy',           // #10
        'measurement_copy',
        'clp',                // #13
        'bl_final',           // #15
        'insurance',          // #16
        'payment_received',   // #20
        'eefc_upload',        // #21
        'firc',               // #22
        'bank_certificate',   // #23
        'ebrc',
    ];

    /**
     * Currently live in the Document OCR screen.
     *
     * @var list<string>
     */
    public const PHASE1_TYPES = [
        'cha_checklist',
    ];

    /**
     * @deprecated Use PHASE1_TYPES / UPLOADED_TYPES — kept for older checklist OCR route.
     *
     * @var list<string>
     */
    public const SUPPORTED_TYPES = self::UPLOADED_TYPES;

    public function isConfigured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public function supports(string $typeCode): bool
    {
        return in_array($typeCode, self::UPLOADED_TYPES, true);
    }

    public function isPhase1(string $typeCode): bool
    {
        return in_array($typeCode, self::PHASE1_TYPES, true);
    }

    /**
     * Human labels for the OCR document-type picker.
     *
     * @return array<string, string>
     */
    public static function phase1Labels(): array
    {
        return [
            'cha_checklist' => '#4 Checklist (from CHA)',
        ];
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

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $key = config('services.gemini.key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $http = Http::timeout(60)->withQueryParameters(['key' => $key]);

            if (config('services.gemini.verify_ssl') === false) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($url, [
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
        } catch (Throwable $e) {
            throw new RuntimeException('Gemini OCR failed: '.$this->safeErrorMessage($e->getMessage()), 0, $e);
        }

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? 'HTTP '.$response->status();

            throw new RuntimeException('Gemini OCR failed: '.$this->safeErrorMessage((string) $message));
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
            // Sheet #4 — CHA checklist after docs are filed on the customs site.
            'cha_checklist' => [
                'checklist_no'       => 'CHA checklist / job / reference number',
                'checklist_date'     => 'Checklist or filing date as YYYY-MM-DD',
                'shipping_bill_no'   => 'Shipping bill number if printed',
                'invoice_no'         => 'Export invoice number if printed',
                'cha_name'           => 'Clearing house agent / CHA name if printed',
                'status_or_remarks'  => 'Any status line or short note on the checklist',
            ],
            'leo_copy' => [
                'leo_number' => 'LEO / Let Export Order number',
                'leo_date'   => 'LEO date as YYYY-MM-DD',
            ],
            'bl_final' => [
                'bl_number' => 'Bill of Lading number',
                'bl_date'   => 'B/L date as YYYY-MM-DD',
            ],
            'assessed_copy', 'measurement_copy', 'clp', 'e_sanchit_docs',
            'insurance', 'payment_received', 'eefc_upload', 'firc', 'bank_certificate', 'ebrc' => [
                'reference_no' => 'Primary reference / document number if printed',
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

        $context = match ($typeCode) {
            'cha_checklist' => 'This is an Indian export CHA checklist / customs filing checklist received from the Clearing House Agent after documents were uploaded to the customs website.',
            default => 'This is an export-shipping document scan for a garment exporter.',
        };

        return <<<PROMPT
{$context}
Checklist type code: {$typeCode}.

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
            case 'cha_checklist':
                $reference = $nullIfBlank($fields['checklist_no'] ?? null)
                    ?? $nullIfBlank($fields['shipping_bill_no'] ?? null)
                    ?? $nullIfBlank($fields['invoice_no'] ?? null);

                if ($date = $nullIfBlank($fields['checklist_date'] ?? null)) {
                    $remarksParts[] = 'Date: '.$date;
                }
                if ($sb = $nullIfBlank($fields['shipping_bill_no'] ?? null)) {
                    $remarksParts[] = 'SB: '.$sb;
                }
                if ($inv = $nullIfBlank($fields['invoice_no'] ?? null)) {
                    $remarksParts[] = 'Invoice: '.$inv;
                }
                if ($cha = $nullIfBlank($fields['cha_name'] ?? null)) {
                    $remarksParts[] = 'CHA: '.$cha;
                }
                if ($note = $nullIfBlank($fields['status_or_remarks'] ?? null)) {
                    $remarksParts[] = $note;
                }
                break;

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

    private function safeErrorMessage(string $message): string
    {
        $message = preg_replace('/([?&]key=)[^&\s]+/i', '$1***', $message) ?? $message;
        $message = preg_replace('/AIza[0-9A-Za-z_-]+/', '***', $message) ?? $message;
        $message = preg_replace('/AQ\.[0-9A-Za-z_-]+/', '***', $message) ?? $message;

        return $message;
    }
}
