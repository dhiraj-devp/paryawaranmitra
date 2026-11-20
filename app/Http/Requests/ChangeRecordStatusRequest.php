<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Models\Client;
use App\Models\IndustryUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRecordStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        if ($unit instanceof IndustryUnit) {
            return $this->user()?->can('update', $unit) ?? false;
        }

        $client = $this->route('client');

        return $client instanceof Client && ($this->user()?->can('update', $client) ?? false);
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
