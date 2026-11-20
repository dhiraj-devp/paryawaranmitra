<?php

namespace App\Actions\Clients;

use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetClientStatus
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Client $client, RecordStatus $status, User $actor): Client
    {
        if ($client->status === $status) {
            return $client;
        }

        return DB::transaction(function () use ($client, $status, $actor) {
            $previous = $client->status?->value;
            $client->status = $status;
            $client->save();

            $this->audit->log(
                action: 'client.status_changed',
                subject: $client,
                oldValues: ['status' => $previous],
                newValues: ['status' => $status->value],
                actor: $actor,
                portal: $actor->portal,
            );

            return $client;
        });
    }
}
