<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,name'],
            'status'   => ['nullable', 'boolean'],
        ];
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
        // An unchecked switch is absent from the payload, not "false".
        $this->merge(['status' => $this->boolean('status')]);
    }
}
