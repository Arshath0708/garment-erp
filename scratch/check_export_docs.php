<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExportDocument;
use App\Models\OrderConfirmation;

echo "Export Documents Count: " . ExportDocument::count() . "\n";
foreach (ExportDocument::with(['buyer', 'orderConfirmation', 'checklist'])->get() as $doc) {
    echo "ID: {$doc->id} | Num: {$doc->doc_num} | OC: {$doc->orderConfirmation?->oc_num} | Buyer: {$doc->buyer?->company_name} | Checklist Count: {$doc->checklist->count()}\n";
}

echo "\nOrder Confirmations Count: " . OrderConfirmation::count() . "\n";
foreach (OrderConfirmation::all() as $oc) {
    echo "OC ID: {$oc->id} | OC Num: {$oc->oc_num} | Status: {$oc->status}\n";
}
