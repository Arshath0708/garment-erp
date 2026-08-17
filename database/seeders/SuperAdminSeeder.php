<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('permissions.super_admin.email', 'admin@gurutraders.com');
        $password = config('permissions.super_admin.password', 'Guru@123');

        // Do not re-hash the password on every seed run — that invalidates
        // active sessions / remember tokens and forces repeated logins locally.
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
                'status'            => true,
                'email_verified_at' => $superAdmin->email_verified_at ?? now(),
            ])->save();
        }

        if (! $superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        $this->command?->info("Super Admin ready: {$email}");
    }
}
