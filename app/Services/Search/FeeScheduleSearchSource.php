<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\FeeSchedule;
use App\Models\User;
use App\Support\LikeTerm;
use App\Support\Money;
use Illuminate\Support\Collection;

class FeeScheduleSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('fees.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return FeeSchedule::query()
            ->with('service')
            ->whereHas('service', function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
            })
            ->orderByDesc('effective_from')
            ->limit(8)
            ->get()
            ->map(fn (FeeSchedule $schedule) => new SearchResult(
                type: __('ui.search.types.fee_schedule'),
                title: $schedule->service->name,
                subtitle: $schedule->effective_from->format('d-m-Y').' · '.Money::inr($schedule->consultancy_fee),
                url: route($actor->portal->value.'.fees.show', $schedule),
            ));
    }
}
