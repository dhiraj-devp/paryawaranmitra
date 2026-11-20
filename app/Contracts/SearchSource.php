<?php

namespace App\Contracts;

use App\Models\User;
use App\Services\Search\SearchResult;
use Illuminate\Support\Collection;

interface SearchSource
{
    /**
     * @return Collection<int, SearchResult>
     */
    public function search(User $actor, string $term): Collection;
}
