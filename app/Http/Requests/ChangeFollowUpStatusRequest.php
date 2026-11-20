<?php

namespace App\Http\Requests;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeFollowUpStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $followUp = $this->route('followUp');

        return $followUp instanceof FollowUp && ($this->user()?->can('complete', $followUp) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'follow_up_status' => ['required', Rule::enum(FollowUpStatus::class)],
            'return_to' => ['nullable', Rule::in(['parent', 'index'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'follow_up_status' => __('ui.follow_ups.status'),
        ];
    }
}
