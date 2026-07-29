<?php

namespace App\Services;

use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'       => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            $this->flushCache();

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            if (! $this->registry->isSystemRole($role->name)) {
                $role->update(['name' => $data['name']]);
            }

            // Super Admin permissions come from Gate::before, not the pivot
            // table, so editing them here would be misleading.
            if ($role->name !== 'Super Admin') {
                $role->syncPermissions($data['permissions'] ?? []);
            }

            $this->flushCache();

            return $role->refresh();
        });
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(Role $role): array
    {
        if ($this->registry->isSystemRole($role->name)) {
            return ['allowed' => false, 'reason' => "\"{$role->name}\" is a system role and cannot be deleted."];
        }

        $userCount = $role->users()->count();

        if ($userCount > 0) {
            return [
                'allowed' => false,
                'reason'  => "This role is assigned to {$userCount} user(s). Reassign them before deleting it.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function delete(Role $role): void
    {
        $role->delete();
        $this->flushCache();
    }

    /**
     * Spatie caches the permission map. Without this, a role change does not
     * take effect until the cache expires — which reads as "permissions are
     * not saving".
     */
    private function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
