<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Models\FeeSchedule;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCatalogStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('feeSchedule');

        if ($schedule instanceof FeeSchedule) {
            return $this->user()?->can('update', $schedule) ?? false;
        }

        $service = $this->route('service');

        return $service instanceof Service && ($this->user()?->can('update', $service) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RecordStatus::class)],
            'confirm_status' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => __('ui.status.label'),
            'confirm_status' => __('ui.status.confirm'),
        ];
    }
}
