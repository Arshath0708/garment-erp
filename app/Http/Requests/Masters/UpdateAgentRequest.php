<?php

namespace App\Http\Requests\Masters;

use App\Models\Agent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('agent.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $agentId = $this->route('agent')->id;

        return [
            'agent_type'           => ['required', Rule::in(array_keys(Agent::TYPES))],
            'name'                 => ['required', 'string', 'max:200'],
            'display_code'         => ['required', 'string', 'max:5', 'regex:/^[a-zA-Z0-9]+$/', Rule::unique('agents', 'display_code')->ignore($agentId)],
            'calculation_basis_id' => ['nullable', 'integer', Rule::exists('calculation_bases', 'id')],
            'commission_rate'      => ['nullable', 'numeric', 'min:0', 'max:999999.99'], // Reserved for future use
            'status'               => ['required', Rule::in(['active', 'inactive'])],
            'remarks'              => ['nullable', 'string', 'max:1000'],
            'categories'           => ['nullable', 'array'],
            'categories.*'         => ['integer', Rule::exists('categories', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'display_code.unique' => 'This display code is already taken.',
            'display_code.regex'  => 'The display code must contain only letters and numbers.',
        ];
    }
}
