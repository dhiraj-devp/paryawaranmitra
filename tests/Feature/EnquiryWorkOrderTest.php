<?php

namespace Tests\Feature;

use App\Actions\WorkOrders\ConvertEnquiry;
use App\Enums\EnquiryStatus;
use App\Enums\RecordStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\IndustryUnit;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class EnquiryWorkOrderTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-18 15:00:00');
        $this->seedAccess();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_staff_can_record_a_prospect_and_link_an_existing_client(): void
    {
        $manager = $this->makeUser('office-manager');
        [$client, $unit, $service] = $this->eligibleWork();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload())
            ->assertRedirect();

        $prospect = Enquiry::query()->where('contact_name', 'Ravi Patil')->first();
        $this->assertNotNull($prospect);
        $this->assertNull($prospect->client_id);
        $this->assertSame('ENQ-2026-0001', $prospect->number);
        $this->assertSame(EnquiryStatus::New, $prospect->status);
        $this->assertSame($manager->id, $prospect->created_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'enquiry.created',
            'subject_id' => $prospect->id,
        ]);

        $this->actingAs($manager, 'staff')
            ->put(route('staff.enquiries.update', $prospect), $this->enquiryPayload([
                'contact_name' => 'Ravi Patil',
                'client_id' => $client->id,
                'industry_unit_id' => $unit->id,
                'service_id' => $service->id,
                'assigned_to' => $manager->id,
            ]))
            ->assertRedirect(route('staff.enquiries.show', $prospect));

        $prospect->refresh();
        $this->assertSame($client->id, $prospect->client_id);
        $this->assertSame($unit->id, $prospect->industry_unit_id);
        $this->assertSame($service->id, $prospect->service_id);
        $this->assertSame($manager->id, $prospect->assigned_to);
        $this->assertSame(1, Client::query()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'enquiry.assignment_changed',
            'subject_id' => $prospect->id,
        ]);

        $this->actingAs($manager, 'staff')
            ->get(route('staff.enquiries.index', ['q' => 'Ravi']))
            ->assertOk()
            ->assertSee('ENQ-2026-0001')
            ->assertSee('Ravi Patil');
    }

    public function test_links_must_be_active_owned_and_eligible(): void
    {
        $manager = $this->makeUser('office-manager');
        [$client, $unit, $service] = $this->eligibleWork();
        $other = IndustryUnit::factory()->create(['industry_category' => 'chemical']);
        $inactiveClient = Client::factory()->inactive()->create();
        $inactiveUnit = IndustryUnit::factory()->for($client)->create([
            'status' => RecordStatus::Inactive,
            'industry_category' => 'chemical',
        ]);
        $inactiveService = Service::factory()->inactive()->create();
        $inactiveService->industries()->create(['industry_category' => 'chemical']);
        $textile = Service::factory()->create();
        $textile->industries()->create(['industry_category' => 'textile']);

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $inactiveClient->id,
            ]))
            ->assertSessionHasErrors('client_id');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $client->id,
                'industry_unit_id' => $inactiveUnit->id,
                'service_id' => $service->id,
            ]))
            ->assertSessionHasErrors('industry_unit_id');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $client->id,
                'industry_unit_id' => $other->id,
                'service_id' => $service->id,
            ]))
            ->assertSessionHasErrors('industry_unit_id');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'service_id' => $inactiveService->id,
            ]))
            ->assertSessionHasErrors('service_id');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $client->id,
                'industry_unit_id' => $unit->id,
                'service_id' => $textile->id,
            ]))
            ->assertSessionHasErrors('service_id');

        $this->assertSame(0, Enquiry::query()->count());
    }

    public function test_follow_ups_stay_in_history_and_numbers_do_not_repeat(): void
    {
        $manager = $this->makeUser('office-manager');

        $this->actingAs($manager, 'staff')->post(route('staff.enquiries.store'), $this->enquiryPayload())->assertRedirect();
        $this->actingAs($manager, 'staff')->post(route('staff.enquiries.store'), $this->enquiryPayload([
            'contact_name' => 'Second caller',
        ]))->assertRedirect();

        $numbers = Enquiry::query()->orderBy('id')->pluck('number')->all();
        $this->assertSame(['ENQ-2026-0001', 'ENQ-2026-0002'], $numbers);

        $enquiry = Enquiry::query()->where('number', 'ENQ-2026-0001')->firstOrFail();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.follow-ups.store', $enquiry), [
                'followed_up_at' => '2026-11-18T10:00',
                'method' => 'phone',
                'notes' => 'Asked for the consent papers.',
                'outcome' => 'Will call again',
                'next_follow_up_on' => '2026-11-20',
            ])
            ->assertRedirect(route('staff.enquiries.show', $enquiry));

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.follow-ups.store', $enquiry), [
                'followed_up_at' => '2026-11-18T14:30',
                'method' => 'whatsapp',
                'notes' => 'They will visit on Friday.',
                'outcome' => 'Visit agreed',
                'next_follow_up_on' => '2026-11-21',
            ])
            ->assertRedirect();

        $notes = EnquiryFollowUp::query()->orderBy('id')->pluck('notes')->all();
        $this->assertSame([
            'Asked for the consent papers.',
            'They will visit on Friday.',
        ], $notes);
        $this->assertSame('2026-11-21', $enquiry->fresh()->next_follow_up_on?->toDateString());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'enquiry.follow_up_created',
            'subject_id' => $enquiry->id,
        ]);
    }

    public function test_conversion_creates_one_work_order_and_rejects_a_second(): void
    {
        $manager = $this->makeUser('office-manager');
        [$client, $unit, $service] = $this->eligibleWork();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $client->id,
                'industry_unit_id' => $unit->id,
                'service_id' => $service->id,
            ]))
            ->assertRedirect();

        $enquiry = Enquiry::query()->firstOrFail();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.status', $enquiry), [
                'status' => 'lost',
                'confirm_status' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($manager, 'staff')
            ->from(route('staff.enquiries.show', $enquiry))
            ->post(route('staff.enquiries.convert', $enquiry), $this->conversionPayload($client->id, $unit->id, $service->id, [
                'client_id' => $client->id + 999,
            ]))
            ->assertSessionHasErrors('client_id');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.convert', $enquiry), $this->conversionPayload($client->id, $unit->id, $service->id))
            ->assertRedirect();

        $enquiry->refresh();
        $workOrder = WorkOrder::query()->first();
        $this->assertNotNull($workOrder);
        $this->assertSame('WO-2026-0001', $workOrder->number);
        $this->assertSame($enquiry->id, $workOrder->enquiry_id);
        $this->assertSame($client->id, $workOrder->client_id);
        $this->assertSame($unit->id, $workOrder->industry_unit_id);
        $this->assertSame($service->id, $workOrder->service_id);
        $this->assertSame(WorkOrderStatus::Active, $workOrder->status);
        $this->assertSame(EnquiryStatus::Converted, $enquiry->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'enquiry.converted',
            'subject_id' => $enquiry->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'work_order.created',
            'subject_id' => $workOrder->id,
        ]);

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.convert', $enquiry), $this->conversionPayload($client->id, $unit->id, $service->id))
            ->assertForbidden();

        try {
            app(ConvertEnquiry::class)->execute($enquiry->fresh(), $this->conversionPayload($client->id, $unit->id, $service->id), $manager);
            $this->fail('A second conversion should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('enquiry', $exception->errors());
        }

        $this->assertSame(1, WorkOrder::query()->count());

        $this->actingAs($manager, 'staff')
            ->from(route('staff.enquiries.show', $enquiry))
            ->post(route('staff.enquiries.status', $enquiry), [
                'status' => 'contacted',
                'confirm_status' => '1',
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($manager, 'staff')
            ->put(route('staff.work-orders.update', $workOrder), [
                'work_description' => 'Revised scope after the site call.',
                'start_date' => '2026-11-18',
                'target_completion_date' => '2026-12-15',
                'internal_notes' => 'Office note',
            ])
            ->assertRedirect(route('staff.work-orders.show', $workOrder));

        $this->assertSame('Revised scope after the site call.', $workOrder->fresh()->work_description);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'work_order.updated',
            'subject_id' => $workOrder->id,
        ]);

        $this->actingAs($manager, 'staff')
            ->get(route('staff.work-orders.index'))
            ->assertOk()
            ->assertSee('WO-2026-0001')
            ->assertSee($client->legal_name);

        try {
            WorkOrder::factory()->create([
                'enquiry_id' => $enquiry->id,
                'client_id' => $client->id,
                'industry_unit_id' => $unit->id,
                'service_id' => $service->id,
            ]);
            $this->fail('A second work order for the same enquiry should be rejected.');
        } catch (UniqueConstraintViolationException) {
            $this->assertSame(1, WorkOrder::query()->where('enquiry_id', $enquiry->id)->count());
        }
    }

    public function test_a_failed_audit_write_rolls_back_the_conversion(): void
    {
        $manager = $this->makeUser('office-manager');
        [$client, $unit, $service] = $this->eligibleWork();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $client->id,
                'industry_unit_id' => $unit->id,
                'service_id' => $service->id,
            ]))
            ->assertRedirect();

        $enquiry = Enquiry::query()->firstOrFail();

        $this->mock(AuditLogger::class, function ($mock) {
            $mock->shouldReceive('log')->andThrow(new RuntimeException('audit failed'));
        });

        $this->actingAs($manager, 'staff')
            ->post(route('staff.enquiries.convert', $enquiry), $this->conversionPayload($client->id, $unit->id, $service->id));

        $this->assertSame(0, WorkOrder::query()->count());
        $this->assertSame(EnquiryStatus::New, $enquiry->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'enquiry.converted']);
    }

    public function test_permissions_and_portals_stay_separate(): void
    {
        $executive = $this->makeUser('office-executive');
        $accounts = $this->makeUser('accounts');
        $consultant = $this->makeUser('consultant');
        $admin = $this->makeUser('super-admin');
        [$client, $unit, $service] = $this->eligibleWork();

        $this->actingAs($executive, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload([
                'client_id' => $client->id,
                'industry_unit_id' => $unit->id,
                'service_id' => $service->id,
            ]))
            ->assertRedirect();

        $enquiry = Enquiry::query()->firstOrFail();

        $this->actingAs($executive, 'staff')
            ->post(route('staff.enquiries.convert', $enquiry), $this->conversionPayload($client->id, $unit->id, $service->id))
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->get(route('staff.work-orders.index'))
            ->assertOk();

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.enquiries.index'))
            ->assertOk();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.enquiries.store'), $this->enquiryPayload(['contact_name' => 'Accounts caller']))
            ->assertForbidden();

        $this->actingAs($consultant, 'staff')
            ->post(route('staff.enquiries.convert', $enquiry), $this->conversionPayload($client->id, $unit->id, $service->id))
            ->assertRedirect();

        $workOrder = WorkOrder::query()->firstOrFail();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.work-orders.status', $workOrder), [
                'status' => 'closed',
                'confirm_status' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->get(route('admin.enquiries.index'))
            ->assertRedirect(route('admin.login'));

        $this->app['auth']->forgetGuards();

        $this->actingAs($admin, 'admin')
            ->get(route('staff.work-orders.show', $workOrder))
            ->assertRedirect(route('staff.login'));
    }

    public function test_enquiries_follow_ups_and_work_orders_cannot_be_deleted(): void
    {
        $enquiry = Enquiry::factory()->create();
        $followUp = new EnquiryFollowUp([
            'followed_up_at' => '2026-11-18 10:00:00',
            'method' => 'phone',
            'notes' => 'Kept note',
            'outcome' => 'Noted',
        ]);
        $followUp->enquiry_id = $enquiry->id;
        $followUp->created_at = now();
        $followUp->save();
        $workOrder = WorkOrder::factory()->create();

        foreach ([$enquiry, $followUp, $workOrder] as $record) {
            try {
                $record->delete();
                $this->fail($record::class.' delete should be refused.');
            } catch (RuntimeException) {
                $this->assertModelExists($record);
            }
        }
    }

    public function test_an_unlinked_enquiry_can_be_deleted_and_a_linked_one_cannot(): void
    {
        $manager = $this->makeUser('office-manager');
        $free = Enquiry::factory()->create();
        $linked = Enquiry::factory()->create();
        $note = new EnquiryFollowUp([
            'followed_up_at' => '2026-11-18 10:00:00',
            'method' => 'phone',
            'notes' => 'Kept note',
            'outcome' => 'Noted',
        ]);
        $note->enquiry_id = $linked->id;
        $note->created_at = now();
        $note->save();

        $response = $this->actingAs($manager, 'staff')
            ->get(route('staff.enquiries.index'))
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('action="'.route('staff.enquiries.destroy', $free).'"', $html);
        $this->assertStringNotContainsString('action="'.route('staff.enquiries.destroy', $linked).'"', $html);

        $this->actingAs($manager, 'staff')
            ->delete(route('staff.enquiries.destroy', $linked), ['confirm_delete' => 1])
            ->assertForbidden();

        $this->actingAs($manager, 'staff')
            ->delete(route('staff.enquiries.destroy-many'), [
                'confirm_delete' => 1,
                'ids' => [$free->id],
            ])
            ->assertRedirect(route('staff.enquiries.index'));

        $this->assertModelMissing($free);
        $this->assertModelExists($linked);
    }

    public function test_the_enquiry_directory_is_available_in_marathi(): void
    {
        $accounts = $this->makeUser('accounts', ['locale' => 'mr']);

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.enquiries.index'))
            ->assertOk()
            ->assertSee('चौकशी')
            ->assertDontSee('नवी चौकशी');
    }

    /**
     * @return array{0: Client, 1: IndustryUnit, 2: Service}
     */
    private function eligibleWork(): array
    {
        $client = Client::factory()->create();
        $unit = IndustryUnit::factory()->for($client)->create(['industry_category' => 'chemical']);
        $service = Service::factory()->create();
        $service->industries()->create(['industry_category' => 'chemical']);

        return [$client, $unit, $service];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function enquiryPayload(array $overrides = []): array
    {
        return array_merge([
            'enquiry_date' => '2026-11-18',
            'client_id' => '',
            'industry_unit_id' => '',
            'contact_name' => 'Ravi Patil',
            'mobile' => '9876543210',
            'email' => 'ravi@example.test',
            'source' => 'phone',
            'service_id' => '',
            'requirement' => 'Needs consent to establish for a chemical unit.',
            'internal_notes' => '',
            'assigned_to' => '',
            'next_follow_up_on' => '2026-11-25',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function conversionPayload(int $clientId, int $unitId, int $serviceId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'industry_unit_id' => $unitId,
            'service_id' => $serviceId,
            'assigned_to' => '',
            'work_description' => 'Prepare the consent to establish for the chemical unit.',
            'start_date' => '2026-11-18',
            'target_completion_date' => '2026-12-31',
            'internal_notes' => '',
            'confirm_conversion' => '1',
        ], $overrides);
    }
}
