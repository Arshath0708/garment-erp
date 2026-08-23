<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductionOrder;

$order = ProductionOrder::first();

if (!$order) {
    echo "No production order found to test.\n";
    exit;
}

$order->update([
    'cutting_qty' => 5000,
    'stitching_qty' => 2500,
]);

echo "Order Number: " . $order->order_number . "\n";
echo "Cutting Qty: " . number_format($order->cutting_qty) . " pcs\n";
echo "Stitching Qty: " . number_format($order->stitching_qty) . " pcs\n";
echo "Remaining Cutting Balance (WIP): " . number_format($order->pendingCuttingQty()) . " pcs\n";

if ($order->pendingCuttingQty() === 2500) {
    echo "STAGE DEDUCTION TEST PASSED PERFECTLY!\n";
} else {
    echo "TEST FAILED!\n";
}
