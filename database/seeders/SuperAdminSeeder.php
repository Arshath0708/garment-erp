<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('permissions.super_admin.email', 'admin@gurutraders.com');

        $superAdmin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make(config('permissions.super_admin.password', 'Guru@123')),
                'status'            => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        $this->command?->info("Super Admin ready: {$email}");
    }
}
