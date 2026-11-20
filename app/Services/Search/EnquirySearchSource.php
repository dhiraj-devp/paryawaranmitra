<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class EnquirySearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if (! $actor->hasPermission('enquiries.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return Enquiry::query()
            ->where(function ($query) use ($like) {
                $query->where('number', 'like', $like)
                    ->orWhere('contact_name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderByDesc('enquiry_date')
            ->limit(8)
            ->get()
            ->map(fn (Enquiry $enquiry) => new SearchResult(
                type: __('ui.search.types.enquiry'),
                title: $enquiry->number.' — '.$enquiry->contact_name,
                subtitle: $enquiry->status->label(),
                url: route($actor->portal->value.'.enquiries.show', $enquiry),
            ));
    }
}
