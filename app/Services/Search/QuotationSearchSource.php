<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\Quotation;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class QuotationSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('quotations.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return Quotation::query()
            ->with(['workOrder.client', 'workOrder.service'])
            ->where(function ($query) use ($like) {
                $query->where('quotation_number', 'like', $like)
                    ->orWhereHas('workOrder', function ($query) use ($like) {
                        $query->where('number', 'like', $like)
                            ->orWhereHas('client', fn ($query) => $query->where('legal_name', 'like', $like))
                            ->orWhereHas('service', fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
                    });
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (Quotation $quotation) => new SearchResult(
                type: __('ui.search.types.quotation'),
                title: $quotation->quotation_number,
                subtitle: $quotation->workOrder?->client?->legal_name,
                url: route($actor->portal->value.'.quotations.show', $quotation),
            ));
    }
}
