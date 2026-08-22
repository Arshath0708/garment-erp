<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Buyer;
use App\Models\GarmentStyle;

echo "--- TESTING COMPLETE BUYER -> STYLE CREATION WORKFLOW ---\n";

// 1. Fetch newly created buyer (Kanmani Readymades)
$buyer = Buyer::where('company_name', 'like', '%Kanmani%')->first();
if (!$buyer) {
    die("ERROR: Buyer Kanmani Readymades not found\n");
}

echo "1. Selected Buyer: ID {$buyer->id} - {$buyer->company_name} (Status: {$buyer->status})\n";

// 2. Create a Style linked to this Buyer
$styleNum = 'TEST-STYLE-' . rand(1000, 9999);
$style = GarmentStyle::create([
    'style_number' => $styleNum,
    'name'         => 'Test Kanmani Cotton Polo Shirt',
    'buyer_id'     => $buyer->id,
    'target_qty'   => 5000,
    'status'       => 'Active',
]);

echo "2. Style Created: ID {$style->id} - {$style->style_number} linked to Buyer ID {$style->buyer_id}\n";

// 3. Verify Relationship
$fetchedStyle = GarmentStyle::with('buyer')->find($style->id);
echo "3. Verified Relationship: Style '{$fetchedStyle->name}' is assigned to Buyer '{$fetchedStyle->buyer->company_name}'\n";

// 4. Update Style
$fetchedStyle->update(['name' => 'Updated Kanmani Premium Cotton Polo Shirt']);
$updatedStyle = GarmentStyle::with('buyer')->find($style->id);
echo "4. Verified Update: Style '{$updatedStyle->name}' retains Buyer '{$updatedStyle->buyer->company_name}'\n";

// 5. Cleanup test style
$updatedStyle->delete();
echo "5. Cleanup completed successfully!\n";
