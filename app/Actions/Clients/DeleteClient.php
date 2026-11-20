<?php

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteClient
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Client $client, User $actor): void
    {
        if ($client->hasDependents()) {
            throw new RuntimeException('Clients are not deleted. Deactivate the client instead.');
        }

        DB::transaction(function () use ($client, $actor) {
            $this->audit->log(
                action: 'client.deleted',
                subject: $client,
                oldValues: $client->auditSnapshot(),
                actor: $actor,
                portal: $actor->portal,
            );

            $client->delete();
        });
    }
}
