<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Order matters: permissions must exist before roles can be given them,
        // and the Super Admin role must exist before a user can be assigned it.
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            SuperAdminSeeder::class,

            // Master data prerequisites. Number series must exist before the
            // first category is saved, or the code generator has no counter.
            NumberSeriesSeeder::class,
            LookupSeeder::class,

            // A sample Order Format, so the Category master's format picker
            // and the Product master's unit dropdowns are not empty.
            DocumentFormatSeeder::class,

            // Supplier sheet cols D and I — the two lookups that sheet asks to
            // be extensible. Separate from LookupSeeder so the Supplier master
            // can be reseeded without touching the buyer and product lists.
            SupplierLookupSeeder::class,

            // Needs countries from LookupSeeder. Feeds the cascading
            // Country -> State -> City dropdowns on the Buyer master.
            GeoSeeder::class,

            // Gives the Buyer master's agent dropdown something to show until
            // the Agent screen is built.
            AgentSeeder::class,

            // Enough Products/Suppliers/FOB Values to actually build an
            // Inquiry end to end, plus Size sub-column tags on the sample
            // format. Runs last — depends on categories and the format above.
            SalesDemoSeeder::class,

            // Two more Order Formats besides "Standard Format", so the
            // Inquiry / OC format picker has real choices to demo.
            OrderFormatDemoSeeder::class,

            // Change request #7 — presets for the Markup form's "Default
            // Markup" dropdown. No screen manages these.
            DefaultMarkupSeeder::class,
        ]);
    }
}
