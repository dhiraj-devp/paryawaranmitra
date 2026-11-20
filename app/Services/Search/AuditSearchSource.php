<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Enums\Portal;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class AuditSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if ($actor->portal !== Portal::Admin || ! $actor->hasPermission('audit.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return AuditLog::query()
            ->with('actor')
            ->where(function ($query) use ($like) {
                $query->where('action', 'like', $like)
                    ->orWhereHas('actor', fn ($query) => $query->where('name', 'like', $like));
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => new SearchResult(
                type: __('ui.search.types.audit'),
                title: $log->label(),
                subtitle: $log->actor?->name,
                url: route('admin.settings.audit-logs', ['q' => $log->action]),
            ));
    }
}
