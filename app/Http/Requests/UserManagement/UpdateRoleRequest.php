<?php

namespace App\Http\Requests\UserManagement;

use App\Support\PermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('role.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->route('role')->id)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $role     = $this->route('role');
            $registry = app(PermissionRegistry::class);

            if (! $registry->isSystemRole($role->name)) {
                return;
            }

            // System role names are referenced by route middleware and by
            // Gate::before. Renaming one silently breaks those checks.
            if ($this->input('name') !== $role->name) {
                $validator->errors()->add('name', "\"{$role->name}\" is a system role and cannot be renamed.");
            }

            if ($role->name === 'Super Admin') {
                $validator->errors()->add('permissions', 'Super Admin permissions are fixed and cannot be edited.');
            }
        });
    }
}
