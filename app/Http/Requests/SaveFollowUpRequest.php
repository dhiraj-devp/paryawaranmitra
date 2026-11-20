<?php

namespace App\Http\Requests;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\FollowUp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $followUp = $this->route('followUp');

        if ($followUp instanceof FollowUp) {
            return $this->user()?->can('update', $followUp) ?? false;
        }

        $parent = $this->resolvedParent();

        return $parent !== null && ($this->user()?->can('create', [FollowUp::class, $parent]) ?? false);
    }

    protected function prepareForValidation(): void
    {
        foreach (['follow_up_assigned_to', 'reminder_at'] as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'followable_type' => ['required', Rule::in(['enquiry', 'client'])],
            'followable_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(FollowUpType::class)],
            'scheduled_at' => ['required', 'date'],
            'follow_up_assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['required', 'string', 'min:2', 'max:5000'],
            'reminder_at' => ['nullable', 'date'],
            'follow_up_status' => ['required', Rule::enum(FollowUpStatus::class)],
            'return_to' => ['nullable', Rule::in(['parent', 'index'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function followUpAttributes(): array
    {
        return [
            'type' => $this->string('type')->toString(),
            'scheduled_at' => $this->input('scheduled_at'),
            'assigned_to' => $this->input('follow_up_assigned_to'),
            'notes' => $this->string('notes')->toString(),
            'reminder_at' => $this->input('reminder_at'),
            'status' => $this->string('follow_up_status')->toString(),
        ];
    }

    public function resolvedParent(): ?Model
    {
        $id = (int) $this->input('followable_id');

        return match ($this->input('followable_type')) {
            'enquiry' => Enquiry::query()->find($id),
            'client' => Client::query()->find($id),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => __('ui.follow_ups.type'),
            'scheduled_at' => __('ui.follow_ups.scheduled'),
            'follow_up_assigned_to' => __('ui.follow_ups.assignee'),
            'notes' => __('ui.follow_ups.notes'),
            'reminder_at' => __('ui.follow_ups.reminder'),
            'follow_up_status' => __('ui.follow_ups.status'),
        ];
    }
}
