<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('feeSchedule');

        if ($schedule instanceof FeeSchedule) {
            return $this->user()?->can('update', $schedule) ?? false;
        }

        return $this->user()?->can('create', FeeSchedule::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $until = trim((string) $this->input('effective_until', ''));

        $this->merge([
            'effective_until' => $until === '' ? null : $until,
            'government_fee' => trim((string) $this->input('government_fee', '')),
            'consultancy_fee' => trim((string) $this->input('consultancy_fee', '')),
            'internal_notes' => $this->blankToNull('internal_notes'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = ! $this->route('feeSchedule') instanceof FeeSchedule;

        return [
            'service_id' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                Rule::exists('services', 'id')->where('status', RecordStatus::Active->value),
            ],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'government_fee' => ['required', 'regex:/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/'],
            'consultancy_fee' => ['required', 'regex:/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function scheduleAttributes(): array
    {
        return $this->safe()->only([
            'effective_from',
            'effective_until',
            'government_fee',
            'consultancy_fee',
            'internal_notes',
        ]);
    }

    public function service(): Service
    {
        $schedule = $this->route('feeSchedule');

        if ($schedule instanceof FeeSchedule) {
            $schedule->loadMissing('service');

            return $schedule->service;
        }

        return Service::query()->findOrFail($this->integer('service_id'));
    }

    private function blankToNull(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
