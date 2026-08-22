<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OrderConfirmation;
use App\Models\OrderConfirmationItem;
use App\Models\GarmentStyle;
use App\Models\Product;
use App\Models\ExportDocument;
use App\Services\Export\ExportDocumentService;

$oc = OrderConfirmation::first();
$style = GarmentStyle::first();
$product = Product::first();

if ($oc && $oc->items()->count() === 0) {
    echo "Adding item to OC ID: {$oc->id}\n";
    $item = $oc->items()->create([
        'sort_order'  => 0,
        'design_no'    => $style?->style_number ?? 'ST-1005',
        'description'  => $style?->name ?? "Men's Casual Shirt",
        'product_id'   => $product?->id,
        'unit'         => 'pcs',
        'price'        => 12.50,
        'qty'          => 5000,
        'amount'       => 62500.00,
    ]);
    
    $colour = $item->colours()->create(['colour' => 'Navy Blue', 'sort_order' => 0]);
    $colour->sizes()->create(['size' => 'M', 'qty' => 2500]);
    $colour->sizes()->create(['size' => 'L', 'qty' => 2500]);
    echo "Item added successfully!\n";
}

if (ExportDocument::count() === 0) {
    $service = app(ExportDocumentService::class);
    $itemIds = $oc->items->pluck('id')->toArray();
    $doc = $service->raiseFromOrderConfirmation($oc, $itemIds);
    echo "SUCCESS: Created Export Document {$doc->doc_num}!\n";
} else {
    echo "Export Document already exists: " . ExportDocument::first()->doc_num . "\n";
}
