<x-ui.table>
    <x-slot:head>
        <th class="px-4 py-3 font-medium">{{ __('ui.quotations.description') }}</th>
        <th class="px-4 py-3 font-medium">{{ __('ui.quotations.quantity') }}</th>
        <th class="px-4 py-3 font-medium">{{ __('ui.quotations.government_fee') }}</th>
        <th class="px-4 py-3 font-medium">{{ __('ui.quotations.consultancy_fee') }}</th>
        <th class="px-4 py-3 font-medium">{{ __('ui.quotations.discount') }}</th>
        <th class="px-4 py-3 font-medium">{{ __('ui.quotations.line_total') }}</th>
    </x-slot:head>
    @foreach ($revision->lines as $line)
        <tr>
            <td class="px-4 py-3">
                <div>{{ $line->description }}</div>
                <div class="text-xs text-muted">
                    {{ $line->line_type->label() }}
                    @if ($line->charge_side)
                        · {{ $line->charge_side->label() }}
                    @endif
                    @if ($line->amounts_adjusted)
                        · {{ __('ui.quotations.adjusted') }}
                    @endif
                </div>
            </td>
            <td class="px-4 py-3">{{ $line->quantity }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::inr($line->government_total) }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::inr($line->consultancy_total) }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::inr($line->discount_amount) }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::inr($line->line_total) }}</td>
        </tr>
    @endforeach
</x-ui.table>

<dl class="grid gap-3 border-t border-line p-5 sm:grid-cols-2">
    <x-ui.definition :label="__('ui.quotations.government_total')">{{ \App\Support\Money::inr($revision->government_total) }}</x-ui.definition>
    <x-ui.definition :label="__('ui.quotations.consultancy_total')">{{ \App\Support\Money::inr($revision->consultancy_total) }}</x-ui.definition>
    <x-ui.definition :label="__('ui.quotations.subtotal')">{{ \App\Support\Money::inr($revision->subtotal) }}</x-ui.definition>
    <x-ui.definition :label="__('ui.quotations.discount')">{{ \App\Support\Money::inr($revision->discount_amount) }}</x-ui.definition>
    <x-ui.definition :label="__('ui.quotations.tax')">{{ \App\Support\Money::inr($revision->tax_amount) }}</x-ui.definition>
    <x-ui.definition :label="__('ui.quotations.total')">{{ \App\Support\Money::inr($revision->grand_total) }}</x-ui.definition>
    <x-ui.definition :label="__('ui.quotations.amount_payable')">{{ \App\Support\Money::inr($revision->grand_total) }}</x-ui.definition>
</dl>
