<?php

namespace Tests\Feature;

use App\Enums\FollowUpStatus;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\FollowUp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\FollowUpReminder;
use App\Services\FollowUps\ImportLegacyEnquiryFollowUps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        Carbon::setTestNow(Carbon::parse('2026-11-19 12:00:00', 'Asia/Kolkata'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_past_follow_up_can_be_recorded_as_completed_and_edited(): void
    {
        $executive = $this->makeUser('office-executive');
        $enquiry = Enquiry::factory()->create();

        $this->actingAs($executive, 'staff')
            ->post(route('staff.follow-ups.store'), $this->payload($enquiry, [
                'scheduled_at' => '2026-11-18T10:00',
                'follow_up_status' => 'completed',
                'notes' => 'Caller already shared the papers.',
            ]))
            ->assertRedirect(route('staff.enquiries.show', $enquiry));

        $followUp = FollowUp::query()->firstOrFail();
        $this->assertSame(FollowUpStatus::Completed, $followUp->status);
        $this->assertSame('2026-11-18 10:00:00', $followUp->completed_at?->timezone('Asia/Kolkata')->format('Y-m-d H:i:s'));
        $this->assertFalse($followUp->isOverdue());

        $this->actingAs($executive, 'staff')
            ->put(route('staff.follow-ups.update', $followUp), $this->payload($enquiry, [
                'notes' => 'Caller already shared the signed papers.',
                'follow_up_status' => 'pending',
                'scheduled_at' => '2026-11-18T10:00',
            ]))
            ->assertRedirect(route('staff.enquiries.show', $enquiry));

        $followUp->refresh();
        $this->assertSame('Caller already shared the signed papers.', $followUp->notes);
        $this->assertSame(FollowUpStatus::Pending, $followUp->status);
        $this->assertNull($followUp->completed_at);
        $this->assertTrue($followUp->isOverdue());
    }

    public function test_overdue_work_stays_pending_until_someone_completes_or_cancels_it(): void
    {
        $executive = $this->makeUser('office-executive');
        $enquiry = Enquiry::factory()->create(['next_follow_up_on' => '2026-11-25']);

        $this->actingAs($executive, 'staff')
            ->post(route('staff.follow-ups.store'), $this->payload($enquiry, [
                'scheduled_at' => '2026-11-18T09:00',
                'follow_up_status' => 'pending',
            ]))
            ->assertRedirect();

        $followUp = FollowUp::query()->where('status', FollowUpStatus::Pending)->where('scheduled_at', '2026-11-18 09:00:00')->firstOrFail();
        $this->assertTrue($followUp->isOverdue());
        $this->artisan('follow-ups:remind')->assertSuccessful();
        $this->assertSame(FollowUpStatus::Pending, $followUp->fresh()->status);

        $this->actingAs($executive, 'staff')
            ->post(route('staff.follow-ups.complete', $followUp), ['return_to' => 'parent'])
            ->assertRedirect(route('staff.enquiries.show', $enquiry));

        $completed = $followUp->fresh();
        $this->assertSame(FollowUpStatus::Completed, $completed->status);
        $this->assertNotNull($completed->completed_at);
        $this->assertFalse($completed->isOverdue());

        $this->actingAs($executive, 'staff')
            ->post(route('staff.follow-ups.status', $followUp), [
                'follow_up_status' => 'cancelled',
                'return_to' => 'parent',
            ])
            ->assertRedirect();

        $cancelled = $followUp->fresh();
        $this->assertSame(FollowUpStatus::Cancelled, $cancelled->status);
        $this->assertNull($cancelled->completed_at);

        $this->actingAs($executive, 'staff')
            ->post(route('staff.follow-ups.status', $followUp), [
                'follow_up_status' => 'pending',
                'return_to' => 'parent',
            ])
            ->assertRedirect();

        $reopened = $followUp->fresh();
        $this->assertSame(FollowUpStatus::Pending, $reopened->status);
        $this->assertNull($reopened->completed_at);

        $this->actingAs($executive, 'staff')
            ->delete(route('staff.follow-ups.destroy', $followUp), ['return_to' => 'parent'])
            ->assertSessionHasErrors('confirm_delete');

        $this->actingAs($executive, 'staff')
            ->delete(route('staff.follow-ups.destroy', $followUp), [
                'confirm_delete' => '1',
                'return_to' => 'parent',
            ])
            ->assertRedirect(route('staff.enquiries.show', $enquiry));

        $this->assertModelMissing($followUp);
    }

    public function test_permissions_stop_people_from_changing_records_they_cannot_manage(): void
    {
        $accounts = $this->makeUser('accounts', ['locale' => 'mr']);
        $executive = $this->makeUser('office-executive');
        $enquiry = Enquiry::factory()->create();
        $client = Client::factory()->create();
        $followUp = $this->makeFollowUp($enquiry, $executive);

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.follow-ups.index'))
            ->assertOk()
            ->assertSee('पाठपुरावे')
            ->assertDontSee('पाठपुरावा जोडा');

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.follow-ups.store'), $this->payload($enquiry))
            ->assertForbidden();

        $this->actingAs($accounts, 'staff')
            ->put(route('staff.follow-ups.update', $followUp), $this->payload($enquiry))
            ->assertForbidden();

        $this->actingAs($accounts, 'staff')
            ->post(route('staff.follow-ups.complete', $followUp))
            ->assertForbidden();

        $this->actingAs($accounts, 'staff')
            ->delete(route('staff.follow-ups.destroy', $followUp), ['confirm_delete' => '1'])
            ->assertForbidden();

        $limited = $this->userWithPermissions(['enquiries.view', 'follow-ups.view', 'follow-ups.complete']);

        $this->actingAs($limited, 'staff')
            ->post(route('staff.follow-ups.complete', $followUp))
            ->assertRedirect();

        $this->actingAs($limited, 'staff')
            ->put(route('staff.follow-ups.update', $followUp), $this->payload($enquiry))
            ->assertForbidden();

        $this->actingAs($limited, 'staff')
            ->delete(route('staff.follow-ups.destroy', $followUp), ['confirm_delete' => '1'])
            ->assertForbidden();

        $enquiryOnly = $this->userWithPermissions(['enquiries.view', 'follow-ups.create']);

        $this->actingAs($enquiryOnly, 'staff')
            ->post(route('staff.follow-ups.store'), $this->payload($client, [
                'followable_type' => 'client',
            ]))
            ->assertForbidden();

        $this->actingAs($enquiryOnly, 'staff')
            ->post(route('staff.follow-ups.store'), $this->payload($enquiry, [
                'notes' => 'A follow-up this person is allowed to add.',
            ]))
            ->assertRedirect();
    }

    public function test_the_directory_puts_overdue_work_before_later_tasks(): void
    {
        $executive = $this->makeUser('office-executive');
        $enquiry = Enquiry::factory()->create(['contact_name' => 'Ravi Patil']);
        $this->makeFollowUp($enquiry, $executive, [
            'notes' => 'Later visit',
            'scheduled_at' => '2026-11-25 10:00:00',
        ]);
        $this->makeFollowUp($enquiry, $executive, [
            'notes' => 'Missed call',
            'scheduled_at' => '2026-11-17 10:00:00',
        ]);
        $this->makeFollowUp($enquiry, $executive, [
            'notes' => 'Finished call',
            'scheduled_at' => '2026-11-10 10:00:00',
            'status' => FollowUpStatus::Completed,
            'completed_at' => '2026-11-10 10:00:00',
        ]);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.follow-ups.index'))
            ->assertOk()
            ->assertSeeInOrder(['Missed call', 'Later visit', 'Finished call']);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.follow-ups.index', ['q' => 'Missed', 'status' => 'overdue', 'type' => 'call', 'related' => 'enquiry']))
            ->assertOk()
            ->assertSee('Missed call')
            ->assertDontSee('Later visit')
            ->assertDontSee('Finished call');
    }

    public function test_a_reminder_is_sent_once_to_the_assigned_staff_member(): void
    {
        Notification::fake();
        $executive = $this->makeUser('office-executive');
        $enquiry = Enquiry::factory()->create();
        $due = $this->makeFollowUp($enquiry, $executive, [
            'reminder_at' => '2026-11-19 11:00:00',
            'notes' => 'Call about the consent.',
        ]);
        $this->makeFollowUp($enquiry, $executive, [
            'reminder_at' => '2026-11-20 11:00:00',
            'notes' => 'Not due yet.',
        ]);

        $this->artisan('follow-ups:remind')->assertSuccessful();
        $this->artisan('follow-ups:remind')->assertSuccessful();

        Notification::assertSentToTimes($executive, FollowUpReminder::class, 1);
        Notification::assertSentTo($executive, FollowUpReminder::class, function (FollowUpReminder $notification, array $channels) use ($due): bool {
            return $channels === ['database'] && $notification->toArray($due->assignee)['follow_up_id'] === $due->id;
        });
        $this->assertNotNull($due->fresh()->reminded_at);
    }

    public function test_existing_enquiry_follow_ups_are_copied_once(): void
    {
        $enquiry = Enquiry::factory()->create([
            'requirement' => 'Needs a consent reminder.',
            'next_follow_up_on' => '2026-11-25',
        ]);
        $legacy = new EnquiryFollowUp([
            'followed_up_at' => '2026-11-18 10:00:00',
            'method' => 'phone',
            'notes' => 'Asked for the unit address.',
            'outcome' => 'Will send it',
        ]);
        $legacy->enquiry_id = $enquiry->id;
        $legacy->created_at = now();
        $legacy->save();

        $importer = app(ImportLegacyEnquiryFollowUps::class);
        $this->assertSame(2, $importer->execute());
        $this->assertSame(0, $importer->execute());

        $copied = FollowUp::query()->where('legacy_enquiry_follow_up_id', $legacy->id)->firstOrFail();
        $this->assertSame(FollowUpStatus::Completed, $copied->status);
        $this->assertStringContainsString('Asked for the unit address.', $copied->notes);
        $this->assertStringContainsString('Will send it', $copied->notes);
        $this->assertNotNull(FollowUp::query()->where('import_key', 'enquiry-next-'.$enquiry->id)->first());
        $this->assertModelExists($legacy);
    }

    /**
     * @param  list<string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::query()->create([
            'name' => 'Follow-up test '.implode('-', $slugs),
            'slug' => 'follow-up-test-'.md5(implode('|', $slugs)),
            'portal' => 'staff',
            'is_system' => false,
        ]);
        $role->permissions()->sync(Permission::query()->whereIn('slug', $slugs)->pluck('id'));

        $user = User::factory()->create(['portal' => 'staff']);
        $user->roles()->sync([$role->id]);

        return $user->fresh();
    }

    private function makeFollowUp(Enquiry $enquiry, User $assignee, array $overrides = []): FollowUp
    {
        $followUp = new FollowUp(array_merge([
            'type' => 'call',
            'scheduled_at' => '2026-11-21 10:00:00',
            'assigned_to' => $assignee->id,
            'notes' => 'A scheduled follow-up.',
            'status' => FollowUpStatus::Pending,
        ], $overrides));
        $followUp->followable()->associate($enquiry);
        $followUp->created_by = $assignee->id;
        $followUp->updated_by = $assignee->id;
        $followUp->save();

        return $followUp->fresh(['assignee']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Enquiry|Client $parent, array $overrides = []): array
    {
        return array_merge([
            'followable_type' => $parent instanceof Enquiry ? 'enquiry' : 'client',
            'followable_id' => $parent->id,
            'type' => 'call',
            'scheduled_at' => '2026-11-21T10:00',
            'follow_up_assigned_to' => '',
            'notes' => 'Call the contact about the pending papers.',
            'reminder_at' => '',
            'follow_up_status' => 'pending',
            'return_to' => 'parent',
        ], $overrides);
    }
}
