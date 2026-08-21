<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use Illuminate\Database\Seeder;

class GarmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a buyer and category exist
        $buyer = Buyer::firstOrCreate(
            ['company_name' => 'Nordic Retail Group'],
            [
                'display_code' => 'BUY-001',
                'status' => 'Active',
            ]
        );

        $category = Category::firstOrCreate(
            ['name' => 'Woven Shirts'],
            [
                'code' => 'CAT-WVN-01',
                'status' => 'Active',
            ]
        );

        // 1. Create Sample Garment Styles
        $style1 = GarmentStyle::create([
            'style_number' => 'ST-9042',
            'name' => 'Men Woven Casual Shirt',
            'buyer_id' => $buyer->id,
            'category_id' => $category->id,
            'season' => 'Autumn / Winter 2026',
            'color' => 'Navy Blue / Crisp White',
            'design' => 'Slim Fit Button Down',
            'fabric' => '100% Cotton Twill 180GSM',
            'sizes' => 'S, M, L, XL, XXL',
            'target_qty' => 12500,
            'tech_specs' => 'Double needle flat-felled side seams (1/4 gauge). Enzyme bio-wash treatment post stitching.',
            'status' => 'Active',
        ]);

        $style2 = GarmentStyle::create([
            'style_number' => 'ST-8821',
            'name' => 'Unisex Denim Jacket',
            'buyer_id' => $buyer->id,
            'category_id' => $category->id,
            'season' => 'Winter 2026',
            'color' => 'Raw Indigo Denim',
            'design' => 'Heavy Trucker Jacket with Metal Buttons',
            'fabric' => 'Ring Spun Cotton Denim 12oz',
            'sizes' => 'M, L, XL',
            'target_qty' => 8000,
            'tech_specs' => 'Contrast gold stitching thread. 6-pocket construction with antique brass rivets.',
            'status' => 'Active',
        ]);

        // 2. Create Sample Production Orders connected to Styles
        ProductionOrder::create([
            'order_number' => 'PO-2026-8841',
            'garment_style_id' => $style1->id,
            'buyer_id' => $buyer->id,
            'total_qty' => 12500,
            'target_date' => now()->addDays(20),
            'current_stage' => 'Stitching',
            'status' => 'In Progress',
            'cutting_qty' => 12500,
            'stitching_qty' => 6200,
            'finishing_qty' => 4100,
            'qc_passed_qty' => 3850,
            'qc_rejected_qty' => 50,
            'packing_qty' => 3200,
            'dispatch_qty' => 1200,
            'notes' => 'Line 3 running ahead by +45 pcs/day. Bottleneck cleared at cuff attachment.',
        ]);

        ProductionOrder::create([
            'order_number' => 'PO-2026-8842',
            'garment_style_id' => $style2->id,
            'buyer_id' => $buyer->id,
            'total_qty' => 8000,
            'target_date' => now()->addDays(35),
            'current_stage' => 'Cutting',
            'status' => 'In Progress',
            'cutting_qty' => 5400,
            'stitching_qty' => 1200,
            'finishing_qty' => 0,
            'qc_passed_qty' => 0,
            'qc_rejected_qty' => 0,
            'packing_qty' => 0,
            'dispatch_qty' => 0,
            'notes' => 'Automated CAD spreader operating at 98.2% nesting efficiency.',
        ]);

        $this->command->info('Garment Demo Styles & Production Orders seeded successfully.');
    }
}
