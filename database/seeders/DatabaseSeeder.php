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
        ]);
    }
}
