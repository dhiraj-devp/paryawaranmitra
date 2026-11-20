<?php

namespace Tests\Feature;

use App\Livewire\Auth\LoginForm;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    public function test_admin_can_sign_in_and_reach_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'locale' => 'mr',
        ]);

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'actor_id' => $admin->id,
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('डॅशबोर्ड');
    }

    public function test_staff_can_sign_in(): void
    {
        $staff = User::factory()->create();

        Livewire::test(LoginForm::class, ['portal' => 'staff'])
            ->set('email', $staff->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('staff.dashboard'));

        $this->assertAuthenticatedAs($staff, 'staff');
    }

    public function test_invalid_credentials_are_rejected_and_audited_without_the_password(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', $admin->email)
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors('email')
            ->assertSee(__('auth.failed'))
            ->assertSet('password', '');

        $this->assertGuest('admin');

        $log = AuditLog::query()->where('action', 'auth.login_failed')->first();
        $this->assertNotNull($log);
        $this->assertSame('invalid_credentials', $log->new_values['reason']);
        $this->assertArrayNotHasKey('password', $log->new_values);
    }

    public function test_a_staff_account_cannot_sign_in_through_the_admin_form(): void
    {
        $staff = User::factory()->create();

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', $staff->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasErrors('email');

        $this->assertGuest('admin');
        $this->assertSame('wrong_portal', AuditLog::query()->first()->new_values['reason']);
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasErrors('email');

        $this->assertGuest('admin');
        $this->assertSame('inactive', AuditLog::query()->first()->new_values['reason']);
    }

    public function test_login_is_throttled(): void
    {
        $admin = User::factory()->admin()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            Livewire::test(LoginForm::class, ['portal' => 'admin'])
                ->set('email', $admin->email)
                ->set('password', 'wrong-password')
                ->call('authenticate')
                ->assertHasErrors('email');
        }

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', $admin->email)
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertSee('Too many login attempts');

        $this->assertSame(5, AuditLog::query()->where('action', 'auth.login_failed')->count());
    }

    public function test_the_admin_two_factor_flag_stops_sign_in_until_enrollment_exists(): void
    {
        config(['security.admin_two_factor' => true]);
        $admin = User::factory()->admin()->create();

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasErrors('email')
            ->assertSee(__('auth.two_factor_unavailable'));

        $this->assertGuest('admin');
        $this->assertSame('two_factor_unavailable', AuditLog::query()->first()->new_values['reason']);
    }

    public function test_logout_records_an_audit_event_and_ends_that_portal_only(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin, 'admin');
        $this->actingAs($staff, 'staff');

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($staff, 'staff');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_get_logout_is_not_available(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.logout'))
            ->assertMethodNotAllowed();
    }
}
