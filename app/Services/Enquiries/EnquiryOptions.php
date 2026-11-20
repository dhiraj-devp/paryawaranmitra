<?php

namespace App\Services\Enquiries;

use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\IndustryUnit;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EnquiryOptions
{
    /**
     * @return Collection<int, Client>
     */
    public function clients(?int $includeId = null): Collection
    {
        return Client::query()
            ->where(function ($query) use ($includeId) {
                $query->where('status', RecordStatus::Active);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('legal_name')
            ->get();
    }

    /**
     * @return Collection<int, IndustryUnit>
     */
    public function units(?int $includeId = null): Collection
    {
        return IndustryUnit::query()
            ->with('client')
            ->where(function ($query) use ($includeId) {
                $query->where('status', RecordStatus::Active)
                    ->whereHas('client', fn ($query) => $query->where('status', RecordStatus::Active));

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Service>
     */
    public function services(?int $includeId = null): Collection
    {
        return Service::query()
            ->where(function ($query) use ($includeId) {
                $query->where('status', RecordStatus::Active);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function staff(?int $includeId = null): Collection
    {
        return User::query()
            ->where(function ($query) use ($includeId) {
                $query->where('is_active', true);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
