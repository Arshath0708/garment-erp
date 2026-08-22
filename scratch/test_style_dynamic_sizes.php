<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GarmentStyle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::where('email', 'admin@garment.com')->first() ?? User::first();
Auth::login($user);

$styleNum = 'ST-TEST-' . rand(1000, 9999);

$requestData = [
    'style_number' => $styleNum,
    'name' => 'Dynamic Size Polo Shirt',
    'status' => 'active',
    'target_qty' => 0,
    'size_names' => ['M', 'L', 'XL'],
    'size_qtys' => [100, 200, 300],
];

$request = Request::create('/masters/styles', 'POST', $requestData);
$controller = app(\App\Http\Controllers\Masters\GarmentStyleController::class);

try {
    $response = $controller->store($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    $created = GarmentStyle::where('style_number', $styleNum)->first();
    if ($created) {
        echo "CREATED STYLE SUCCESSFULLY!\n";
        echo "Style Number: " . $created->style_number . "\n";
        echo "Sizes Column: " . $created->sizes . "\n";
        echo "Target Quantity: " . $created->target_qty . " pcs\n";
    } else {
        echo "Style creation failed.\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
