<?php

namespace Database\Seeders;

use App\Models\DefaultMarkup;
use Illuminate\Database\Seeder;

/**
 * Change request #7 — presets for the Markup form's "Default Markup"
 * dropdown. No screen manages these; add or change a preset here and
 * re-run this seeder.
 */
class DefaultMarkupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Economy 5%', 'markup_percent' => 5],
            ['name' => 'Standard 10%', 'markup_percent' => 10],
            ['name' => 'Premium 15%', 'markup_percent' => 15],
            ['name' => 'Premium Plus 20%', 'markup_percent' => 20],
        ] as $row) {
            DefaultMarkup::firstOrCreate(
                ['name' => $row['name']],
                ['markup_percent' => $row['markup_percent'], 'status' => 'active']
            );
        }
    }
}
