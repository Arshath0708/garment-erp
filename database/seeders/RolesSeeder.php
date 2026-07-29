<?php

namespace Database\Seeders;

use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the system roles from config/permissions.php and syncs their
 * permissions.
 *
 * Must run AFTER PermissionsSeeder — a role can only be given a permission
 * that already exists.
 *
 * syncPermissions() is intentional: config is the source of truth, so a
 * permission removed from a system role in config is removed from the role
 * on the next seed. Roles created through the UI are untouched.
 */
class RolesSeeder extends Seeder
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    public function run(): void
    {
        foreach ($this->registry->systemRoles() as $roleName) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            // Super Admin gets everything through a Gate::before check in
            // AppServiceProvider, so it does not need rows in the pivot table.
            // Syncing them anyway keeps the permission matrix honest when an
            // admin opens the Super Admin role to see what it can do.
            $role->syncPermissions($this->registry->permissionsForRole($roleName));

            $this->command?->info("Role synced: {$roleName} ({$role->permissions->count()} permissions)");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
