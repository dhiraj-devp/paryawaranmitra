<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\AttemptPortalLogin;
use App\Enums\Portal;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LoginForm extends Component
{
    public string $portal = 'staff';

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(string $portal): void
    {
        abort_unless(in_array($portal, [Portal::Admin->value, Portal::Staff->value], true), 404);

        $this->portal = $portal;
    }

    public function authenticate(AttemptPortalLogin $login)
    {
        $this->validate([
            'portal' => ['required', Rule::in([Portal::Admin->value, Portal::Staff->value])],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $login->execute(
                Portal::from($this->portal),
                $this->email,
                $this->password,
                $this->remember,
                request(),
            );
        } finally {
            $this->reset('password');
        }

        return redirect()->intended(route($this->portal.'.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
