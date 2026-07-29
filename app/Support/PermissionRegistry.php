<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Reads config/permissions.php and turns it into the shapes the seeder,
 * the artisan command and the role permission matrix all need.
 *
 * Everything here is derived from config — nothing queries the database,
 * so it is safe to call before migrations have run.
 */
class PermissionRegistry
{
    /**
     * Every permission name declared in config, flat.
     *
     * @return array<int, string>  ['user.view', 'user.create', ...]
     */
    public function all(): array
    {
        $names = [];

        foreach ($this->groups() as $modules) {
            foreach ($modules as $module => $meta) {
                foreach ($meta['actions'] as $action) {
                    $names[] = "{$module}.{$action}";
                }
            }
        }

        return $names;
    }

    /**
     * Groups with every module's metadata resolved (actions filled in,
     * labels defaulted, built flag defaulted to true).
     *
     * @return array<string, array<string, array{label: string, actions: array<int, string>, built: bool}>>
     */
    public function groups(): array
    {
        $defaults = config('permissions.default_actions', ['view', 'create', 'edit', 'delete']);
        $resolved = [];

        foreach (config('permissions.groups', []) as $group => $modules) {
            foreach ($modules as $module => $meta) {
                $resolved[$group][$module] = [
                    'label'   => $meta['label'] ?? str($module)->headline()->toString(),
                    'actions' => $meta['actions'] ?? $defaults,
                    'built'   => $meta['built'] ?? true,
                ];
            }
        }

        return $resolved;
    }

    /**
     * Every distinct action used anywhere, in the order declared in
     * action_labels. Drives the column order of the permission matrix.
     *
     * @return array<int, string>
     */
    public function actions(): array
    {
        $used = collect($this->groups())
            ->flatMap(fn (array $modules) => collect($modules)->pluck('actions'))
            ->flatten()
            ->unique();

        return collect(array_keys(config('permissions.action_labels', [])))
            ->filter(fn (string $action) => $used->contains($action))
            ->values()
            ->all();
    }

    public function actionLabel(string $action): string
    {
        return config("permissions.action_labels.{$action}", str($action)->headline()->toString());
    }

    /**
     * Names of the roles declared in config. These are protected from
     * rename and delete in the UI.
     *
     * @return array<int, string>
     */
    public function systemRoles(): array
    {
        return array_keys(config('permissions.roles', []));
    }

    public function isSystemRole(string $name): bool
    {
        return in_array($name, $this->systemRoles(), true);
    }

    public function roleDescription(string $name): ?string
    {
        return config("permissions.roles.{$name}.description");
    }

    /**
     * Resolve a role's configured permissions into concrete names.
     *
     * Accepts '*' for everything, 'module.*' for a whole module, or an
     * exact permission name. Unknown entries are dropped rather than
     * created, so a typo in config can never invent a permission.
     *
     * @return array<int, string>
     */
    public function permissionsForRole(string $role): array
    {
        $configured = config("permissions.roles.{$role}.permissions", []);

        if ($configured === '*') {
            return $this->all();
        }

        $available = collect($this->all());

        return collect($configured)
            ->flatMap(function (string $pattern) use ($available) {
                if (! str_contains($pattern, '*')) {
                    return $available->contains($pattern) ? [$pattern] : [];
                }

                $prefix = str($pattern)->before('.*')->toString();

                return $available->filter(
                    fn (string $name) => str_starts_with($name, "{$prefix}.")
                )->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Group a collection of existing Permission models by config group,
     * for read-only display. Permissions present in the database but no
     * longer in config land under "Unregistered" so they are visible
     * rather than silently ignored.
     *
     * @param  Collection<int, \Spatie\Permission\Models\Permission>  $permissions
     */
    public function groupExisting(Collection $permissions): array
    {
        $moduleToGroup = [];

        foreach ($this->groups() as $group => $modules) {
            foreach (array_keys($modules) as $module) {
                $moduleToGroup[$module] = $group;
            }
        }

        return $permissions
            ->groupBy(function ($permission) use ($moduleToGroup) {
                $module = str($permission->name)->beforeLast('.')->toString();

                return $moduleToGroup[$module] ?? 'Unregistered';
            })
            ->sortKeys()
            ->all();
    }

    /**
     * Permissions that exist in config but not in the database, and rows in
     * the database that config no longer declares. Shown on the Permissions
     * screen so drift is visible instead of silent.
     *
     * @param  array<int, string>  $existing  permission names from the database
     * @return array{missing: array<int, string>, orphaned: array<int, string>}
     */
    public function diff(array $existing): array
    {
        $declared = $this->all();

        return [
            'missing'  => array_values(array_diff($declared, $existing)),
            'orphaned' => array_values(array_diff($existing, $declared)),
        ];
    }
}
