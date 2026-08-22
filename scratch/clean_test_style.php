<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Models\GarmentStyle::where('style_number', 'like', 'ST-TEST-%')->delete();
echo "Cleaned test style records.\n";
