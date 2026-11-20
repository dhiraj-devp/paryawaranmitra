<?php

namespace App\Http\Requests;

use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('enquiry');

        return $enquiry instanceof Enquiry && ($this->user()?->can('convert', $enquiry) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $blank = [];

        foreach (['client_id', 'industry_unit_id', 'service_id', 'assigned_to', 'target_completion_date', 'internal_notes'] as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $blank[$key] = null;
            }
        }

        if ($blank !== []) {
            $this->merge($blank);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'industry_unit_id' => ['nullable', 'integer', Rule::exists('industry_units', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'work_description' => ['required', 'string', 'min:2', 'max:5000'],
            'start_date' => ['required', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'confirm_conversion' => ['accepted'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function conversionAttributes(): array
    {
        return $this->safe()->only([
            'client_id',
            'industry_unit_id',
            'service_id',
            'assigned_to',
            'work_description',
            'start_date',
            'target_completion_date',
            'internal_notes',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => __('ui.enquiries.client'),
            'industry_unit_id' => __('ui.enquiries.unit'),
            'service_id' => __('ui.enquiries.service'),
            'assigned_to' => __('ui.enquiries.assignee'),
            'work_description' => __('ui.work_orders.description'),
            'start_date' => __('ui.work_orders.start_date'),
            'target_completion_date' => __('ui.work_orders.target_date'),
            'internal_notes' => __('ui.work_orders.notes'),
            'confirm_conversion' => __('ui.enquiries.confirm_conversion'),
        ];
    }
}
