<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@garment.com';
        $password = 'garment@123';

        $superAdmin = User::query()->where('email', $email)->first();

        if (! $superAdmin) {
            $superAdmin = User::create([
                'name'              => 'Super Admin',
                'email'             => $email,
                'password'          => $password, // User::$casts password => hashed
                'status'            => true,
                'email_verified_at' => now(),
            ]);
        } else {
            $superAdmin->forceFill([
                'name'              => 'Super Admin',
                'password'          => $password,
                'status'            => true,
                'email_verified_at' => $superAdmin->email_verified_at ?? now(),
            ])->save();
        }

        if (! $superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        $this->command?->info("Garment Super Admin ready: {$email}");
    }
}
