<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Buyer;
use App\Models\GarmentStyle;
use App\Models\Category;

echo "--- BUYERS IN DATABASE ---\n";
$allBuyers = Buyer::all();
echo "Total Buyers in DB: " . $allBuyers->count() . "\n";
foreach ($allBuyers as $b) {
    echo "ID: {$b->id} | Name: {$b->company_name} | Status: '{$b->status}'\n";
}

echo "\n--- ACTIVE BUYERS FOR STYLE DROPDOWN ---\n";
$dropdownBuyers = Buyer::query()->whereIn('status', ['active', 'Active'])->orderBy('company_name')->get();
echo "Dropdown Buyers Count: " . $dropdownBuyers->count() . "\n";
foreach ($dropdownBuyers as $b) {
    echo "ID: {$b->id} | Name: {$b->company_name}\n";
}

echo "\n--- CATEGORIES FOR STYLE DROPDOWN ---\n";
$dropdownCategories = Category::query()->whereIn('status', ['active', 'Active'])->orderBy('name')->get();
echo "Dropdown Categories Count: " . $dropdownCategories->count() . "\n";
foreach ($dropdownCategories as $c) {
    echo "ID: {$c->id} | Name: {$c->name}\n";
}
