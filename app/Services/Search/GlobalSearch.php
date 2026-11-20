<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Models\User;
use Illuminate\Support\Collection;

class GlobalSearch
{
    /**
     * @param  iterable<SearchSource>  $sources
     */
    public function __construct(private iterable $sources) {}

    /**
     * @return Collection<int, SearchResult>
     */
    public function search(User $actor, string $term): Collection
    {
        return collect($this->sources)
            ->flatMap(fn (SearchSource $source) => $source->search($actor, $term))
            ->values();
    }
}
