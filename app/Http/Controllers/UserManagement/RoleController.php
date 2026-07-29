<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreRoleRequest;
use App\Http\Requests\UserManagement\UpdateRoleRequest;
use App\Services\RoleService;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly PermissionRegistry $registry,
    ) {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:role.view', only: ['index', 'show']),
            new Middleware('permission:role.create', only: ['create', 'store']),
            new Middleware('permission:role.edit', only: ['edit', 'update']),
            new Middleware('permission:role.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('user-management.roles.index', [
            'roles'    => Role::withCount(['permissions', 'users'])->orderBy('id')->get(),
            'registry' => $this->registry,
        ]);
    }

    public function create(): View
    {
        return view('user-management.roles.create', [
            'groups'          => $this->registry->groups(),
            'actions'         => $this->registry->actions(),
            'registry'        => $this->registry,
            'rolePermissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->validated());

        return redirect()
            ->route('user-management.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        return view('user-management.roles.show', [
            'role'            => $role->load('permissions', 'users'),
            'groups'          => $this->registry->groups(),
            'actions'         => $this->registry->actions(),
            'registry'        => $this->registry,
            'rolePermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('user-management.roles.edit', [
            'role'            => $role,
            'groups'          => $this->registry->groups(),
            'actions'         => $this->registry->actions(),
            'registry'        => $this->registry,
            'rolePermissions' => $role->permissions->pluck('name')->all(),
            'isSystemRole'    => $this->registry->isSystemRole($role->name),
            'isLocked'        => $role->name === 'Super Admin',
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roles->update($role, $request->validated());

        return redirect()
            ->route('user-management.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $check = $this->roles->canDelete($role);

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $this->roles->delete($role);

        return redirect()
            ->route('user-management.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
