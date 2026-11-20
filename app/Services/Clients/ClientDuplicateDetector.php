<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Support\NameKey;
use Illuminate\Support\Collection;

class ClientDuplicateDetector
{
    /**
     * @return Collection<int, Client>
     */
    public function sameLegalName(string $legalName, ?int $ignoreId = null): Collection
    {
        $key = NameKey::make($legalName);

        if ($key === '') {
            return collect();
        }

        return Client::query()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('name_key', $key)
            ->orderBy('legal_name')
            ->limit(5)
            ->get(['id', 'legal_name', 'gstin', 'district']);
    }
}
