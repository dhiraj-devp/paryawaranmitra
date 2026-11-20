<?php

namespace App\Http\Requests;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeWorkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('workOrder');

        return $workOrder instanceof WorkOrder && ($this->user()?->can('update', $workOrder) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(WorkOrderStatus::class)],
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
