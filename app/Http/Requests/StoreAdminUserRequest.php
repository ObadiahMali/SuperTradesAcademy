<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow admins or users with permission
        return $this->user() && $this->user()->can('manage-users');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'send_invite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A user with that email already exists.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Normalize boolean-like inputs.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('send_invite')) {
            $this->merge([
                'send_invite' => filter_var($this->input('send_invite'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}