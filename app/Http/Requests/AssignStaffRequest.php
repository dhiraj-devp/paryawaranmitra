<?php

namespace App\Http\Requests;

use App\Models\Enquiry;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('workOrder');

        if ($workOrder instanceof WorkOrder) {
            return $this->user()?->can('update', $workOrder) ?? false;
        }

        $enquiry = $this->route('enquiry');

        return $enquiry instanceof Enquiry && ($this->user()?->can('update', $enquiry) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('assigned_to') && $this->input('assigned_to') === '') {
            $this->merge(['assigned_to' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'confirm_assignment' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'assigned_to' => __('ui.enquiries.assignee'),
            'confirm_assignment' => __('ui.enquiries.confirm_assignment'),
        ];
    }
}
