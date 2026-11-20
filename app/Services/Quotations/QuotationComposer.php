<?php

namespace App\Services\Quotations;

use App\Enums\ChargeSide;
use App\Enums\QuotationLineType;
use App\Enums\RecordStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Services\Fees\ApplicableFee;
use App\Support\Decimal;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuotationComposer
{
    public function __construct(
        private ApplicableFee $fees,
        private TaxSettings $tax,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function prepare(WorkOrder $workOrder, array $input): PreparedRevision
    {
        $workOrder->load(['client', 'industryUnit', 'service']);
        $this->assertWorkOrder($workOrder);
        $this->assertNoTax($input);

        $issueDate = Carbon::parse((string) $input['issue_date'])->startOfDay();
        $validUntil = Carbon::parse((string) $input['valid_until'])->startOfDay();

        if ($validUntil->lt($issueDate)) {
            throw ValidationException::withMessages([
                'valid_until' => __('ui.quotations.validity_after_issue'),
            ]);
        }

        $schedule = $this->fees->on($workOrder->service, $issueDate);

        if ($schedule === null) {
            throw ValidationException::withMessages([
                'issue_date' => __('ui.quotations.no_fee'),
            ]);
        }

        $quantity = $this->money((string) $input['service_quantity'], 'service_quantity', '99999999.99');
        $this->assertPositive($quantity, 'service_quantity');
        $government = $this->money((string) $input['government_fee'], 'government_fee');
        $consultancy = $this->money((string) $input['consultancy_fee'], 'consultancy_fee');
        $discount = $this->money((string) ($input['service_discount'] ?? '0'), 'service_discount');
        $priced = $this->price('service_discount', $quantity, $government, $consultancy, $discount);

        $adjusted = Decimal::comp($government, (string) $schedule->government_fee) !== 0
            || Decimal::comp($consultancy, (string) $schedule->consultancy_fee) !== 0;

        $description = trim((string) ($input['service_description'] ?? ''));

        if ($description === '') {
            $description = $workOrder->service->code.' — '.$workOrder->service->name;
        }

        $lines = [[
            'sort_order' => 1,
            'line_type' => QuotationLineType::Service,
            'charge_side' => null,
            'description' => $description,
            'quantity' => $quantity,
            'government_fee' => $government,
            'consultancy_fee' => $consultancy,
            'discount_amount' => $discount,
            'fee_schedule_id' => $schedule->id,
            'schedule_government_fee' => Decimal::normalize((string) $schedule->government_fee),
            'schedule_consultancy_fee' => Decimal::normalize((string) $schedule->consultancy_fee),
            'amounts_adjusted' => $adjusted,
            'tax_code' => null,
            'tax_amount' => '0.00',
            'government_total' => $priced['government_total'],
            'consultancy_total' => $priced['consultancy_total'],
            'line_total' => $priced['line_total'],
        ]];

        $governmentTotal = $priced['government_total'];
        $consultancyTotal = $priced['consultancy_total'];
        $subtotal = $priced['gross'];
        $discountTotal = $discount;

        foreach ($this->customLines($input['custom_lines'] ?? []) as $custom) {
            $lines[] = $custom;
            $governmentTotal = Decimal::add($governmentTotal, $custom['government_total']);
            $consultancyTotal = Decimal::add($consultancyTotal, $custom['consultancy_total']);
            $subtotal = Decimal::add($subtotal, Decimal::add($custom['government_total'], $custom['consultancy_total']));
            $discountTotal = Decimal::add($discountTotal, $custom['discount_amount']);
        }

        foreach ($lines as $index => $line) {
            $lines[$index]['sort_order'] = $index + 1;
        }

        $grand = Decimal::sub($subtotal, $discountTotal);

        return new PreparedRevision(
            schedule: $schedule,
            amountsAdjusted: $adjusted,
            attributes: [
                'issue_date' => $issueDate->toDateString(),
                'valid_until' => $validUntil->toDateString(),
                'government_total' => $governmentTotal,
                'consultancy_total' => $consultancyTotal,
                'subtotal' => $subtotal,
                'discount_amount' => $discountTotal,
                'tax_amount' => '0.00',
                'grand_total' => $grand,
                'payment_terms' => $this->blank($input['payment_terms'] ?? null),
                'terms_and_conditions' => $this->blank($input['terms_and_conditions'] ?? null),
                'customer_notes' => $this->blank($input['customer_notes'] ?? null),
                'internal_notes' => $this->blank($input['internal_notes'] ?? null),
            ],
            lines: $lines,
        );
    }

    private function assertWorkOrder(WorkOrder $workOrder): void
    {
        if ($workOrder->status === WorkOrderStatus::Closed) {
            throw ValidationException::withMessages([
                'work_order_id' => __('ui.quotations.closed_work_order'),
            ]);
        }

        if ($workOrder->client === null || $workOrder->client->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'work_order_id' => __('ui.quotations.inactive_client'),
            ]);
        }

        if (
            $workOrder->industryUnit === null
            || $workOrder->industryUnit->status !== RecordStatus::Active
            || $workOrder->industryUnit->client_id !== $workOrder->client_id
        ) {
            throw ValidationException::withMessages([
                'work_order_id' => __('ui.quotations.inactive_unit'),
            ]);
        }

        if ($workOrder->service === null || $workOrder->service->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'work_order_id' => __('ui.quotations.inactive_service'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function assertNoTax(array $input): void
    {
        if (! $this->tax->pending()) {
            return;
        }

        $tax = $input['tax_amount'] ?? null;

        if ($tax === null || $tax === '') {
            return;
        }

        try {
            $amount = Decimal::normalize((string) $tax);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'tax_amount' => __('ui.quotations.tax_not_configured'),
            ]);
        }

        if (Decimal::comp($amount, '0.00') !== 0) {
            throw ValidationException::withMessages([
                'tax_amount' => __('ui.quotations.tax_not_configured'),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customLines(mixed $rows): array
    {
        if ($rows === null || $rows === []) {
            return [];
        }

        if (! is_array($rows)) {
            throw ValidationException::withMessages([
                'custom_lines' => __('ui.quotations.custom_invalid'),
            ]);
        }

        $lines = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row) || $this->customRowIsBlank($row)) {
                continue;
            }

            $key = 'custom_lines.'.$index;
            $description = trim((string) ($row['description'] ?? ''));

            if ($description === '') {
                throw ValidationException::withMessages([
                    $key.'.description' => __('ui.quotations.custom_description'),
                ]);
            }

            $side = ChargeSide::tryFrom((string) ($row['charge_side'] ?? ''));

            if ($side === null) {
                throw ValidationException::withMessages([
                    $key.'.charge_side' => __('ui.quotations.custom_side'),
                ]);
            }

            $quantity = $this->money((string) ($row['quantity'] ?? ''), $key.'.quantity', '99999999.99');
            $this->assertPositive($quantity, $key.'.quantity');
            $amount = $this->money((string) ($row['amount'] ?? ''), $key.'.amount');
            $this->assertPositive($amount, $key.'.amount');
            $discount = $this->money((string) ($row['discount_amount'] ?? '0'), $key.'.discount_amount');
            $government = $side === ChargeSide::Government ? $amount : '0.00';
            $consultancy = $side === ChargeSide::Consultancy ? $amount : '0.00';
            $priced = $this->price($key.'.discount_amount', $quantity, $government, $consultancy, $discount);

            $lines[] = [
                'sort_order' => count($lines) + 2,
                'line_type' => QuotationLineType::Custom,
                'charge_side' => $side,
                'description' => $description,
                'quantity' => $quantity,
                'government_fee' => $government,
                'consultancy_fee' => $consultancy,
                'discount_amount' => $discount,
                'fee_schedule_id' => null,
                'schedule_government_fee' => null,
                'schedule_consultancy_fee' => null,
                'amounts_adjusted' => false,
                'tax_code' => null,
                'tax_amount' => '0.00',
                'government_total' => $priced['government_total'],
                'consultancy_total' => $priced['consultancy_total'],
                'line_total' => $priced['line_total'],
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function customRowIsBlank(array $row): bool
    {
        $description = trim((string) ($row['description'] ?? ''));
        $side = trim((string) ($row['charge_side'] ?? ''));
        $quantity = trim((string) ($row['quantity'] ?? ''));
        $amount = trim((string) ($row['amount'] ?? ''));
        $discount = trim((string) ($row['discount_amount'] ?? ''));

        return $description === ''
            && $side === ''
            && ($quantity === '' || $quantity === '1' || $quantity === '1.00')
            && $amount === ''
            && ($discount === '' || $discount === '0' || $discount === '0.00');
    }

    /**
     * @return array{government_total: string, consultancy_total: string, gross: string, line_total: string}
     */
    private function price(string $discountKey, string $quantity, string $government, string $consultancy, string $discount): array
    {
        $governmentTotal = Decimal::mul($quantity, $government);
        $consultancyTotal = Decimal::mul($quantity, $consultancy);
        $gross = Decimal::add($governmentTotal, $consultancyTotal);

        if (Decimal::comp($discount, $gross) === 1) {
            throw ValidationException::withMessages([
                $discountKey => __('ui.quotations.discount_too_large'),
            ]);
        }

        return [
            'government_total' => $governmentTotal,
            'consultancy_total' => $consultancyTotal,
            'gross' => $gross,
            'line_total' => Decimal::sub($gross, $discount),
        ];
    }

    private function money(string $amount, string $key, string $maximum = '999999999999.99'): string
    {
        try {
            $normalized = Decimal::normalize($amount);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                $key => __('ui.quotations.amount_invalid'),
            ]);
        }

        if (Decimal::comp($normalized, $maximum) === 1) {
            throw ValidationException::withMessages([
                $key => __('ui.quotations.amount_invalid'),
            ]);
        }

        return $normalized;
    }

    private function assertPositive(string $amount, string $key): void
    {
        if (Decimal::comp($amount, '0.00') !== 1) {
            throw ValidationException::withMessages([
                $key => __('ui.quotations.amount_positive'),
            ]);
        }
    }

    private function blank(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
