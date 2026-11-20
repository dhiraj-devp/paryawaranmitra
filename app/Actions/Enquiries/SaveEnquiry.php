<?php

namespace App\Actions\Enquiries;

use App\Enums\EnquiryStatus;
use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\IndustryUnit;
use App\Models\Service;
use App\Models\User;
use App\Services\Audit\AuditDiff;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\ServiceEligibility;
use App\Services\Numbers\DocumentNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveEnquiry
{
    public function __construct(
        private AuditLogger $audit,
        private DocumentNumber $numbers,
        private ServiceEligibility $eligibility,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $actor, ?Enquiry $enquiry = null): Enquiry
    {
        $enquiry ??= new Enquiry;
        $attributes = $this->validatedLinks($attributes, $enquiry);

        return DB::transaction(function () use ($attributes, $actor, $enquiry) {
            $before = $enquiry->exists ? $enquiry->auditSnapshot() : null;
            $creating = ! $enquiry->exists;

            $enquiry->fill($attributes);

            if ($creating) {
                $enquiry->status = EnquiryStatus::New;
                $enquiry->created_by = $actor->id;
                $enquiry->number = $this->numbers->allocate(
                    'enquiry',
                    Carbon::parse($attributes['enquiry_date'])->year,
                );
            }

            if (! $creating && ! $enquiry->isDirty()) {
                return $enquiry;
            }

            $enquiry->updated_by = $actor->id;
            $enquiry->save();

            $after = $enquiry->fresh()->auditSnapshot();
            [$oldValues, $newValues] = $before === null ? [[], $after] : AuditDiff::changed($before, $after);
            $assignmentOld = $oldValues['assigned_to'] ?? null;
            $assignmentNew = $newValues['assigned_to'] ?? null;
            $assignmentChanged = array_key_exists('assigned_to', $newValues);
            unset($oldValues['assigned_to'], $newValues['assigned_to']);

            if ($before === null) {
                $this->audit->log(
                    action: 'enquiry.created',
                    subject: $enquiry,
                    newValues: $after,
                    actor: $actor,
                    portal: $actor->portal,
                );
            } elseif ($oldValues !== [] || $newValues !== []) {
                $this->audit->log(
                    action: 'enquiry.updated',
                    subject: $enquiry,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            if ($before !== null && $assignmentChanged) {
                $this->audit->log(
                    action: 'enquiry.assignment_changed',
                    subject: $enquiry,
                    oldValues: ['assigned_to' => $assignmentOld],
                    newValues: ['assigned_to' => $assignmentNew],
                    actor: $actor,
                    portal: $actor->portal,
                );
            }

            return $enquiry;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedLinks(array $attributes, Enquiry $enquiry): array
    {
        foreach (['client_id', 'industry_unit_id', 'service_id', 'assigned_to', 'email', 'internal_notes', 'next_follow_up_on'] as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] === '') {
                $attributes[$key] = null;
            }
        }

        if ($enquiry->status === EnquiryStatus::Converted) {
            foreach (['client_id', 'industry_unit_id', 'service_id'] as $key) {
                if (! $this->sameId($attributes[$key] ?? null, $enquiry->{$key})) {
                    throw ValidationException::withMessages([
                        $key => __('ui.enquiries.locked_link'),
                    ]);
                }
            }
        }

        $client = $this->client($attributes['client_id'] ?? null, $enquiry);
        $unit = $this->unit($attributes['industry_unit_id'] ?? null, $client, $enquiry);
        $service = $this->service($attributes['service_id'] ?? null, $unit, $enquiry);
        $this->assignee($attributes['assigned_to'] ?? null, $enquiry);

        $attributes['client_id'] = $client?->id;
        $attributes['industry_unit_id'] = $unit?->id;
        $attributes['service_id'] = $service?->id;

        return $attributes;
    }

    private function client(mixed $id, Enquiry $enquiry): ?Client
    {
        if ($id === null) {
            return null;
        }

        $client = Client::query()->find($id);

        if (! $client) {
            throw ValidationException::withMessages([
                'client_id' => __('ui.enquiries.client_missing'),
            ]);
        }

        if ($this->sameId($id, $enquiry->client_id)) {
            return $client;
        }

        if ($client->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'client_id' => __('ui.enquiries.client_inactive'),
            ]);
        }

        return $client;
    }

    private function unit(mixed $id, ?Client $client, Enquiry $enquiry): ?IndustryUnit
    {
        if ($id === null) {
            return null;
        }

        $unit = IndustryUnit::query()->find($id);

        if (! $unit || $client === null || $unit->client_id !== $client->id) {
            throw ValidationException::withMessages([
                'industry_unit_id' => __('ui.enquiries.unit_mismatch'),
            ]);
        }

        if ($this->sameId($id, $enquiry->industry_unit_id)) {
            return $unit;
        }

        if ($unit->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'industry_unit_id' => __('ui.enquiries.unit_inactive'),
            ]);
        }

        return $unit;
    }

    private function service(mixed $id, ?IndustryUnit $unit, Enquiry $enquiry): ?Service
    {
        if ($id === null) {
            return null;
        }

        $service = Service::query()->find($id);

        if (! $service) {
            throw ValidationException::withMessages([
                'service_id' => __('ui.enquiries.service_missing'),
            ]);
        }

        $unchanged = $this->sameId($id, $enquiry->service_id)
            && $this->sameId($unit?->id, $enquiry->industry_unit_id);

        if ($unchanged) {
            return $service;
        }

        if ($service->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'service_id' => __('ui.enquiries.service_inactive'),
            ]);
        }

        if ($unit !== null && ! $this->eligibility->allows($service, $unit->industry_category)) {
            throw ValidationException::withMessages([
                'service_id' => __('ui.enquiries.service_ineligible'),
            ]);
        }

        return $service;
    }

    private function assignee(mixed $id, Enquiry $enquiry): void
    {
        if ($id === null || $this->sameId($id, $enquiry->assigned_to)) {
            return;
        }

        $assignee = User::query()->find($id);

        if (! $assignee || ! $assignee->is_active) {
            throw ValidationException::withMessages([
                'assigned_to' => __('ui.enquiries.assignee_inactive'),
            ]);
        }
    }

    private function sameId(mixed $left, mixed $right): bool
    {
        $left = $left === null || $left === '' ? null : (int) $left;
        $right = $right === null || $right === '' ? null : (int) $right;

        return $left === $right;
    }
}
