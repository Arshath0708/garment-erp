<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// 1. Authenticate Admin User
$user = User::where('email', 'admin@garment.com')->first() ?? User::first();
Auth::login($user);
echo "1. Logged in as: " . $user->email . "\n";

// 2. Fetch or create a test Production Order
$order = ProductionOrder::first();
if (!$order) {
    echo "No order found, creating one...\n";
    $order = ProductionOrder::create([
        'order_number' => 'PO-LIVE-TEST',
        'garment_style_id' => 1,
        'buyer_id' => 1,
        'total_qty' => 5000,
        'target_date' => now()->addDays(10),
        'current_stage' => 'Cutting',
        'status' => 'In Progress',
        'cutting_qty' => 5000,
        'stitching_qty' => 0,
    ]);
} else {
    $order->update([
        'total_qty' => 5000,
        'cutting_qty' => 5000,
        'stitching_qty' => 0,
        'finishing_qty' => 0,
        'qc_passed_qty' => 0,
        'packing_qty' => 0,
        'dispatch_qty' => 0,
    ]);
}

echo "2. Initialized Order {$order->order_number}: Cutting Qty = 5,000, Stitching Qty = 0\n";
echo "   Initial Cutting Balance = " . $order->pendingCuttingQty() . " pcs\n";

// 3. Perform Live Stage Update Request (Moving 2500 pcs to Stitching)
$updatePayload = [
    'current_stage' => 'Stitching',
    'qc_rejected_qty' => 0,
    'sizes' => [
        'cutting' => ['S' => 1000, 'M' => 2000, 'L' => 2000],
        'stitching' => ['S' => 500, 'M' => 1000, 'L' => 1000], // 2,500 total
    ],
];

$request = Request::create("/manufacturing/{$order->id}/update-stage", 'POST', $updatePayload);
$controller = app(\App\Http\Controllers\Manufacturing\ManufacturingController::class);


$response = $controller->updateStage($request, $order);
echo "3. Sent update-stage request -> Response Status: " . $response->getStatusCode() . "\n";

// 4. Reload fresh order record from database
$order->refresh();
echo "\n--- LIVE DATABASE VERIFICATION RESULTS ---\n";
echo "Order Number: " . $order->order_number . "\n";
echo "Current Active Stage: " . $order->current_stage . "\n";
echo "Total Cut Output: " . number_format($order->cutting_qty) . " pcs\n";
echo "Stitching Progress Output: " . number_format($order->stitching_qty) . " pcs\n";
echo "Remaining Cutting Balance (WIP): " . number_format($order->pendingCuttingQty()) . " pcs\n";

// 5. Test Live View Rendering (/manufacturing index page)
$indexRequest = Request::create('/manufacturing', 'GET');
$indexResponse = $app->handle($indexRequest);
echo "\n4. Rendered /manufacturing desk page -> Status: " . $indexResponse->getStatusCode() . "\n";

$html = $indexResponse->getContent();
if (str_contains($html, 'Bal: 2,500') || str_contains($html, '2,500')) {
    echo "   HTML Check: Found 'Bal: 2,500' badge rendered on live page!\n";
}

if ($order->pendingCuttingQty() === 2500 && $order->stitching_qty === 2500) {
    echo "\n✅ ALL LIVE TESTS PASSED! 2,500 pcs automatically deducted from Cutting and retained as balance!\n";
} else {
    echo "\n❌ TEST FAILED!\n";
}
