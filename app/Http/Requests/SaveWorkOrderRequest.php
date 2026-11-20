<?php

namespace App\Http\Requests;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class SaveWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('workOrder');

        return $workOrder instanceof WorkOrder && ($this->user()?->can('update', $workOrder) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $blank = [];

        foreach (['target_completion_date', 'internal_notes'] as $key) {
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
            'work_description' => ['required', 'string', 'min:2', 'max:5000'],
            'start_date' => ['required', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function workOrderAttributes(): array
    {
        return $this->safe()->only([
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
            'work_description' => __('ui.work_orders.description'),
            'start_date' => __('ui.work_orders.start_date'),
            'target_completion_date' => __('ui.work_orders.target_date'),
            'internal_notes' => __('ui.work_orders.notes'),
        ];
    }
}
