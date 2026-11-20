<?php

namespace App\Http\Requests;

use App\Enums\EnquirySource;
use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('enquiry');

        if ($enquiry instanceof Enquiry) {
            return $this->user()?->can('update', $enquiry) ?? false;
        }

        return $this->user()?->can('create', Enquiry::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $blank = [];

        foreach (['client_id', 'industry_unit_id', 'service_id', 'assigned_to', 'email', 'internal_notes', 'next_follow_up_on'] as $key) {
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
        $enquiry = $this->route('enquiry');
        $currentAssignee = $enquiry instanceof Enquiry ? $enquiry->assigned_to : null;

        return [
            'enquiry_date' => ['required', 'date', 'before_or_equal:today'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'industry_unit_id' => ['nullable', 'integer', Rule::exists('industry_units', 'id')],
            'contact_name' => ['required', 'string', 'min:2', 'max:160'],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'source' => ['required', Rule::enum(EnquirySource::class)],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'requirement' => ['required', 'string', 'min:2', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where(function ($query) use ($currentAssignee) {
                if ($this->input('assigned_to') !== null && (int) $this->input('assigned_to') === (int) $currentAssignee) {
                    return;
                }

                $query->where('is_active', true);
            })],
            'next_follow_up_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function enquiryAttributes(): array
    {
        return $this->safe()->only([
            'enquiry_date',
            'client_id',
            'industry_unit_id',
            'contact_name',
            'mobile',
            'email',
            'source',
            'service_id',
            'requirement',
            'internal_notes',
            'assigned_to',
            'next_follow_up_on',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'enquiry_date' => __('ui.enquiries.date'),
            'client_id' => __('ui.enquiries.client'),
            'industry_unit_id' => __('ui.enquiries.unit'),
            'contact_name' => __('ui.enquiries.contact'),
            'mobile' => __('ui.enquiries.mobile'),
            'email' => __('ui.enquiries.email'),
            'source' => __('ui.enquiries.source'),
            'service_id' => __('ui.enquiries.service'),
            'requirement' => __('ui.enquiries.requirement'),
            'internal_notes' => __('ui.enquiries.notes'),
            'assigned_to' => __('ui.enquiries.assignee'),
            'next_follow_up_on' => __('ui.enquiries.next_follow_up'),
        ];
    }
}
