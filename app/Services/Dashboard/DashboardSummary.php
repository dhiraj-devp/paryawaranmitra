<?php

namespace App\Services\Dashboard;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardSummary
{
    /**
     * @return array{activity: Collection<int, AuditLog>, counts: ?array{total: int, active: int}}
     */
    public function forUser(User $user): array
    {
        return [
            'activity' => AuditLog::query()
                ->with('actor')
                ->where('actor_id', $user->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'counts' => $user->hasPermission('users.view')
                ? [
                    'total' => User::query()->count(),
                    'active' => User::query()->where('is_active', true)->count(),
                ]
                : null,
        ];
    }
}
