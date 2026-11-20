<?php

namespace App\Http\Requests;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeQuotationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation && ($this->user()?->can('update', $quotation) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(QuotationStatus::class)],
            'reason' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn () => QuotationStatus::tryFrom((string) $this->input('status'))?->needsReason() ?? false),
            ],
            'confirm_status' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('reason') && trim((string) $this->input('reason')) === '') {
            $this->merge(['reason' => null]);
        }
    }
}
