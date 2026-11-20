<?php

namespace Tests\Feature;

use App\Enums\Portal;
use App\Enums\RecordStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\IndustryUnit;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class ClientIndustryTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_a_manager_can_record_a_client_and_its_industry_unit(): void
    {
        $executive = $this->makeUser('office-executive');

        $this->actingAs($executive, 'staff')
            ->post(route('staff.clients.store'), $this->clientPayload())
            ->assertRedirect();

        $client = Client::query()->where('legal_name', 'Riverbend Chemicals Private Limited')->first();
        $this->assertNotNull($client);
        $this->assertSame(RecordStatus::Active, $client->status);
        $this->assertSame('ABCDE1234F', $client->pan);
        $this->assertSame($executive->id, $client->created_by);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.show', $client))
            ->assertOk()
            ->assertSee('Riverbend Chemicals Private Limited')
            ->assertSee(__('ui.audit_actions.client_created'));

        $this->actingAs($executive, 'staff')
            ->post(route('staff.clients.units.store', $client), $this->unitPayload())
            ->assertRedirect();

        $unit = IndustryUnit::query()->where('client_id', $client->id)->first();
        $this->assertNotNull($unit);
        $this->assertSame('Chakan Plant', $unit->name);
        $this->assertSame('Plot 18, MIDC Chakan', $unit->site_address_line);
        $this->assertSame('chemical', $unit->industry_category);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'client.created',
            'subject_type' => $client->getMorphClass(),
            'subject_id' => $client->id,
            'actor_id' => $executive->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'industry_unit.created',
            'subject_id' => $unit->id,
        ]);
    }

    public function test_view_permission_cannot_change_records_and_portals_stay_separate(): void
    {
        $accounts = $this->makeUser('accounts');
        $consultant = $this->makeUser('consultant');
        $executive = $this->makeUser('office-executive');
        $admin = $this->makeUser('super-admin');
        $client = Client::factory()->create(['legal_name' => 'Shared Client']);

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.clients.index'))
            ->assertOk()
            ->assertSee('Shared Client');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.clients.store'), $this->clientPayload(['legal_name' => 'Blocked Client', 'pan' => null, 'gstin' => null]))
            ->assertForbidden();

        $this->actingAs($consultant, 'staff')
            ->get(route('staff.clients.units.create', $client))
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->get(route('admin.clients.show', $client))
            ->assertRedirect(route('admin.login'));

        $this->app['auth']->forgetGuards();

        $this->actingAs($admin, 'admin')
            ->get(route('staff.clients.show', $client))
            ->assertRedirect(route('staff.login'));

        $this->app['auth']->forgetGuards();

        $this->actingAs($executive, 'admin')
            ->get(route('admin.clients.index'))
            ->assertForbidden();
    }

    public function test_duplicate_identity_is_rejected_until_a_different_organisation_is_confirmed(): void
    {
        $executive = $this->makeUser('office-executive');
        $this->actingAs($executive, 'staff')->post(route('staff.clients.store'), $this->clientPayload())->assertRedirect();

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.create'))
            ->post(route('staff.clients.store'), $this->clientPayload([
                'legal_name' => 'riverbend chemicals private limited',
                'pan' => 'ZZZZZ9999Z',
                'gstin' => '27ZZZZZ9999Z1Z5',
                'email' => 'other@riverbend.test',
                'phone' => '9000000001',
            ]))
            ->assertRedirect(route('staff.clients.create'))
            ->assertSessionHasErrors('duplicate_name');

        $this->assertSame(1, Client::query()->count());

        $this->actingAs($executive, 'staff')
            ->post(route('staff.clients.store'), $this->clientPayload([
                'legal_name' => 'riverbend chemicals private limited',
                'pan' => 'ZZZZZ9999Z',
                'gstin' => '27ZZZZZ9999Z1Z5',
                'email' => 'other@riverbend.test',
                'phone' => '9000000001',
                'acknowledge_duplicate' => '1',
            ]))
            ->assertRedirect();

        $this->assertSame(2, Client::query()->count());
        $log = AuditLog::query()->where('action', 'client.created')->latest('id')->first();
        $this->assertTrue($log->new_values['duplicate_acknowledged']);

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.create'))
            ->post(route('staff.clients.store'), $this->clientPayload([
                'legal_name' => 'Another Chemicals',
                'pan' => 'ABCDE1234F',
                'gstin' => null,
                'email' => 'third@riverbend.test',
            ]))
            ->assertSessionHasErrors('pan');
    }

    public function test_a_gstin_must_contain_the_pan_and_a_category_must_be_known(): void
    {
        $executive = $this->makeUser('office-executive');

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.create'))
            ->post(route('staff.clients.store'), $this->clientPayload([
                'pan' => 'ABCDE1234F',
                'gstin' => '27QWERT1234Y1Z8',
            ]))
            ->assertSessionHasErrors('gstin');

        $client = Client::factory()->create();

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.units.create', $client))
            ->post(route('staff.clients.units.store', $client), $this->unitPayload([
                'industry_category' => 'not-a-real-category',
            ]))
            ->assertSessionHasErrors('industry_category');
    }

    public function test_an_industry_unit_stays_on_its_client_and_cannot_repeat_a_name(): void
    {
        $executive = $this->makeUser('office-executive');
        $first = Client::factory()->create();
        $second = Client::factory()->create();
        $unit = IndustryUnit::factory()->for($first)->create(['name' => 'Chakan Plant']);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.units.show', [$second, $unit]))
            ->assertNotFound();

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.units.create', $first))
            ->post(route('staff.clients.units.store', $first), $this->unitPayload(['name' => 'chakan-plant']))
            ->assertSessionHasErrors('name');

        $this->actingAs($executive, 'staff')
            ->post(route('staff.clients.units.store', $second), $this->unitPayload(['name' => 'Chakan Plant']))
            ->assertRedirect();

        $this->assertSame(1, $second->industryUnits()->count());

        $this->actingAs($executive, 'staff')
            ->put(route('staff.clients.units.update', [$first, $unit]), $this->unitPayload([
                'name' => 'Chakan Plant East',
                'client_id' => $second->id,
            ]))
            ->assertRedirect(route('staff.clients.units.show', [$first, $unit]));

        $this->assertSame($first->id, $unit->fresh()->client_id);
        $this->assertSame('Chakan Plant East', $unit->fresh()->name);

        $update = AuditLog::query()->where('action', 'industry_unit.updated')->first();
        $this->assertSame('Chakan Plant', $update->old_values['name']);
        $this->assertSame('Chakan Plant East', $update->new_values['name']);
    }

    public function test_status_changes_are_confirmed_and_an_inactive_client_cannot_gain_a_unit(): void
    {
        $executive = $this->makeUser('office-executive');
        $client = Client::factory()->create();

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.show', $client))
            ->post(route('staff.clients.status', $client), ['status' => 'inactive'])
            ->assertSessionHasErrors('confirm_status');

        $this->assertSame(RecordStatus::Active, $client->fresh()->status);

        $this->actingAs($executive, 'staff')
            ->post(route('staff.clients.status', $client), [
                'status' => 'inactive',
                'confirm_status' => '1',
            ])
            ->assertRedirect(route('staff.clients.show', $client));

        $client->refresh();
        $this->assertSame(RecordStatus::Inactive, $client->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'client.status_changed',
            'subject_id' => $client->id,
        ]);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.units.create', $client))
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->from(route('staff.clients.show', $client))
            ->post(route('staff.clients.units.store', $client), $this->unitPayload())
            ->assertSessionHasErrors('client');

        $this->assertSame(0, $client->industryUnits()->count());

        $visible = Client::factory()->create(['legal_name' => 'Visible Mills']);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($client->legal_name)
            ->assertDontSee('Visible Mills');

        $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Visible Mills')
            ->assertDontSee($client->legal_name);
    }

    public function test_search_respects_client_permission_and_a_failed_audit_does_not_keep_the_client(): void
    {
        $executive = $this->makeUser('office-executive');
        $outsider = User::factory()->create(['portal' => Portal::Staff]);
        $client = Client::factory()->create(['legal_name' => 'Searchable Alloys']);
        IndustryUnit::factory()->for($client)->create(['name' => 'Wagholi Furnace']);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.search', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee(route('staff.clients.show', $client), false)
            ->assertSee(__('ui.search.types.client'));

        $this->actingAs($executive, 'staff')
            ->get(route('staff.search', ['q' => 'Wagholi']))
            ->assertOk()
            ->assertSee(__('ui.search.types.industry_unit'));

        $this->actingAs($outsider, 'staff')
            ->get(route('staff.search', ['q' => 'Searchable']))
            ->assertOk()
            ->assertDontSee(route('staff.clients.show', $client), false);

        $this->mock(AuditLogger::class, function ($mock): void {
            $mock->shouldReceive('log')->andThrow(new RuntimeException('audit failed'));
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($executive, 'staff')
                ->post(route('staff.clients.store'), $this->clientPayload([
                    'legal_name' => 'Should Not Persist',
                    'pan' => 'DDDDD1111D',
                    'gstin' => '27DDDDD1111D1Z2',
                    'email' => 'rollback@riverbend.test',
                ]));
            $this->fail('The save should have stopped when the audit log failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }

        $this->assertNull(Client::query()->where('legal_name', 'Should Not Persist')->first());
    }

    public function test_clients_and_industry_units_cannot_be_deleted(): void
    {
        $client = Client::factory()->create();
        $unit = IndustryUnit::factory()->for($client)->create();

        try {
            $unit->delete();
            $this->fail('Industry unit delete should be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Industry units', $exception->getMessage());
        }

        try {
            $client->delete();
            $this->fail('Client delete should be refused.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Clients', $exception->getMessage());
        }

        $this->assertModelExists($client);
        $this->assertModelExists($unit);
    }

    public function test_an_unlinked_client_can_be_deleted_and_a_linked_one_cannot(): void
    {
        $executive = $this->makeUser('office-executive');
        $free = Client::factory()->create();
        $linked = Client::factory()->create();
        IndustryUnit::factory()->for($linked)->create();

        $response = $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.index'))
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('action="'.route('staff.clients.destroy', $free).'"', $html);
        $this->assertStringNotContainsString('action="'.route('staff.clients.destroy', $linked).'"', $html);

        $this->actingAs($executive, 'staff')
            ->delete(route('staff.clients.destroy', $linked), ['confirm_delete' => 1])
            ->assertForbidden();

        $this->actingAs($executive, 'staff')
            ->delete(route('staff.clients.destroy', $free), ['confirm_delete' => 1])
            ->assertRedirect(route('staff.clients.index'));

        $this->assertModelMissing($free);
        $this->assertModelExists($linked);
    }

    public function test_the_client_directory_is_available_in_marathi(): void
    {
        $executive = $this->makeUser('office-executive', ['locale' => 'mr']);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.clients.index'))
            ->assertOk()
            ->assertSee('कायदेशीर ग्राहक')
            ->assertSee('नवा ग्राहक');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function clientPayload(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Riverbend Chemicals Private Limited',
            'trade_name' => 'Riverbend',
            'constitution' => 'private_limited',
            'pan' => 'ABCDE1234F',
            'gstin' => '27ABCDE1234F1Z5',
            'email' => 'office@riverbend.test',
            'phone' => '98765 43210',
            'contact_name' => 'Meera Joshi',
            'address_line' => '12 Industrial Road',
            'city' => 'Pune',
            'district' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'notes' => 'Head office',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function unitPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Chakan Plant',
            'industry_category' => 'chemical',
            'activity' => 'Solvent distillation',
            'site_address_line' => 'Plot 18, MIDC Chakan',
            'site_landmark' => 'Near the highway',
            'site_village' => 'Chakan',
            'site_taluka' => 'Khed',
            'site_district' => 'Pune',
            'site_state' => 'Maharashtra',
            'site_pincode' => '410501',
            'plot_number' => '18',
            'site_contact_name' => 'Ravi Patil',
            'site_contact_phone' => '9123456780',
            'notes' => 'Main plant',
        ], $overrides);
    }
}
