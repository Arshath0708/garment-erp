<?php

namespace App\Console\Commands;

use App\Support\PermissionRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

use function Laravel\Prompts\confirm;

class SyncPermissions extends Command
{
    protected $signature = 'permission:sync
                            {--prune : Delete permissions that are no longer declared in config}
                            {--roles : Also re-sync system role permissions from config}';

    protected $description = 'Sync permissions from config/permissions.php into the database';

    public function handle(PermissionRegistry $registry): int
    {
        $existing = Permission::pluck('name')->all();
        $diff     = $registry->diff($existing);

        foreach ($diff['missing'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        $created = count($diff['missing']);
        $this->info("Created {$created} permission(s).");

        if ($diff['orphaned'] !== []) {
            $this->newLine();
            $this->warn(count($diff['orphaned']).' permission(s) in the database are not declared in config:');
            $this->line('  '.implode(', ', $diff['orphaned']));

            if ($this->option('prune')) {
                // Deleting a permission also strips it from every role that had
                // it, so this is never automatic.
                if (! $this->input->isInteractive() || confirm('Delete these permissions and remove them from all roles?', default: false)) {
                    Permission::whereIn('name', $diff['orphaned'])->delete();
                    $this->info('Pruned '.count($diff['orphaned']).' permission(s).');
                }
            } else {
                $this->line('Run with --prune to remove them.');
            }
        }

        if ($this->option('roles')) {
            $this->newLine();
            $this->call('db:seed', ['--class' => 'RolesSeeder', '--force' => true]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return self::SUCCESS;
    }
}
