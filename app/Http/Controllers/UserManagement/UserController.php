<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(private readonly UserService $users)
    {
    }

    /**
     * Laravel 12's base controller has no middleware() method, so per-action
     * middleware is declared here instead of in the constructor.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:user.view', only: ['index', 'show']),
            new Middleware('permission:user.create', only: ['create', 'store']),
            new Middleware('permission:user.edit', only: ['edit', 'update', 'toggleStatus']),
            new Middleware('permission:user.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles:id,name')
            ->search($request->string('search')->toString())
            ->withRole($request->string('role')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('user-management.users.index', [
            'users'   => $users,
            'roles'   => Role::orderBy('name')->pluck('name'),
            'filters' => $request->only('search', 'role', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('user-management.users.create', [
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated(), $request->user());

        return redirect()
            ->route('user-management.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        return view('user-management.users.show', [
            'user' => $user->load('roles.permissions', 'creator'),
        ]);
    }

    public function edit(User $user): View
    {
        return view('user-management.users.edit', [
            'user'      => $user,
            'roles'     => Role::withCount('permissions')->orderBy('name')->get(),
            'userRoles' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        return redirect()
            ->route('user-management.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $check = $this->users->canDelete($user, $request->user());

        if (! $check['allowed']) {
            return back()->with('error', $check['reason']);
        }

        $user->delete();

        return redirect()
            ->route('user-management.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->isProtected()) {
            return back()->with('error', 'The Super Admin account cannot be deactivated.');
        }

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['status' => ! $user->status]);

        return back()->with('success', 'User '.($user->status ? 'activated' : 'deactivated').'.');
    }
}
