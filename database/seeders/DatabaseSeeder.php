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

            // Needs countries from LookupSeeder. Feeds the cascading
            // Country -> State -> City dropdowns on the Buyer master.
            GeoSeeder::class,

            // Gives the Buyer master's agent dropdown something to show until
            // the Agent screen is built.
            AgentSeeder::class,
        ]);
    }
}
