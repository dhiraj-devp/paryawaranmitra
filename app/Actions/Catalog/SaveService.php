<?php

namespace App\Actions\Catalog;

use App\Enums\RecordStatus;
use App\Models\Service;
use App\Models\User;
use App\Services\Audit\AuditDiff;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $industries
     */
    public function execute(array $attributes, array $industries, User $actor, ?Service $service = null): Service
    {
        $industries = collect($industries)->unique()->sort()->values()->all();

        return DB::transaction(function () use ($attributes, $industries, $actor, $service) {
            $service ??= new Service;
            $before = $service->exists ? $service->auditSnapshot() : null;
            $previousIndustries = $service->exists
                ? $service->industries()->orderBy('industry_category')->pluck('industry_category')->all()
                : [];

            $service->fill($attributes);
            $fieldsChanged = $service->isDirty();
            $eligibilityChanged = $previousIndustries !== $industries;

            if ($service->exists && ! $fieldsChanged && ! $eligibilityChanged) {
                return $service;
            }

            if (! $service->exists) {
                $service->status = RecordStatus::Active;
                $service->created_by = $actor->id;
            }

            $service->updated_by = $actor->id;

            try {
                $service->save();
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'code' => __('ui.services.code_taken'),
                ]);
            }

            if ($eligibilityChanged) {
                $service->industries()->delete();

                foreach ($industries as $category) {
                    $service->industries()->create([
                        'industry_category' => $category,
                    ]);
                }
            }

            if ($fieldsChanged || $before === null) {
                $after = $service->auditSnapshot();
                [$oldValues, $newValues] = $before === null ? [[], $after] : AuditDiff::changed($before, $after);

                $this->audit->log(
                    action: $before === null ? 'service.created' : 'service.updated',
                    subject: $service,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            if ($eligibilityChanged) {
                $this->audit->log(
                    action: 'service.eligibility_changed',
                    subject: $service,
                    oldValues: ['industries' => $previousIndustries],
                    newValues: ['industries' => $industries],
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $service;
        });
    }
}
