<?php

namespace Database\Seeders;

use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pushes every permission declared in config/permissions.php into the
 * database. Idempotent — safe to run on every deploy.
 *
 * Permissions removed from config are NOT deleted here. Deleting a
 * permission also strips it from every role, which is not something a
 * seeder should do silently. Run `php artisan permission:sync --prune`
 * to remove them deliberately.
 */
class PermissionsSeeder extends Seeder
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    public function run(): void
    {
        $declared = $this->registry->all();
        $existing = Permission::pluck('name')->all();
        $missing  = array_diff($declared, $existing);

        if ($missing === []) {
            $this->command?->info('Permissions already up to date ('.count($declared).' total).');

            return;
        }

        $now = now();

        DB::table(config('permission.table_names.permissions'))->insert(
            collect($missing)->map(fn (string $name) => [
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Created '.count($missing).' permission(s).');
    }
}
