<?php

namespace App\Http\Requests;

use App\Enums\Portal;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('user');

        if ($subject instanceof User) {
            return $this->user()?->can('update', $subject) ?? false;
        }

        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $password = $this->input('password');
        $blank = ! is_string($password) || $password === '';

        $this->merge([
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
            'is_active' => $this->boolean('is_active'),
            'password' => $blank ? null : $password,
            'password_confirmation' => $blank ? null : $this->input('password_confirmation'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $subject = $this->route('user');
        $userId = $subject instanceof User ? $subject->id : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'portal' => ['required', Rule::in([Portal::Admin->value, Portal::Staff->value])],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'password' => [$userId === null ? 'required' : 'nullable', 'string', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array{name: string, email: string, portal: string, role_id: int, password: ?string, is_active: bool}
     */
    public function userAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'portal' => $this->string('portal')->toString(),
            'role_id' => $this->integer('role_id'),
            'password' => $this->input('password'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
