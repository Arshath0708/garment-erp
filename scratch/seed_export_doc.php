<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OrderConfirmation;
use App\Services\Export\ExportDocumentService;

$oc = OrderConfirmation::with('items')->first();
if (!$oc) {
    die("No OC found\n");
}

echo "Found OC ID: {$oc->id} with " . $oc->items->count() . " items.\n";
$itemIds = $oc->items->pluck('id')->toArray();

$service = app(ExportDocumentService::class);
try {
    $doc = $service->raiseFromOrderConfirmation($oc, $itemIds);
    echo "SUCCESS: Export Document raised! Doc Num: {$doc->doc_num}\n";
} catch (\Throwable $e) {
    echo "INFO: " . $e->getMessage() . "\n";
}
