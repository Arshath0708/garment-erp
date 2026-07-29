<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

/**
 * Permissions are read-only in the UI.
 *
 * They are declared in config/permissions.php and pushed into the database by
 * the seeder or `php artisan permission:sync`. There is deliberately no
 * create/edit/delete here: a permission typed by hand does not match any
 * @can() check in the code, so it grants nothing and fails silently. The only
 * write action is Sync, which re-reads config.
 */
class PermissionController extends Controller implements HasMiddleware
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:permission.view', only: ['index']),
            new Middleware('permission:permission.sync', only: ['sync']),
        ];
    }

    public function index(): View
    {
        $permissions = Permission::withCount('roles')->orderBy('name')->get();

        return view('user-management.permissions.index', [
            'grouped'  => $this->registry->groupExisting($permissions),
            'total'    => $permissions->count(),
            'diff'     => $this->registry->diff($permissions->pluck('name')->all()),
            'registry' => $this->registry,
        ]);
    }

    public function sync(): RedirectResponse
    {
        Artisan::call('permission:sync');

        return back()->with('success', trim(Artisan::output()) ?: 'Permissions synced from config.');
    }
}
