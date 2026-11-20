<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\Service;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class ServiceSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('services.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return Service::query()
            ->where(function ($query) use ($like) {
                $query->where('code', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('category', 'like', $like);
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Service $service) => new SearchResult(
                type: __('ui.search.types.service'),
                title: $service->code.' — '.$service->name,
                subtitle: $service->category,
                url: route($actor->portal->value.'.services.show', $service),
            ));
    }
}
