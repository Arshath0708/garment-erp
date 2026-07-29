@php
    $isEdit = isset($user);
    $selectedRoles = old('roles', $userRoles ?? []);
@endphp

<div class="row">
    <x-ui.field name="name" label="Full Name" :value="$user->name ?? null" required col="col-md-6" />
    <x-ui.field name="email" label="Email Address" type="email" :value="$user->email ?? null" required col="col-md-6" />
    <x-ui.field name="phone" label="Phone" :value="$user->phone ?? null" col="col-md-6"
                placeholder="+91 98765 43210" />

    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Status</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="status" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" value="1"
                   @checked(old('status', $user->status ?? true))
                   @disabled($isEdit && ($user->isProtected() || $user->is(auth()->user())))>
            <label class="form-check-label" for="status">Active — the user can sign in</label>
        </div>
        @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @if($isEdit && $user->isProtected())
            <div class="form-text"><i class="bi bi-shield-lock me-1"></i>Protected system account.</div>
        @endif
    </div>

    <x-ui.field name="password" label="Password" type="password" :required="! $isEdit" col="col-md-6"
                autocomplete="new-password"
                :hint="$isEdit ? 'Leave blank to keep the current password.' : 'Minimum 8 characters, with letters and numbers.'" />

    <x-ui.field name="password_confirmation" label="Confirm Password" type="password" :required="! $isEdit"
                col="col-md-6" autocomplete="new-password" />
</div>

<hr class="my-4">

<div class="mb-2 d-flex justify-content-between align-items-center">
    <label class="form-label fw-semibold mb-0">
        Roles <span class="text-danger">*</span>
    </label>
    <span class="small text-body-secondary">A user's permissions are the sum of all their roles.</span>
</div>

@error('roles')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

<div class="row g-2">
    @foreach($roles as $role)
        <div class="col-md-4">
            <label class="border rounded p-2 d-flex align-items-start gap-2 h-100 role-option">
                <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="roles[]"
                       value="{{ $role->name }}" @checked(in_array($role->name, $selectedRoles, true))
                       @disabled($isEdit && $user->isProtected() && $role->name === 'Super Admin')>
                <span>
                    <span class="fw-semibold d-block">{{ $role->name }}</span>
                    <span class="small text-body-secondary">
                        {{ app(\App\Support\PermissionRegistry::class)->roleDescription($role->name) ?? $role->permissions_count.' permissions' }}
                    </span>
                </span>
            </label>
        </div>
    @endforeach
</div>

<div class="d-flex gap-2 mt-4 pt-3 border-top">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Update User' : 'Create User' }}
    </button>
    <a href="{{ route('user-management.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
