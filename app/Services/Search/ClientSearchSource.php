<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\Client;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class ClientSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('clients.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return Client::query()
            ->where(function ($query) use ($like) {
                $query->where('legal_name', 'like', $like)
                    ->orWhere('trade_name', 'like', $like)
                    ->orWhere('pan', 'like', $like)
                    ->orWhere('gstin', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->orderBy('legal_name')
            ->limit(8)
            ->get()
            ->map(fn (Client $client) => new SearchResult(
                type: __('ui.search.types.client'),
                title: $client->legal_name,
                subtitle: $client->gstin ?: $client->district,
                url: route($actor->portal->value.'.clients.show', $client),
            ));
    }
}
