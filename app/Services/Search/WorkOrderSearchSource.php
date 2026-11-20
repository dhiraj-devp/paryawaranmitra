<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class WorkOrderSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('work-orders.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return WorkOrder::query()
            ->with('client')
            ->where(function ($query) use ($like) {
                $query->where('number', 'like', $like)
                    ->orWhereHas('client', fn ($query) => $query->where('legal_name', 'like', $like))
                    ->orWhereHas('service', fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->orderByDesc('start_date')
            ->limit(8)
            ->get()
            ->map(fn (WorkOrder $workOrder) => new SearchResult(
                type: __('ui.search.types.work_order'),
                title: $workOrder->number,
                subtitle: $workOrder->client?->legal_name,
                url: route($actor->portal->value.'.work-orders.show', $workOrder),
            ));
    }
}
