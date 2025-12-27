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
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Allow users with the manage-users permission or the two roles
        if (method_exists($user, 'can') && $user->can('manage-users')) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('administrator') || $user->hasRole('secretary');
        }

        return in_array($user->role ?? '', ['administrator', 'secretary'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => ['required', 'string', Rule::in(['administrator', 'secretary'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            'role.in' => 'Role must be either Administrator or Secretary.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('send_invite')) {
            $this->merge([
                'send_invite' => filter_var($this->input('send_invite'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}