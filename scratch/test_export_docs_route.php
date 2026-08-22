<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Authenticate as Super Admin
$user = User::where('email', 'admin@garment.com')->first() ?? User::first();
Auth::login($user);

$request = Request::create('/export/documents', 'GET');
try {
    $response = $app->handle($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 400) {
        echo "Response Content:\n";
        echo substr($response->getContent(), 0, 1500) . "\n";
    } else {
        echo "Successfully rendered /export/documents page!\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION THROWN: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
