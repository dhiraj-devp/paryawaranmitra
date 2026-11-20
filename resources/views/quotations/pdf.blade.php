<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->quotation_number }} {{ $revision->label() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1c1917; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        p { margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d6d3d1; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f4; font-size: 11px; }
        .muted { color: #57534e; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; }
        .totals td { border: none; padding: 3px 0; }
    </style>
</head>
<body>
    <h1>{{ $company['legal_name'] ?? 'Paryavaran Mitra' }}</h1>
    @if (! empty($company['address']))
        <p>{{ $company['address'] }}</p>
    @endif
    @if (! empty($company['phone']) || ! empty($company['email']))
        <p class="muted">{{ collect([$company['phone'] ?? null, $company['email'] ?? null])->filter()->implode(' · ') }}</p>
    @endif
    @if (! empty($company['gstin']))
        <p>{{ __('ui.quotations.gstin') }}: {{ $company['gstin'] }}</p>
    @else
        <p class="muted">{{ __('ui.quotations.gstin_missing') }}</p>
    @endif

    <h2>{{ __('ui.quotations.document_title') }} {{ $quotation->quotation_number }} · {{ $revision->label() }}</h2>
    <p>{{ __('ui.quotations.issue_date') }}: {{ $revision->issue_date?->format('d-m-Y') }}</p>
    <p>{{ __('ui.quotations.valid_until') }}: {{ $revision->valid_until?->format('d-m-Y') }}</p>
    <p>{{ __('ui.quotations.work_order') }}: {{ $quotation->workOrder?->number }}</p>

    <h2>{{ __('ui.quotations.client') }}</h2>
    <p>{{ $quotation->workOrder?->client?->legal_name }}</p>
    @if ($quotation->workOrder?->client?->address_line)
        <p>{{ $quotation->workOrder->client->address_line }}</p>
    @endif
    <p class="muted">{{ collect([$quotation->workOrder?->client?->city, $quotation->workOrder?->client?->district, $quotation->workOrder?->client?->state, $quotation->workOrder?->client?->pincode])->filter()->implode(', ') }}</p>

    <h2>{{ __('ui.quotations.unit') }}</h2>
    <p>{{ $quotation->workOrder?->industryUnit?->name }}</p>
    @if ($quotation->workOrder?->industryUnit?->site_address_line)
        <p>{{ $quotation->workOrder->industryUnit->site_address_line }}</p>
    @endif
    <p class="muted">{{ collect([$quotation->workOrder?->industryUnit?->site_district, $quotation->workOrder?->industryUnit?->site_state, $quotation->workOrder?->industryUnit?->site_pincode])->filter()->implode(', ') }}</p>

    <h2>{{ __('ui.quotations.service') }}</h2>
    <p>{{ $quotation->workOrder?->service?->code }} — {{ $quotation->workOrder?->service?->name }}</p>
    @if ($quotation->workOrder?->work_description)
        <p>{{ $quotation->workOrder->work_description }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>{{ __('ui.quotations.description') }}</th>
                <th class="right">{{ __('ui.quotations.quantity') }}</th>
                <th class="right">{{ __('ui.quotations.government_fee') }}</th>
                <th class="right">{{ __('ui.quotations.consultancy_fee') }}</th>
                <th class="right">{{ __('ui.quotations.discount') }}</th>
                <th class="right">{{ __('ui.quotations.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($revision->lines as $line)
                <tr>
                    <td>
                        {{ $line->description }}
                        @if ($line->charge_side)
                            <div class="muted">{{ $line->charge_side->label() }}</div>
                        @endif
                    </td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">{{ \App\Support\Money::inr($line->government_total) }}</td>
                    <td class="right">{{ \App\Support\Money::inr($line->consultancy_total) }}</td>
                    <td class="right">{{ \App\Support\Money::inr($line->discount_amount) }}</td>
                    <td class="right">{{ \App\Support\Money::inr($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('ui.quotations.government_total') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->government_total) }}</td>
        </tr>
        <tr>
            <td>{{ __('ui.quotations.consultancy_total') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->consultancy_total) }}</td>
        </tr>
        <tr>
            <td>{{ __('ui.quotations.subtotal') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->subtotal) }}</td>
        </tr>
        <tr>
            <td>{{ __('ui.quotations.discount') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->discount_amount) }}</td>
        </tr>
        <tr>
            <td>{{ __('ui.quotations.tax') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->tax_amount) }}</td>
        </tr>
        <tr>
            <td>{{ __('ui.quotations.total') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->grand_total) }}</td>
        </tr>
        <tr>
            <td>{{ __('ui.quotations.amount_payable') }}</td>
            <td class="right">{{ \App\Support\Money::inr($revision->grand_total) }}</td>
        </tr>
    </table>

    @if ($taxPending)
        <p class="muted">{{ __('ui.quotations.tax_pending') }}</p>
    @endif
    <p class="muted">{{ __('ui.quotations.payment_not_recorded') }}</p>

    @if ($revision->payment_terms)
        <h2>{{ __('ui.quotations.payment_terms') }}</h2>
        <p>{{ $revision->payment_terms }}</p>
    @endif
    @if ($revision->customer_notes)
        <h2>{{ __('ui.quotations.customer_notes') }}</h2>
        <p>{{ $revision->customer_notes }}</p>
    @endif
    @if ($revision->terms_and_conditions)
        <h2>{{ __('ui.quotations.terms') }}</h2>
        <p>{{ $revision->terms_and_conditions }}</p>
    @endif
</body>
</html>
