<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    protected function prepareForValidation(): void
    {
        $password = $this->input('password');
        $blank = ! is_string($password) || $password === '';

        $this->merge([
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
            'password' => $blank ? null : $password,
            'password_confirmation' => $blank ? null : $this->input('password_confirmation'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => [Rule::requiredIf(fn () => $this->confirmsIdentity($user)), 'nullable', 'string'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var User $user */
            $user = $this->user();

            if (! $this->confirmsIdentity($user)) {
                return;
            }

            if (! Hash::check((string) $this->input('current_password'), $user->password)) {
                $validator->errors()->add('current_password', __('ui.profile.current_wrong'));
            }
        });
    }

    /**
     * @return array{name: string, email: string, password: ?string}
     */
    public function profileAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'password' => $this->input('password'),
        ];
    }

    private function confirmsIdentity(User $user): bool
    {
        return filled($this->input('password')) || $this->string('email')->toString() !== $user->email;
    }
}
