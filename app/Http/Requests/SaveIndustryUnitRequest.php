<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\IndustryUnit;
use App\Support\NameKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveIndustryUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        if ($unit instanceof IndustryUnit) {
            return $this->user()?->can('update', $unit) ?? false;
        }

        return $this->user()?->can('create', IndustryUnit::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'activity' => $this->blankToNull('activity'),
            'site_address_line' => trim((string) $this->input('site_address_line', '')),
            'site_landmark' => $this->blankToNull('site_landmark'),
            'site_village' => $this->blankToNull('site_village'),
            'site_taluka' => $this->blankToNull('site_taluka'),
            'site_district' => trim((string) $this->input('site_district', '')),
            'site_state' => trim((string) $this->input('site_state', '')) ?: 'Maharashtra',
            'site_pincode' => trim((string) $this->input('site_pincode', '')),
            'plot_number' => $this->blankToNull('plot_number'),
            'site_contact_name' => $this->blankToNull('site_contact_name'),
            'site_contact_phone' => $this->digitsOrNull('site_contact_phone'),
            'notes' => $this->blankToNull('notes'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'industry_category' => ['required', 'string', Rule::in(config('industries.categories', []))],
            'activity' => ['nullable', 'string', 'max:2000'],
            'site_address_line' => ['required', 'string', 'max:255'],
            'site_landmark' => ['nullable', 'string', 'max:255'],
            'site_village' => ['nullable', 'string', 'max:120'],
            'site_taluka' => ['nullable', 'string', 'max:120'],
            'site_district' => ['required', 'string', 'max:120'],
            'site_state' => ['required', 'string', 'max:120'],
            'site_pincode' => ['required', 'digits:6'],
            'plot_number' => ['nullable', 'string', 'max:80'],
            'site_contact_name' => ['nullable', 'string', 'max:120'],
            'site_contact_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,12}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $name = trim((string) $this->input('name'));
            $nameKey = NameKey::make($name);

            if ($name !== '' && $nameKey === '') {
                $validator->errors()->add('name', __('ui.units.name_required'));

                return;
            }

            if ($nameKey === '' || $validator->errors()->isNotEmpty()) {
                return;
            }

            $client = $this->route('client');
            $unit = $this->route('unit');

            if (! $client instanceof Client) {
                return;
            }

            $taken = IndustryUnit::query()
                ->where('client_id', $client->id)
                ->where('name_key', $nameKey)
                ->when($unit instanceof IndustryUnit, fn ($query) => $query->whereKeyNot($unit->id))
                ->exists();

            if ($taken) {
                $validator->errors()->add('name', __('ui.units.duplicate_name'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('ui.units.name'),
            'industry_category' => __('ui.units.category'),
            'site_address_line' => __('ui.units.site_address'),
            'site_district' => __('ui.units.district'),
            'site_pincode' => __('ui.units.pincode'),
            'plot_number' => __('ui.units.plot'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function unitAttributes(): array
    {
        return $this->safe()->except([]);
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
