<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_guests_are_sent_to_the_login_for_the_area_they_opened(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('staff.dashboard'))->assertRedirect(route('staff.login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/staff')->assertRedirect(route('staff.login'));
    }

    public function test_home_offers_both_internal_workspaces(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('admin.login'), false)
            ->assertSee(route('staff.login'), false)
            ->assertSee(__('ui.home.customer_unavailable'));
    }

    public function test_customer_portal_is_reserved_and_has_no_authentication(): void
    {
        $this->get(route('customer.login'))
            ->assertOk()
            ->assertSee(__('ui.customer.body'))
            ->assertDontSee('name="password"', false);

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee(__('ui.customer.title'));

        $this->post(route('customer.login'))->assertMethodNotAllowed();
        $this->get('/customer/register')->assertNotFound();
    }

    public function test_a_staff_session_cannot_open_the_admin_dashboard(): void
    {
        $staff = $this->makeUser('office-executive');

        $this->actingAs($staff, 'staff')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_session_cannot_open_the_staff_dashboard(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin, 'admin')
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_a_staff_user_forced_onto_the_admin_guard_is_rejected(): void
    {
        $staff = $this->makeUser('office-executive');

        $this->actingAs($staff, 'admin')
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->assertGuest('admin');
    }

    public function test_an_inactive_session_is_rejected(): void
    {
        $admin = $this->makeUser('super-admin');
        $admin->update(['is_active' => false]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_user_administration_is_limited_to_people_with_permission(): void
    {
        $super = $this->makeUser('super-admin');
        $plain = User::factory()->admin()->create();

        $this->actingAs($plain, 'admin')
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($super, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($super->email)
            ->assertSee(__('ui.roles.super_admin'));

        $this->actingAs($super, 'admin')
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Asia/Kolkata')
            ->assertDontSee((string) config('app.key'));

        $this->actingAs($super, 'admin')
            ->get(route('admin.settings.audit-logs'))
            ->assertOk();

        $this->actingAs($plain, 'admin')
            ->get(route('admin.settings.audit-logs'))
            ->assertForbidden();
    }

    public function test_policies_allow_review_and_refuse_deletion(): void
    {
        $super = $this->makeUser('super-admin');
        $consultant = $this->makeUser('consultant');

        $this->assertTrue(Gate::forUser($super)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($super)->allows('delete', $super));
        $this->assertFalse(Gate::forUser($consultant)->allows('viewAny', AuditLog::class));
    }
}
