<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\SupplierType;
use Illuminate\Database\Seeder;

/**
 * Starting values for the two Supplier master dropdowns the sheet asks to be
 * extensible — col D (supplier type) and col I (designation).
 *
 * Idempotent: every row goes through firstOrCreate on its natural key, so
 * re-running this on a deploy never duplicates and never overwrites a value the
 * client has since edited. Same contract as LookupSeeder.
 *
 * A starting point, not the client's final data. Both tables get a Lookups
 * screen under Settings in a later phase; until then adding a designation needs
 * a developer, which is worth saying out loud.
 */
class SupplierLookupSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Col D. The sheet names one of these outright — "supplier type
         * (composition)" — which is the GST composition scheme, so the three
         * that matter are the three GST registration statuses.
         *
         * is_registered is what col E hangs off: "GST Number (only if the
         * registered option is chosen)". A composition dealer is registered and
         * does have a GSTIN; what differs is the rate they charge, not whether
         * they have a number.
         */
        $types = [
            // code, name, is_registered
            [SupplierType::UNREGISTERED,            'Unregistered',              false],
            [SupplierType::REGISTERED_REGULAR,      'Registered — Regular',      true],
            [SupplierType::REGISTERED_COMPOSITION,  'Registered — Composition',  true],
        ];

        foreach ($types as [$code, $name, $isRegistered]) {
            SupplierType::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_registered' => $isRegistered],
            );
        }

        // Col I. Who actually signs for a Tirupur jobber or a trading supplier.
        $designations = [
            'Proprietor',
            'Partner',
            'Director',
            'General Manager',
            'Production Manager',
            'Merchandiser',
            'Marketing Executive',
            'Accountant',
            'Store Keeper',
        ];

        foreach ($designations as $name) {
            Designation::firstOrCreate(['name' => $name]);
        }

        $this->command?->info('Supplier lookups seeded.');
    }
}
