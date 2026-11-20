<?php

namespace App\Actions\Auth;

use App\Enums\Portal;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttemptPortalLogin
{
    private static ?string $dummyHash = null;

    public function __construct(private AuditLogger $audit) {}

    public function execute(Portal $portal, string $email, string $password, bool $remember, Request $request): User
    {
        $email = Str::lower(trim($email));
        $emailKey = $this->emailKey($portal, $email, $request);
        $ipKey = $this->ipKey($portal, $request);

        if (RateLimiter::tooManyAttempts($emailKey, $this->maxAttempts()) || RateLimiter::tooManyAttempts($ipKey, $this->ipMaxAttempts())) {
            $seconds = max(1, RateLimiter::availableIn($emailKey), RateLimiter::availableIn($ipKey));

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::query()->where('email', $email)->first();
        $passwordMatches = Hash::check($password, $user?->password ?? $this->dummyHash());

        if (! $user || ! $passwordMatches) {
            $this->recordFailure($portal, $email, $request, $user, 'invalid_credentials');

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->portal !== $portal) {
            $this->recordFailure($portal, $email, $request, $user, 'wrong_portal');

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            $this->recordFailure($portal, $email, $request, $user, 'inactive');

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($portal === Portal::Admin && filter_var(config('security.admin_two_factor'), FILTER_VALIDATE_BOOL)) {
            $this->recordFailure($portal, $email, $request, $user, 'two_factor_unavailable');

            throw ValidationException::withMessages([
                'email' => __('auth.two_factor_unavailable'),
            ]);
        }

        DB::transaction(function () use ($user, $portal, $request) {
            $user->forceFill(['last_login_at' => now()])->save();

            $this->audit->log(
                action: 'auth.login',
                subject: $user,
                newValues: ['last_login_at' => $user->last_login_at?->toIso8601String()],
                actor: $user,
                portal: $portal,
                request: $request,
            );
        });

        Auth::guard($portal->value)->login($user, $remember);

        if (! session()->isStarted()) {
            session()->start();
        }

        session()->regenerate();
        session()->put('locale', $user->locale);
        RateLimiter::clear($emailKey);

        return $user;
    }

    private function recordFailure(Portal $portal, string $email, Request $request, ?User $user, string $reason): void
    {
        RateLimiter::hit($this->emailKey($portal, $email, $request), $this->decay());
        RateLimiter::hit($this->ipKey($portal, $request), $this->decay());

        $this->audit->log(
            action: 'auth.login_failed',
            subject: $user,
            newValues: [
                'email' => $email,
                'reason' => $reason,
            ],
            portal: $portal,
            request: $request,
        );
    }

    private function emailKey(Portal $portal, string $email, Request $request): string
    {
        return implode('|', ['login', $portal->value, $email, $request->ip()]);
    }

    private function ipKey(Portal $portal, Request $request): string
    {
        return implode('|', ['login-ip', $portal->value, $request->ip()]);
    }

    private function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(40));
    }

    private function maxAttempts(): int
    {
        return (int) config('security.login_max_attempts');
    }

    private function ipMaxAttempts(): int
    {
        return (int) config('security.login_ip_max_attempts');
    }

    private function decay(): int
    {
        return (int) config('security.login_decay_seconds');
    }
}
