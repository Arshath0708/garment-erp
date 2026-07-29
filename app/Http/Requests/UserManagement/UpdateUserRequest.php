<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'    => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,name'],
            'status'   => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $target = $this->route('user');

            // Guard the seeded Super Admin. Without this, removing its role or
            // deactivating it locks everyone out of permission management with
            // no way back in through the UI.
            if ($target->isProtected()) {
                if (! in_array('Super Admin', $this->input('roles', []), true)) {
                    $validator->errors()->add('roles', 'The Super Admin role cannot be removed from this account.');
                }

                if (! $this->boolean('status')) {
                    $validator->errors()->add('status', 'This account cannot be deactivated.');
                }
            }

            // Stop an admin from removing their own last route back in.
            if ($target->is($this->user()) && ! $this->boolean('status')) {
                $validator->errors()->add('status', 'You cannot deactivate your own account.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Assign at least one role to the user.',
            'phone.regex'    => 'Phone number may contain digits, spaces and + - ( ) only.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['status' => $this->boolean('status')]);
    }
}
