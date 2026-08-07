<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DocumentFormat;
use App\Models\DocumentFormatColumn;
use App\Services\Masters\DocumentFormatService;
use Illuminate\Database\Seeder;

/**
 * Two more Order Formats beyond DocumentFormatSeeder's "Standard Format", so
 * the Inquiry / OC Order Format dropdown has real choices to demo instead of
 * always resolving to the one seeded format. Built through
 * DocumentFormatService::create() — the same call the real form makes — so
 * these are exactly what saving the form by hand would produce, not a
 * shortcut that happens to look right.
 *
 * Both link to every existing category, same as Standard Format, so whichever
 * category is picked on an Inquiry, all three formats are on offer.
 */
class OrderFormatDemoSeeder extends Seeder
{
    public function __construct(private readonly DocumentFormatService $formats)
    {
    }

    public function run(): void
    {
        $categoryIds = Category::query()->pluck('id');

        if ($categoryIds->isEmpty()) {
            $this->command?->warn('No categories found — skipping OrderFormatDemoSeeder.');

            return;
        }

        $this->createFormat(
            name: 'Simple Format (No Size Grid)',
            description: 'Fewer columns, free-form size entry — for quick inquiries that don\'t need a full size breakdown.',
            allowMultipleColours: false,
            units: ['PCS'],
            enabled: ['design_no', 'product', 'unit', 'price', 'colour', 'size'],
            sizeTags: [],
            categoryIds: $categoryIds,
        );

        $this->createFormat(
            name: 'Export Format — Extended Sizes',
            description: 'Full column set with a Buyer PO Ref custom column and a wider S–3XL size grid, for export orders with multiple colourways.',
            allowMultipleColours: true,
            units: ['PCS', 'DOZ'],
            enabled: ['supplier', 'design_no', 'product', 'colour', 'size', 'unit', 'price', 'image'],
            sizeTags: ['S', 'M', 'L', 'XL', 'XXL', '3XL'],
            categoryIds: $categoryIds,
            customColumnLabel: 'Buyer PO Ref',
        );
    }

    /**
     * @param  array<int, string>  $enabled
     * @param  array<int, string>  $units
     * @param  array<int, string>  $sizeTags
     * @param  \Illuminate\Support\Collection<int, int>  $categoryIds
     */
    private function createFormat(
        string $name,
        string $description,
        bool $allowMultipleColours,
        array $units,
        array $enabled,
        array $sizeTags,
        $categoryIds,
        ?string $customColumnLabel = null,
    ): void {
        if (DocumentFormat::where('name', $name)->exists()) {
            return;
        }

        $columns = [];
        $order = [];

        foreach (DocumentFormatColumn::STANDARD as $key => $meta) {
            $columns[$key] = [
                'label'   => $meta['label'],
                'enabled' => in_array($key, $enabled, true) ? '1' : null,
            ];

            if ($key === 'size' && filled($sizeTags)) {
                $columns[$key]['sub_columns'] = implode(',', $sizeTags);
            }

            $order[] = $key;
        }

        if ($customColumnLabel) {
            $columns['custom_1'] = ['label' => $customColumnLabel, 'enabled' => '1', 'is_custom' => '1'];
            $order[] = 'custom_1';
        }

        $format = $this->formats->create([
            'name'                   => $name,
            'description'            => $description,
            'status'                 => 'active',
            'allow_multiple_colours' => $allowMultipleColours,
            'units'                  => $units,
            'columns'                => $columns,
            'column_order'           => $order,
            'delivery_details'       => null,
            'packing_details'        => null,
        ]);

        $format->categories()->syncWithoutDetaching($categoryIds);

        $this->command?->info("Order Format \"{$name}\" seeded.");
    }
}
