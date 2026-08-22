<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');


$modelsToTest = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];

foreach ($modelsToTest as $model) {
    echo "Testing model '{$model}'...\n";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
    $response = Http::withoutVerifying()->post($url, [
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Hello, respond with OK if working.']
                ]
            ]
        ]
    ]);
    
    echo "Status: " . $response->status() . "\n";
    if ($response->successful()) {
        echo "Response: " . substr($response->body(), 0, 200) . "\n\n";
    } else {
        echo "Error: " . substr($response->body(), 0, 300) . "\n\n";
    }
}
