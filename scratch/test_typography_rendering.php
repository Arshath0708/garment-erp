<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::where('email', 'admin@garment.com')->first() ?? User::first();
Auth::login($user);

$routesToTest = [
    '/dashboard',
    '/masters/categories',
    '/masters/buyers',
    '/masters/styles',
    '/sales/inquiries',
    '/sales/order-confirmations',
    '/manufacturing',
    '/procurement/purchase-orders',
    '/export/documents',
    '/export/ocr',
];

echo "--- RUNNING TYPOGRAPHY & ROUTE RENDER VERIFICATION ---\n";
foreach ($routesToTest as $route) {
    $request = Request::create($route, 'GET');
    $response = $app->handle($request);
    $status = $response->getStatusCode();
    echo str_pad($route, 35) . " -> Status: " . $status . " " . ($status === 200 ? "✓ OK" : "FAILED") . "\n";
}
