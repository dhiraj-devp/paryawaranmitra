<?php

namespace App\Http\Requests;

use App\Enums\ClientConstitution;
use App\Models\Client;
use App\Services\Clients\ClientDuplicateDetector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        if ($client instanceof Client) {
            return $this->user()?->can('update', $client) ?? false;
        }

        return $this->user()?->can('create', Client::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $pan = strtoupper(trim((string) $this->input('pan', '')));
        $gstin = strtoupper(trim((string) $this->input('gstin', '')));

        $this->merge([
            'legal_name' => trim((string) $this->input('legal_name', '')),
            'trade_name' => $this->blankToNull('trade_name'),
            'pan' => $pan === '' ? null : $pan,
            'gstin' => $gstin === '' ? null : $gstin,
            'email' => $this->blankToNull('email'),
            'phone' => $this->digitsOrNull('phone'),
            'contact_name' => $this->blankToNull('contact_name'),
            'address_line' => $this->blankToNull('address_line'),
            'city' => $this->blankToNull('city'),
            'district' => $this->blankToNull('district'),
            'state' => trim((string) $this->input('state', '')) ?: 'Maharashtra',
            'pincode' => $this->blankToNull('pincode'),
            'notes' => $this->blankToNull('notes'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $client = $this->route('client');
        $clientId = $client instanceof Client ? $client->id : null;

        return [
            'legal_name' => ['required', 'string', 'min:2', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'constitution' => ['required', Rule::enum(ClientConstitution::class)],
            'pan' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/', Rule::unique('clients', 'pan')->ignore($clientId)],
            'gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', Rule::unique('clients', 'gstin')->ignore($clientId)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,12}$/'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'pincode' => ['nullable', 'digits:6'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'acknowledge_duplicate' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pan = $this->input('pan');
            $gstin = $this->input('gstin');

            if (is_string($pan) && is_string($gstin) && substr($gstin, 2, 10) !== $pan) {
                $validator->errors()->add('gstin', __('ui.clients.pan_gstin_mismatch'));

                return;
            }

            $client = $this->route('client');
            $matches = app(ClientDuplicateDetector::class)->sameLegalName(
                (string) $this->input('legal_name'),
                $client instanceof Client ? $client->id : null,
            );

            if ($matches->isNotEmpty() && ! $this->boolean('acknowledge_duplicate')) {
                $validator->errors()->add('duplicate_name', __('ui.clients.duplicate_name', [
                    'names' => $matches->pluck('legal_name')->join(', '),
                ]));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'legal_name' => __('ui.clients.legal_name'),
            'trade_name' => __('ui.clients.trade_name'),
            'constitution' => __('ui.clients.constitution'),
            'pan' => __('ui.clients.pan'),
            'gstin' => __('ui.clients.gstin'),
            'phone' => __('ui.clients.phone'),
            'contact_name' => __('ui.clients.contact_name'),
            'address_line' => __('ui.clients.address'),
            'pincode' => __('ui.clients.pincode'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function clientAttributes(): array
    {
        return $this->safe()->except(['acknowledge_duplicate']);
    }

    private function blankToNull(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    private function digitsOrNull(string $key): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input($key, '')) ?? '';

        return $digits === '' ? null : $digits;
    }
}
