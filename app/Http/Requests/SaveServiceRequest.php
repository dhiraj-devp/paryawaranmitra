<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        if ($service instanceof Service) {
            return $this->user()?->can('update', $service) ?? false;
        }

        return $this->user()?->can('create', Service::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $industries = $this->input('industries', []);

        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code', ''))),
            'name' => trim((string) $this->input('name', '')),
            'category' => trim((string) $this->input('category', '')),
            'description' => trim((string) $this->input('description', '')),
            'detailed_description' => $this->blankToNull('detailed_description'),
            'expected_processing_days' => $this->blankToNull('expected_processing_days'),
            'internal_notes' => $this->blankToNull('internal_notes'),
            'industries' => is_array($industries) ? array_values($industries) : [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $service = $this->route('service');

        return [
            'code' => ['required', 'string', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', 'max:30', Rule::unique('services', 'code')->ignore($service instanceof Service ? $service->id : null)],
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'category' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['required', 'string', 'max:500'],
            'detailed_description' => ['nullable', 'string', 'max:5000'],
            'expected_processing_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'industries' => ['array'],
            'industries.*' => ['string', Rule::in(config('industries.categories', []))],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serviceAttributes(): array
    {
        return $this->safe()->except(['industries']);
    }

    /**
     * @return list<string>
     */
    public function industries(): array
    {
        return array_values($this->validated('industries') ?? []);
    }

    private function blankToNull(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
