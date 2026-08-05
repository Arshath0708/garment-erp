<?php

namespace Database\Seeders;

use App\Models\DocumentFormat;
use App\Models\DocumentFormatColumn;
use App\Models\DocumentFormatUnit;
use Illuminate\Database\Seeder;

/**
 * One ready-to-use Order Format, so a fresh install is not empty everywhere
 * a format is picked from — the Category master's "formats" picker, and the
 * Product master's Unit (PO & OC) / Unit (Export Docs) dropdowns, which are
 * synced from the units defined here.
 *
 * Same shape DocumentFormatService::defaults() hands a brand-new format on
 * the create screen: every standard column switched on, and the six default
 * unit chips (PCS, SET, MTR, KGS, PAIR, DOZ).
 */
class DocumentFormatSeeder extends Seeder
{
    public function run(): void
    {
        $format = DocumentFormat::firstOrCreate(
            ['name' => 'Standard Format'],
            [
                'description' => 'Default item table — every standard column, the six common units.',
                'module'      => 'po',
                'status'      => 'active',
            ]
        );

        // Already seeded on an earlier run — do not overwrite columns or
        // units someone has since edited on this format.
        if ($format->columns()->exists()) {
            return;
        }

        $order = 0;

        foreach (DocumentFormatColumn::STANDARD as $key => $meta) {
            $format->columns()->create([
                'key'        => $key,
                'label'      => $meta['label'],
                'is_enabled' => true,
                'is_custom'  => false,
                'print_only' => $meta['print_only'],
                'sort_order' => $order++,
            ]);
        }

        foreach (DocumentFormatUnit::DEFAULTS as $index => $unit) {
            $format->units()->create([
                'name'       => $unit,
                'sort_order' => $index,
            ]);
        }

        $this->command?->info('Sample Order Format seeded.');
    }
}
