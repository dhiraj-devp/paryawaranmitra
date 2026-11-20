<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\AuditLog;
use App\Models\FeeSchedule;
use App\Models\Service;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\ServiceEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class ServiceFeeTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_manager_can_create_and_edit_a_service_with_eligible_industries(): void
    {
        $manager = $this->makeUser('office-manager');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.services.store'), $this->servicePayload())
            ->assertRedirect();

        $service = Service::query()->where('code', 'CTE')->first();
        $this->assertNotNull($service);
        $this->assertSame(RecordStatus::Active, $service->status);
        $this->assertSame($manager->id, $service->created_by);
        $this->assertEqualsCanonicalizing(['chemical', 'pharmaceutical'], $service->industries()->pluck('industry_category')->all());
        $this->assertTrue(app(ServiceEligibility::class)->allows($service, 'chemical'));
        $this->assertFalse(app(ServiceEligibility::class)->allows($service, 'textile'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service.created',
            'subject_id' => $service->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service.eligibility_changed',
            'subject_id' => $service->id,
        ]);

        $this->actingAs($manager, 'staff')
            ->put(route('staff.services.update', $service), $this->servicePayload([
                'name' => 'Consent to Establish — revised',
                'industries' => ['chemical'],
            ]))
            ->assertRedirect(route('staff.services.show', $service));

        $service->refresh();
        $this->assertSame('Consent to Establish — revised', $service->name);
        $this->assertSame(['chemical'], $service->industries()->orderBy('industry_category')->pluck('industry_category')->all());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service.updated',
            'subject_id' => $service->id,
        ]);
    }

    public function test_service_codes_categories_and_inactive_services_are_guarded(): void
    {
        $manager = $this->makeUser('office-manager');
        $this->actingAs($manager, 'staff')->post(route('staff.services.store'), $this->servicePayload())->assertRedirect();

        $this->actingAs($manager, 'staff')
            ->from(route('staff.services.create'))
            ->post(route('staff.services.store'), $this->servicePayload(['name' => 'Another CTE']))
            ->assertRedirect(route('staff.services.create'))
            ->assertSessionHasErrors('code');

        $this->actingAs($manager, 'staff')
            ->from(route('staff.services.create'))
            ->post(route('staff.services.store'), $this->servicePayload([
                'code' => 'CTO',
                'industries' => ['not-a-category'],
            ]))
            ->assertSessionHasErrors('industries.0');

        $service = Service::query()->where('code', 'CTE')->firstOrFail();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.services.status', $service), [
                'status' => 'inactive',
                'confirm_status' => '1',
            ])
            ->assertRedirect();

        $service->refresh();
        $this->assertSame(RecordStatus::Inactive, $service->status);
        $this->assertFalse(app(ServiceEligibility::class)->allows($service, 'chemical'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service.status_changed',
            'subject_id' => $service->id,
        ]);

        $this->actingAs($manager, 'staff')
            ->from(route('staff.fees.create'))
            ->post(route('staff.fees.store'), $this->feePayload($service))
            ->assertSessionHasErrors('service_id');
        $this->assertSame(0, FeeSchedule::query()->count());
    }

    public function test_view_permission_cannot_change_catalogues_and_portals_stay_separate(): void
    {
        $consultant = $this->makeUser('consultant');
        $accounts = $this->makeUser('accounts');
        $executive = $this->makeUser('office-executive');
        $manager = $this->makeUser('office-manager');
        $admin = $this->makeUser('super-admin');
        $service = Service::factory()->create(['name' => 'Visible Service']);

        $this->actingAs($consultant, 'staff')
            ->get(route('staff.services.index'))
            ->assertOk()
            ->assertSee('Visible Service');

        $this->actingAs($consultant, 'staff')
            ->post(route('staff.services.store'), $this->servicePayload(['code' => 'HW']))
            ->assertForbidden();

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.services.index'))
            ->assertForbidden();

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.fees.index'))
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->get(route('staff.fees.index'))
            ->assertOk();

        $this->actingAs($executive, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service))
            ->assertForbidden();

        $this->actingAs($manager, 'staff')
            ->get(route('admin.services.show', $service))
            ->assertRedirect(route('admin.login'));

        $this->app['auth']->forgetGuards();

        $this->actingAs($admin, 'admin')
            ->get(route('staff.fees.index'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_fee_schedules_keep_history_and_reject_overlaps(): void
    {
        Carbon::setTestNow('2026-05-15');
        $manager = $this->makeUser('office-manager');
        $service = Service::factory()->create(['code' => 'CTO', 'name' => 'Consent to Operate']);

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-01-01',
                'effective_until' => '2026-03-31',
                'government_fee' => '100.00',
                'consultancy_fee' => '200.00',
            ]))
            ->assertRedirect();

        $first = FeeSchedule::query()->firstOrFail();
        $this->assertSame('100.00', $first->government_fee);
        $this->assertSame('200.00', $first->consultancy_fee);

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-04-01',
                'government_fee' => '150.50',
                'consultancy_fee' => '300',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $second = FeeSchedule::query()->whereDate('effective_from', '2026-04-01')->firstOrFail();
        $this->assertSame('150.50', $second->government_fee);
        $this->assertSame('300.00', $second->consultancy_fee);
        $this->assertNull($second->effective_until);
        $first->refresh();
        $this->assertSame('100.00', $first->government_fee);
        $this->assertSame('200.00', $first->consultancy_fee);

        $this->actingAs($manager, 'staff')
            ->from(route('staff.fees.create'))
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-02-01',
                'effective_until' => '2026-02-28',
                'government_fee' => '1.00',
                'consultancy_fee' => '1.00',
            ]))
            ->assertSessionHasErrors('effective_from');

        $this->actingAs($manager, 'staff')
            ->from(route('staff.fees.create'))
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-06-01',
                'government_fee' => '9.00',
                'consultancy_fee' => '9.00',
            ]))
            ->assertSessionHasErrors('effective_from');

        $this->actingAs($manager, 'staff')
            ->put(route('staff.fees.update', $second), [
                'effective_from' => '2026-04-01',
                'effective_until' => '2026-05-31',
                'government_fee' => '150.50',
                'consultancy_fee' => '300.00',
            ])
            ->assertRedirect();

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-06-01',
                'government_fee' => '175.25',
                'consultancy_fee' => '400.00',
            ]))
            ->assertRedirect();

        $this->assertSame(3, FeeSchedule::query()->count());
        $first->refresh();
        $this->assertSame('100.00', $first->government_fee);
        $this->assertSame('200.00', $first->consultancy_fee);

        $this->actingAs($manager, 'staff')
            ->put(route('staff.fees.update', $first), [
                'service_id' => Service::factory()->create()->id,
                'effective_from' => '2026-01-01',
                'effective_until' => '2026-03-31',
                'government_fee' => '110.10',
                'consultancy_fee' => '200.00',
            ])
            ->assertRedirect();

        $first->refresh();
        $this->assertSame($service->id, $first->service_id);
        $this->assertSame('110.10', $first->government_fee);
        $this->assertSame('200.00', $first->consultancy_fee);
        $updated = AuditLog::query()->where('action', 'fee_schedule.updated')->latest('id')->first();
        $this->assertNotNull($updated);
        $this->assertSame('100.00', $updated->old_values['government_fee']);
        $this->assertSame('110.10', $updated->new_values['government_fee']);

        $this->actingAs($manager, 'staff')
            ->get(route('staff.fees.index', [
                'lookup_service' => $service->id,
                'lookup_on' => '2026-02-15',
            ]))
            ->assertOk()
            ->assertSee('₹110.10')
            ->assertSee('₹200.00');

        $this->actingAs($manager, 'staff')
            ->get(route('staff.fees.index', [
                'lookup_service' => $service->id,
                'lookup_on' => '2025-12-01',
            ]))
            ->assertOk()
            ->assertSee(__('ui.fees.none_configured'));

        $this->actingAs($manager, 'staff')
            ->get(route('staff.fees.index', ['coverage' => 'upcoming']))
            ->assertOk()
            ->assertSee('175.25')
            ->assertDontSee('110.10');

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.status', $second), [
                'status' => 'inactive',
                'confirm_status' => '1',
            ])
            ->assertRedirect();

        $second->refresh();
        $this->assertSame(RecordStatus::Inactive, $second->status);

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-04-15',
                'effective_until' => '2026-04-20',
                'government_fee' => '1.00',
                'consultancy_fee' => '2.00',
            ]))
            ->assertRedirect();

        $this->actingAs($manager, 'staff')
            ->from(route('staff.fees.show', $second))
            ->post(route('staff.fees.status', $second), [
                'status' => 'active',
                'confirm_status' => '1',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(RecordStatus::Inactive, $second->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'fee_schedule.status_changed',
            'subject_id' => $second->id,
        ]);
    }

    public function test_money_rejects_extra_precision_and_a_failed_audit_rolls_back(): void
    {
        $manager = $this->makeUser('office-manager');
        $service = Service::factory()->create();

        $this->actingAs($manager, 'staff')
            ->from(route('staff.fees.create'))
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'government_fee' => '10.125',
                'consultancy_fee' => '1e2',
            ]))
            ->assertSessionHasErrors(['government_fee', 'consultancy_fee']);

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'government_fee' => '0.10',
                'consultancy_fee' => '999999999999.99',
                'effective_until' => '2026-01-31',
            ]))
            ->assertRedirect();

        $schedule = FeeSchedule::query()->firstOrFail();
        $this->assertSame('0.10', $schedule->government_fee);
        $this->assertSame('999999999999.99', $schedule->consultancy_fee);
        $this->assertIsString($schedule->government_fee);

        $this->mock(AuditLogger::class, function ($mock): void {
            $mock->shouldReceive('log')->andThrow(new RuntimeException('audit failed'));
        });

        $this->actingAs($manager, 'staff')
            ->post(route('staff.fees.store'), $this->feePayload($service, [
                'effective_from' => '2026-02-01',
                'government_fee' => '5.00',
                'consultancy_fee' => '6.00',
            ]));

        $this->assertSame(1, FeeSchedule::query()->count());
        $this->assertSame('0.10', FeeSchedule::query()->first()->government_fee);
    }

    public function test_services_and_fee_schedules_cannot_be_deleted(): void
    {
        $service = Service::factory()->create();
        $schedule = FeeSchedule::factory()->for($service)->create();

        try {
            $schedule->delete();
            $this->fail('Fee schedule delete should be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Fee schedules', $exception->getMessage());
        }

        try {
            $service->delete();
            $this->fail('Service delete should be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Services', $exception->getMessage());
        }

        $this->assertModelExists($service);
        $this->assertModelExists($schedule);
    }

    public function test_the_service_directory_is_available_in_marathi(): void
    {
        $executive = $this->makeUser('office-executive', ['locale' => 'mr']);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.services.index'))
            ->assertOk()
            ->assertSee('सेवा')
            ->assertDontSee('नवी सेवा');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function servicePayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'cte',
            'name' => 'Consent to Establish',
            'category' => 'Consent',
            'description' => 'First consent for a new industry unit.',
            'detailed_description' => 'Prepared for the Maharashtra office.',
            'expected_processing_days' => '45',
            'internal_notes' => 'Estimate only.',
            'industries' => ['chemical', 'pharmaceutical'],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function feePayload(Service $service, array $overrides = []): array
    {
        return array_merge([
            'service_id' => $service->id,
            'effective_from' => '2026-01-01',
            'effective_until' => '',
            'government_fee' => '1000.00',
            'consultancy_fee' => '2500.00',
            'internal_notes' => '',
        ], $overrides);
    }
}
