<?php

namespace App\Actions\Clients;

use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\IndustryUnit;
use App\Models\User;
use App\Services\Audit\AuditDiff;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveIndustryUnit
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Client $client, array $attributes, User $actor, ?IndustryUnit $unit = null): IndustryUnit
    {
        if ($unit === null && $client->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'client' => __('ui.units.client_inactive'),
            ]);
        }

        if ($unit !== null && $unit->client_id !== $client->id) {
            abort(404);
        }

        return DB::transaction(function () use ($client, $attributes, $actor, $unit) {
            $unit ??= new IndustryUnit;
            $before = $unit->exists ? $unit->auditSnapshot() : null;

            $unit->fill($attributes);

            if (! $unit->exists) {
                $unit->client_id = $client->id;
                $unit->status = RecordStatus::Active;
                $unit->created_by = $actor->id;
            }

            if ($before !== null && ! $unit->isDirty()) {
                return $unit;
            }

            try {
                $unit->save();
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'name' => __('ui.units.duplicate_name'),
                ]);
            }

            $after = $unit->auditSnapshot();
            [$oldValues, $newValues] = $before === null ? [[], $after] : AuditDiff::changed($before, $after);

            if ($oldValues !== [] || $newValues !== []) {
                $this->audit->log(
                    action: $before === null ? 'industry_unit.created' : 'industry_unit.updated',
                    subject: $unit,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $unit;
        });
    }
}
