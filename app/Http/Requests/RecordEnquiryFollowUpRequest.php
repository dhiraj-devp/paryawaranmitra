<?php

namespace App\Http\Requests;

use App\Enums\FollowUpMethod;
use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordEnquiryFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('enquiry');

        return $enquiry instanceof Enquiry && ($this->user()?->can('update', $enquiry) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('next_follow_up_on') && $this->input('next_follow_up_on') === '') {
            $this->merge(['next_follow_up_on' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'followed_up_at' => ['required', 'date', 'before_or_equal:now'],
            'method' => ['required', Rule::enum(FollowUpMethod::class)],
            'notes' => ['required', 'string', 'min:2', 'max:5000'],
            'outcome' => ['required', 'string', 'min:2', 'max:160'],
            'next_follow_up_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function followUpAttributes(): array
    {
        return $this->safe()->only([
            'followed_up_at',
            'method',
            'notes',
            'outcome',
            'next_follow_up_on',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'followed_up_at' => __('ui.follow_ups.when'),
            'method' => __('ui.follow_ups.method'),
            'notes' => __('ui.follow_ups.notes'),
            'outcome' => __('ui.follow_ups.outcome'),
            'next_follow_up_on' => __('ui.follow_ups.next'),
        ];
    }
}
