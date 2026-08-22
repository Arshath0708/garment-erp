<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Export\GeminiDocumentExtractor;
use Illuminate\Http\UploadedFile;

$extractor = app(GeminiDocumentExtractor::class);

echo "Is Configured: " . ($extractor->isConfigured() ? 'YES' : 'NO') . "\n";

// Test OCR on a sample generated image/file
$tmpFile = tempnam(sys_get_temp_dir(), 'ocr_test_') . '.png';
$im = imagecreatetruecolor(400, 100);
$bg = imagecolorallocate($im, 255, 255, 255);
$textColor = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, 400, 100, $bg);
imagestring($im, 5, 20, 40, "INVOICE NO: EXP-2026-999", $textColor);
imagepng($im, $tmpFile);
imagedestroy($im);

$uploadedFile = new UploadedFile($tmpFile, 'test_invoice.png', 'image/png', null, true);

try {
    echo "Running OCR extraction with Gemini 2.5 Flash...\n";
    $result = $extractor->extract($uploadedFile, 'insurance');

    echo "SUCCESS!\n";
    echo "Reference No: " . ($result['reference_no'] ?? 'N/A') . "\n";
    echo "Remarks: " . ($result['remarks'] ?? 'N/A') . "\n";
    echo "Fields JSON: " . json_encode($result['fields'], JSON_PRETTY_PRINT) . "\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    @unlink($tmpFile);
}
