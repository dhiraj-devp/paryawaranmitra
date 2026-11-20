<?php

namespace App\Actions\Clients;

use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditDiff;
use App\Services\Audit\AuditLogger;
use App\Services\Clients\ClientDuplicateDetector;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveClient
{
    public function __construct(
        private AuditLogger $audit,
        private ClientDuplicateDetector $duplicates,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $actor, ?Client $client = null, bool $duplicateAcknowledged = false): Client
    {
        $matches = $this->duplicates->sameLegalName((string) $attributes['legal_name'], $client?->id);

        if ($matches->isNotEmpty() && ! $duplicateAcknowledged) {
            throw ValidationException::withMessages([
                'duplicate_name' => __('ui.clients.duplicate_name', [
                    'names' => $matches->pluck('legal_name')->join(', '),
                ]),
            ]);
        }

        return DB::transaction(function () use ($attributes, $actor, $client, $duplicateAcknowledged, $matches) {
            $client ??= new Client;
            $before = $client->exists ? $client->auditSnapshot() : null;

            $client->fill($attributes);

            if (! $client->exists) {
                $client->status = RecordStatus::Active;
                $client->created_by = $actor->id;
            }

            if ($before !== null && ! $client->isDirty()) {
                return $client;
            }

            try {
                $client->save();
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'pan' => __('ui.clients.identifier_taken'),
                ]);
            }

            $after = $client->auditSnapshot();
            [$oldValues, $newValues] = $before === null ? [[], $after] : AuditDiff::changed($before, $after);

            if ($duplicateAcknowledged && $matches->isNotEmpty()) {
                $newValues['duplicate_acknowledged'] = true;
                $newValues['matched_client_ids'] = $matches->pluck('id')->all();
            }

            if ($oldValues !== [] || $newValues !== []) {
                $this->audit->log(
                    action: $before === null ? 'client.created' : 'client.updated',
                    subject: $client,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $client;
        });
    }
}
