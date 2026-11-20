<?php

namespace App\Services\Clients;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\IndustryUnit;
use Illuminate\Support\Collection;

class ClientHistory
{
    /**
     * @return Collection<int, AuditLog>
     */
    public function forClient(Client $client): Collection
    {
        $unitIds = $client->industryUnits()->pluck('id');

        return AuditLog::query()
            ->with('actor')
            ->where(function ($query) use ($client, $unitIds) {
                $query->where(function ($query) use ($client) {
                    $query->where('subject_type', $client->getMorphClass())
                        ->where('subject_id', $client->id);
                })->orWhere(function ($query) use ($unitIds) {
                    $query->where('subject_type', (new IndustryUnit)->getMorphClass())
                        ->whereIn('subject_id', $unitIds->all());
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }
}
