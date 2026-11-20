<?php

namespace App\Services\Audit;

use App\Enums\Portal;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * @var list<string>
     */
    private const REDACTED = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
    ];

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
        ?Portal $portal = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();
        $agent = Str::limit(trim((string) $request->userAgent()), 1000, '');
        $old = $this->redact($oldValues);
        $new = $this->redact($newValues);

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'portal' => $portal?->value ?? $actor?->portal?->value,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'ip_address' => $request->ip(),
            'user_agent' => $agent !== '' ? $agent : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    public function redact(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED, true)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $clean;
    }
}
