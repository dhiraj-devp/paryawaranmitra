<?php

namespace App\Services\Search;

use App\Contracts\SearchSource;
use App\Enums\Portal;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Support\Collection;

class UserSearchSource implements SearchSource
{
    public function search(User $actor, string $term): Collection
    {
        if ($actor->portal !== Portal::Admin || ! $actor->hasPermission('users.view')) {
            return collect();
        }

        $like = LikeTerm::contains($term);

        return User::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (User $match) => new SearchResult(
                type: __('ui.search.types.user'),
                title: $match->name,
                subtitle: $match->email,
                url: route('admin.users.index', ['q' => $match->email]),
            ));
    }
}
