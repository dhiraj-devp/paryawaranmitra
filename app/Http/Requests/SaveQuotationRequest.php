<?php

namespace App\Http\Requests;

use App\Enums\ChargeSide;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Services\Quotations\TaxSettings;
use App\Support\Decimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SaveQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->quotation();

        if ($quotation === null) {
            return $this->user()?->can('create', Quotation::class) ?? false;
        }

        return $this->user()?->can('update', $quotation) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $quotation = $this->quotation();
        $accepted = $quotation?->status === QuotationStatus::Accepted;

        return [
            'work_order_id' => $quotation === null
                ? ['required', 'integer', 'exists:work_orders,id']
                : ['prohibited'],
            'client_id' => ['prohibited'],
            'industry_unit_id' => ['prohibited'],
            'service_id' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'grand_total' => ['prohibited'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date'],
            'government_fee' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'consultancy_fee' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'service_quantity' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'service_discount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'service_description' => ['nullable', 'string', 'max:500'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'terms_and_conditions' => ['nullable', 'string', 'max:5000'],
            'customer_notes' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'custom_lines' => ['nullable', 'array', 'max:20'],
            'custom_lines.*.description' => ['nullable', 'string', 'max:500'],
            'custom_lines.*.charge_side' => ['nullable', Rule::enum(ChargeSide::class)],
            'custom_lines.*.quantity' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'custom_lines.*.amount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'custom_lines.*.discount_amount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'tax_amount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/', function (string $attribute, mixed $value, callable $fail): void {
                if ($value === null || $value === '' || ! app(TaxSettings::class)->pending()) {
                    return;
                }

                try {
                    $amount = Decimal::normalize((string) $value);
                } catch (InvalidArgumentException) {
                    $fail(__('ui.quotations.tax_not_configured'));

                    return;
                }

                if (Decimal::comp($amount, '0.00') !== 0) {
                    $fail(__('ui.quotations.tax_not_configured'));
                }
            }],
            'confirm_revision' => $accepted ? ['accepted'] : ['nullable'],
            'revision_reason' => $accepted ? ['required', 'string', 'max:500'] : ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quotationInput(): array
    {
        return $this->safe()->except(['work_order_id']);
    }

    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach ([
            'service_description',
            'service_discount',
            'payment_terms',
            'terms_and_conditions',
            'customer_notes',
            'internal_notes',
            'revision_reason',
            'tax_amount',
        ] as $field) {
            if ($this->exists($field) && trim((string) $this->input($field)) === '') {
                $clean[$field] = null;
            }
        }

        if ($clean !== []) {
            $this->merge($clean);
        }
    }

    private function quotation(): ?Quotation
    {
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation ? $quotation : null;
    }
}
