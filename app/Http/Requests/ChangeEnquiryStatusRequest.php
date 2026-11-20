<?php

namespace App\Http\Requests;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEnquiryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('enquiry');

        return $enquiry instanceof Enquiry && ($this->user()?->can('update', $enquiry) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
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
