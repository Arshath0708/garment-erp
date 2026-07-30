<?php

namespace Database\Seeders;

use App\Models\CalculationBasis;
use App\Models\GstRate;
use App\Models\PriceBand;
use Illuminate\Database\Seeder;

/**
 * Starting values for the Category and Product dropdowns.
 *
 * Idempotent — every row goes through firstOrCreate on its natural key, so
 * re-running this on a deploy never duplicates and never overwrites a value the
 * client has since edited.
 *
 * No units here: the client cancelled the Unit Master, so the unit is typed on
 * the Product master instead. See the products migration.
 */
class LookupSeeder extends Seeder
{
    public function run(): void
    {
        // Product sheet col J: "AA and AB with an option to add in the future".
        foreach ([['AA', 'Band AA'], ['AB', 'Band AB']] as [$code, $name]) {
            PriceBand::firstOrCreate(['code' => $code], ['name' => $name]);
        }

        // Product sheet col K. Standard Indian GST slabs.
        foreach ([0, 5, 12, 18, 28] as $rate) {
            GstRate::firstOrCreate(['rate' => $rate]);
        }

        // Product sheet cols N, R, U: what an incentive percentage applies to.
        foreach (['FOB Value', 'Quantity', 'Net Weight', 'Square Metre'] as $basis) {
            CalculationBasis::firstOrCreate(['name' => $basis]);
        }

        $this->command?->info('Lookups seeded.');
    }
}
