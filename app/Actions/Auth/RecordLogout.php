<?php

namespace App\Actions\Auth;

use App\Enums\Portal;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordLogout
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Portal $portal, Request $request): void
    {
        $guard = Auth::guard($portal->value);
        $user = $guard->user();

        if ($user) {
            $this->audit->log(
                action: 'auth.logout',
                subject: $user,
                actor: $user,
                portal: $portal,
                request: $request,
            );
        }

        // Drop only this portal. The other portal keeps its own guard session.
        $guard->logout();

        if (! session()->isStarted()) {
            session()->start();
        }

        session()->regenerate();
        session()->regenerateToken();
    }
}
