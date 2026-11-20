<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\IndustryUnit;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class IndustryUnitSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('clients.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return IndustryUnit::query()
            ->with('client')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('site_district', 'like', $like)
                    ->orWhere('plot_number', 'like', $like);
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (IndustryUnit $unit) => new SearchResult(
                type: __('ui.search.types.industry_unit'),
                title: $unit->name,
                subtitle: $unit->client->legal_name,
                url: route($actor->portal->value.'.clients.units.show', [$unit->client, $unit]),
            ));
    }
}
