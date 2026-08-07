<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DocumentFormat;
use App\Models\FobValue;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Enough Products, Suppliers and FOB Values to actually click through an
 * Inquiry end to end — a fresh install (or this one, before this seeder)
 * has categories and a format but nothing a category-filtered item-row
 * dropdown can show, so every Inquiry item stalls on an empty Product /
 * Supplier / FOB Value select.
 *
 * Idempotent throughout (firstOrCreate / syncWithoutDetaching), so re-running
 * after someone has since edited this data does not clobber their changes.
 */
class SalesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->get()->keyBy('name');

        if ($categories->isEmpty()) {
            $this->command?->warn('No categories found — run LookupSeeder first. Skipping SalesDemoSeeder.');

            return;
        }

        $this->seedProducts($categories);
        $this->seedSuppliers($categories);
        $this->seedFobValues();
        $this->seedSizeGrid();

        $this->command?->info('Sales demo data seeded (products, suppliers, FOB values, Size grid tags).');
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Category>  $categories
     */
    private function seedProducts($categories): void
    {
        $rows = [
            ['code' => 'TSH-101', 'name' => 'Men Round Neck T-Shirt', 'category' => 'Mens tshirt'],
            ['code' => 'TSH-102', 'name' => 'Men Polo T-Shirt',       'category' => 'Mens tshirt'],
            ['code' => 'SHT-101', 'name' => 'Men Formal Shirt',       'category' => 'Mens Shirt'],
            ['code' => 'SHT-102', 'name' => 'Men Casual Check Shirt', 'category' => 'Mens Shirt'],
        ];

        foreach ($rows as $row) {
            $category = $categories->get($row['category']);

            if (! $category) {
                continue;
            }

            Product::firstOrCreate(
                ['item_group_code' => $row['code']],
                [
                    'category_id' => $category->id,
                    'name'        => $row['name'],
                    'unit_po'     => 'PCS',
                    'unit_export' => 'PCS',
                    'status'      => 'active',
                ]
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Category>  $categories
     */
    private function seedSuppliers($categories): void
    {
        // Link whatever supplier(s) already exist to every demo category —
        // an existing supplier with no category link never shows up in a
        // category-filtered dropdown, which otherwise looks identical to a
        // broken dropdown from the user's side.
        Supplier::all()->each(fn (Supplier $supplier) => $supplier->categories()->syncWithoutDetaching($categories->pluck('id')));

        $demo = Supplier::firstOrCreate(
            ['company_name' => 'Sunrise Garments Pvt Ltd'],
            [
                'display_code' => 'SUP01',
                'party_type'   => 'supplier',
                'status'       => 'active',
            ]
        );
        $demo->categories()->syncWithoutDetaching($categories->pluck('id'));
    }

    private function seedFobValues(): void
    {
        foreach (['FOB Mumbai (USD)', 'FOB Chennai (USD)', 'FOB Delhi (INR)'] as $name) {
            FobValue::firstOrCreate(['name' => $name], ['status' => 'active']);
        }
    }

    /**
     * Gives "Standard Format" Size sub-column tags so the new qty-per-size
     * grid (Order Format → Item Table Columns → Sub-columns) has something to
     * show out of the box instead of an empty chip list.
     */
    private function seedSizeGrid(): void
    {
        $format = DocumentFormat::where('name', 'Standard Format')->first();

        if (! $format) {
            return;
        }

        $sizeColumn = $format->columns()->where('key', 'size')->first();

        if ($sizeColumn && blank($sizeColumn->sub_columns)) {
            $sizeColumn->update(['sub_columns' => ['S', 'M', 'L', 'XL', 'XXL']]);
        }
    }
}
