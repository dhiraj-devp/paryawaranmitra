<?php

namespace Tests\Feature;

use App\Enums\QuotationStatus;
use App\Enums\RecordStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\FeeSchedule;
use App\Models\IndustryUnit;
use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use App\Services\Quotations\TaxSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_accounts_can_quote_a_work_order_without_changing_the_fee_schedule(): void
    {
        $accounts = $this->makeUser('accounts');
        [$workOrder, $schedule, $client] = $this->quotedWork();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder, [
                'government_fee' => '14000.00',
                'client_id' => $client->id + 99,
                'payment_terms' => '50 percent before submission.',
                'internal_notes' => 'Office only',
            ]))
            ->assertSessionHasErrors('client_id');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder, [
                'government_fee' => '14000.00',
                'payment_terms' => '50 percent before submission.',
                'internal_notes' => 'Office only',
                'custom_lines' => [
                    ['description' => 'Site visit', 'charge_side' => 'consultancy', 'quantity' => '1.50', 'amount' => '10.01', 'discount_amount' => '0.00'],
                    ['description' => 'Gazette notice', 'charge_side' => 'government', 'quantity' => '2', 'amount' => '100.00', 'discount_amount' => '25.00'],
                ],
            ]))
            ->assertRedirect();

        $quotation = Quotation::query()->with(['workOrder', 'currentRevision.lines'])->first();
        $this->assertNotNull($quotation);
        $this->assertSame('QT-2026-0001', $quotation->quotation_number);
        $this->assertSame($workOrder->id, $quotation->work_order_id);
        $this->assertSame($client->id, $quotation->workOrder->client_id);
        $this->assertSame(QuotationStatus::Draft, $quotation->status);
        $this->assertSame(1, $quotation->currentRevision->revision_number);
        $this->assertSame('14200.00', $quotation->currentRevision->government_total);
        $this->assertSame('25015.52', $quotation->currentRevision->consultancy_total);
        $this->assertSame('39215.52', $quotation->currentRevision->subtotal);
        $this->assertSame('25.00', $quotation->currentRevision->discount_amount);
        $this->assertSame('0.00', $quotation->currentRevision->tax_amount);
        $this->assertSame('39190.52', $quotation->currentRevision->grand_total);

        $serviceLine = $quotation->currentRevision->lines->first();
        $this->assertSame('14000.00', $serviceLine->government_fee);
        $this->assertSame('25000.50', $serviceLine->consultancy_fee);
        $this->assertTrue($serviceLine->amounts_adjusted);
        $this->assertSame($schedule->id, $serviceLine->fee_schedule_id);
        $custom = $quotation->currentRevision->lines->firstWhere('description', 'Site visit');
        $this->assertSame('0.00', $custom->government_fee);
        $this->assertSame('15.02', $custom->consultancy_total);

        $schedule->refresh();
        $this->assertSame('15000.00', $schedule->government_fee);
        $this->assertSame('25000.50', $schedule->consultancy_fee);

        $created = AuditLog::query()->where('action', 'quotation.created')->first();
        $this->assertTrue($created->new_values['amounts_adjusted']);
        $this->assertSame('15000.00', $created->new_values['schedule_government_fee']);
        $this->assertSame('14000.00', $created->new_values['government_fee']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'quotation.revision_created',
            'subject_id' => $quotation->id,
        ]);

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('QT-2026-0001')
            ->assertSee('Office only')
            ->assertSee(__('ui.quotations.tax_pending'));

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder, [
                'issue_date' => '2027-02-01',
                'valid_until' => '2027-03-01',
            ]))
            ->assertRedirect();

        $this->assertSame('QT-2027-0001', Quotation::query()->where('quotation_number', '!=', 'QT-2026-0001')->value('quotation_number'));
    }

    public function test_a_quotation_requires_an_applicable_fee_and_an_open_active_work_order(): void
    {
        $accounts = $this->makeUser('accounts');
        [$workOrder] = $this->quotedWork();
        [$bare] = $this->workOrder();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($bare))
            ->assertSessionHasErrors('issue_date');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder, ['tax_amount' => '18.00']))
            ->assertSessionHasErrors('tax_amount');

        $workOrder->status = WorkOrderStatus::Closed;
        $workOrder->save();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertSessionHasErrors('work_order_id');

        $workOrder->status = WorkOrderStatus::Active;
        $workOrder->save();
        $workOrder->client->status = RecordStatus::Inactive;
        $workOrder->client->save();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertSessionHasErrors('work_order_id');

        $workOrder->client->status = RecordStatus::Active;
        $workOrder->client->save();
        $workOrder->service->status = RecordStatus::Inactive;
        $workOrder->service->save();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertSessionHasErrors('work_order_id');

        $workOrder->service->status = RecordStatus::Active;
        $workOrder->service->save();
        $other = Client::factory()->create();
        $workOrder->industryUnit->client_id = $other->id;
        $workOrder->industryUnit->save();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertSessionHasErrors('work_order_id');

        $this->assertSame(0, Quotation::query()->count());
        $this->assertTrue(app(TaxSettings::class)->pending());
    }

    public function test_revisions_stay_immutable_and_an_accepted_revision_is_preserved(): void
    {
        $accounts = $this->makeUser('accounts');
        [$workOrder] = $this->quotedWork();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertRedirect();

        $quotation = Quotation::query()->firstOrFail();
        $first = $quotation->currentRevision;
        $firstTotal = $first->grand_total;

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), ['status' => 'accepted', 'confirm_status' => '1'])
            ->assertSessionHasErrors('status');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), ['status' => 'sent', 'confirm_status' => '1'])
            ->assertRedirect();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), ['status' => 'accepted', 'confirm_status' => '1'])
            ->assertRedirect();

        $quotation->refresh();
        $this->assertSame($first->id, $quotation->accepted_revision_id);
        $this->assertSame(QuotationStatus::Accepted, $quotation->status);

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.revisions.store', $quotation), $this->revisionPayload($workOrder, [
                'consultancy_fee' => '26000.50',
            ]))
            ->assertSessionHasErrors('confirm_revision');

        $this->assertSame(1, QuotationRevision::query()->count());

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.revisions.store', $quotation), $this->revisionPayload($workOrder, [
                'consultancy_fee' => '26000.50',
                'confirm_revision' => '1',
                'revision_reason' => 'Customer asked for a revised consultancy charge.',
            ]))
            ->assertRedirect();

        $quotation->refresh();
        $first->refresh();
        $this->assertSame(QuotationStatus::Draft, $quotation->status);
        $this->assertSame($first->id, $quotation->accepted_revision_id);
        $this->assertSame(2, $quotation->currentRevision->revision_number);
        $this->assertSame($firstTotal, $first->grand_total);
        $this->assertSame('41000.50', $quotation->currentRevision->grand_total);

        try {
            $first->grand_total = '1.00';
            $first->save();
            $this->fail('A revision should not change.');
        } catch (RuntimeException) {
            $this->assertSame($firstTotal, $first->fresh()->grand_total);
        }

        $duplicate = new QuotationRevision;
        $duplicate->quotation_id = $quotation->id;
        $duplicate->revision_number = 1;
        $duplicate->issue_date = '2026-11-18';
        $duplicate->valid_until = '2026-12-18';
        $duplicate->government_total = '1.00';
        $duplicate->consultancy_total = '1.00';
        $duplicate->subtotal = '2.00';
        $duplicate->discount_amount = '0.00';
        $duplicate->tax_amount = '0.00';
        $duplicate->grand_total = '2.00';

        try {
            $duplicate->save();
            $this->fail('Revision numbers must be unique for one quotation.');
        } catch (UniqueConstraintViolationException) {
            $this->assertSame(2, QuotationRevision::query()->where('quotation_id', $quotation->id)->count());
        }

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), ['status' => 'accepted', 'confirm_status' => '1'])
            ->assertSessionHasErrors('status');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), ['status' => 'sent', 'confirm_status' => '1'])
            ->assertRedirect();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), [
                'status' => 'accepted',
                'confirm_status' => '1',
            ])
            ->assertRedirect();

        $quotation->refresh();
        $this->assertSame($quotation->current_revision_id, $quotation->accepted_revision_id);
        $this->assertSame($firstTotal, $first->fresh()->grand_total);
        $this->assertNotSame($first->id, $quotation->accepted_revision_id);

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), [
                'status' => 'rejected',
                'reason' => 'Not this revision.',
                'confirm_status' => '1',
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), [
                'status' => 'cancelled',
                'confirm_status' => '1',
            ])
            ->assertSessionHasErrors('reason');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.status', $quotation), [
                'status' => 'cancelled',
                'reason' => 'Customer withdrew.',
                'confirm_status' => '1',
            ])
            ->assertRedirect();

        $quotation->refresh();
        $this->assertSame(QuotationStatus::Cancelled, $quotation->status);
        $this->assertSame($quotation->current_revision_id, $quotation->accepted_revision_id);

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.revisions.store', $quotation), $this->revisionPayload($workOrder))
            ->assertSessionHasErrors('revision');

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.quotations.compare', $quotation))
            ->assertOk()
            ->assertSee('R1')
            ->assertSee('R2');
    }

    public function test_pdf_downloads_are_authorized_and_older_revisions_remain_available(): void
    {
        Storage::fake('quotations');

        $accounts = $this->makeUser('accounts');
        $manager = $this->makeUser('office-manager');
        $consultant = $this->makeUser('consultant');
        [$workOrder] = $this->quotedWork();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder, [
                'internal_notes' => 'Office only',
                'customer_notes' => 'Please confirm by email.',
            ]))
            ->assertRedirect();

        $quotation = Quotation::query()->with('currentRevision.lines')->firstOrFail();
        $first = $quotation->currentRevision;

        $html = view('quotations.pdf', [
            'quotation' => $quotation->load(['workOrder.client', 'workOrder.industryUnit', 'workOrder.service']),
            'revision' => $first,
            'company' => config('company'),
            'taxPending' => true,
        ])->render();

        $this->assertStringContainsString('QT-2026-0001', $html);
        $this->assertStringContainsString('Please confirm by email.', $html);
        $this->assertStringNotContainsString('Office only', $html);
        $this->assertStringContainsString(__('ui.quotations.tax_pending'), $html);
        $this->assertStringContainsString(__('ui.quotations.gstin_missing'), $html);

        $this->actingAs($consultant, 'staff')
            ->get(route('staff.quotations.revisions.pdf', [$quotation, $first]))
            ->assertForbidden();

        $this->assertSame([], Storage::disk('quotations')->allFiles());

        $response = $this->actingAs($manager, 'staff')
            ->get(route('staff.quotations.revisions.pdf', [$quotation, $first]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
        $this->assertFalse(config('filesystems.disks.quotations.serve'));
        $this->assertSame('private', config('filesystems.disks.quotations.visibility'));
        Storage::disk('quotations')->assertExists($quotation->id.'/r1.pdf');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'quotation.pdf_generated',
            'subject_id' => $quotation->id,
        ]);

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.revisions.store', $quotation), $this->revisionPayload($workOrder, [
                'consultancy_fee' => '26000.50',
            ]))
            ->assertRedirect();

        $second = $quotation->fresh()->currentRevision;
        $this->actingAs($accounts, 'staff')
            ->get(route('staff.quotations.revisions.pdf', [$quotation, $first]))
            ->assertOk();
        $this->actingAs($accounts, 'staff')
            ->get(route('staff.quotations.revisions.pdf', [$quotation, $second]))
            ->assertOk();

        Storage::disk('quotations')->assertExists($quotation->id.'/r1.pdf');
        Storage::disk('quotations')->assertExists($quotation->id.'/r2.pdf');
    }

    public function test_permissions_portal_isolation_and_audit_rollback(): void
    {
        $accounts = $this->makeUser('accounts');
        $manager = $this->makeUser('office-manager');
        $consultant = $this->makeUser('consultant');
        $executive = $this->makeUser('office-executive');
        $admin = $this->makeUser('super-admin');
        [$workOrder, , $client] = $this->quotedWork();

        $this->actingAs($manager, 'staff')
            ->get(route('staff.quotations.index'))
            ->assertOk()
            ->assertDontSee(__('ui.quotations.create'));

        $this->actingAs($manager, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertForbidden();

        $this->actingAs($consultant, 'staff')
            ->get(route('staff.quotations.index'))
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->get(route('staff.quotations.index'))
            ->assertForbidden();

        $this->actingAs($accounts, 'staff')
            ->get(route('admin.quotations.index'))
            ->assertRedirect(route('admin.login'));

        $this->mock(AuditLogger::class, function ($mock): void {
            $mock->shouldReceive('log')->andThrow(new RuntimeException('audit failed'));
        });

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertServerError();

        $this->assertSame(0, Quotation::query()->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'quotation.created')->count());

        $this->app['auth']->forgetGuards();

        $this->actingAs($admin, 'admin')
            ->get(route('staff.quotations.index'))
            ->assertRedirect(route('staff.login'));

        $this->assertSame($client->id, $workOrder->client_id);
    }

    public function test_quotations_and_lines_cannot_be_deleted_and_the_list_is_available_in_marathi(): void
    {
        $accounts = $this->makeUser('accounts');
        $manager = $this->makeUser('office-manager', ['locale' => 'mr']);
        [$workOrder] = $this->quotedWork();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.quotations.store'), $this->payload($workOrder))
            ->assertRedirect();

        $quotation = Quotation::query()->with('currentRevision.lines')->firstOrFail();
        $line = $quotation->currentRevision->lines->first();

        foreach ([$quotation, $quotation->currentRevision, $line] as $record) {
            try {
                $record->delete();
                $this->fail($record::class.' delete should be refused.');
            } catch (RuntimeException) {
                $this->assertModelExists($record);
            }
        }

        try {
            $line->description = 'Changed';
            $line->save();
            $this->fail('A line should not change.');
        } catch (RuntimeException) {
            $this->assertNotSame('Changed', $line->fresh()->description);
        }

        $this->actingAs($manager, 'staff')
            ->get(route('staff.quotations.index'))
            ->assertOk()
            ->assertSee('कोटेशन')
            ->assertDontSee('नवीन कोटेशन');
    }

    /**
     * @return array{0: WorkOrder, 1: FeeSchedule, 2: Client}
     */
    private function quotedWork(): array
    {
        [$workOrder, $client, $service] = $this->workOrder();
        $schedule = FeeSchedule::factory()->create([
            'service_id' => $service->id,
            'effective_from' => '2026-01-01',
            'government_fee' => '15000.00',
            'consultancy_fee' => '25000.50',
        ]);

        return [$workOrder, $schedule, $client];
    }

    /**
     * @return array{0: WorkOrder, 1: Client, 2: Service}
     */
    private function workOrder(): array
    {
        $client = Client::factory()->create();
        $unit = IndustryUnit::factory()->for($client)->create(['industry_category' => 'chemical']);
        $service = Service::factory()->create();
        $service->industries()->create(['industry_category' => 'chemical']);
        $enquiry = Enquiry::factory()->create([
            'client_id' => $client->id,
            'industry_unit_id' => $unit->id,
            'service_id' => $service->id,
        ]);
        $workOrder = WorkOrder::factory()->create([
            'enquiry_id' => $enquiry->id,
            'client_id' => $client->id,
            'industry_unit_id' => $unit->id,
            'service_id' => $service->id,
            'status' => WorkOrderStatus::Active,
        ]);

        return [$workOrder, $client, $service];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(WorkOrder $workOrder, array $overrides = []): array
    {
        return array_merge([
            'work_order_id' => $workOrder->id,
            'issue_date' => '2026-11-18',
            'valid_until' => '2026-12-18',
            'government_fee' => '15000.00',
            'consultancy_fee' => '25000.50',
            'service_quantity' => '1',
            'service_discount' => '0.00',
            'payment_terms' => 'Payable before submission.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function revisionPayload(WorkOrder $workOrder, array $overrides = []): array
    {
        $payload = $this->payload($workOrder, $overrides);
        unset($payload['work_order_id']);

        return $payload;
    }
}
