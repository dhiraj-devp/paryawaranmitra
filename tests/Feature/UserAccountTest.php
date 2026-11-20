<?php

namespace Tests\Feature;

use App\Livewire\Auth\LoginForm;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class UserAccountTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_an_administrator_can_store_a_team_login_and_that_person_can_sign_in(): void
    {
        $admin = $this->makeUser('super-admin');
        $role = Role::query()->where('slug', 'office-executive')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'New Executive',
                'email' => 'executive@riverbend.test',
                'portal' => 'staff',
                'role_id' => $role->id,
                'password' => 'Office#Team#2026',
                'password_confirmation' => 'Office#Team#2026',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $person = User::query()->where('email', 'executive@riverbend.test')->firstOrFail();

        $this->assertTrue(Hash::check('Office#Team#2026', $person->password));
        $this->assertTrue($person->hasRole('office-executive'));

        $log = AuditLog::query()->where('action', 'user.created')->first();
        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);

        $this->app['auth']->forgetGuards();

        Livewire::test(LoginForm::class, ['portal' => 'staff'])
            ->set('email', 'executive@riverbend.test')
            ->set('password', 'Office#Team#2026')
            ->call('authenticate')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_cannot_create_accounts_and_a_short_password_is_rejected(): void
    {
        $executive = $this->makeUser('office-executive');
        $admin = $this->makeUser('super-admin');
        $role = Role::query()->where('slug', 'office-executive')->firstOrFail();

        $this->actingAs($executive, 'staff')
            ->post(route('admin.users.store'), [])
            ->assertRedirect(route('admin.login'));

        $this->actingAs($admin, 'admin')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Short Password',
                'email' => 'short@riverbend.test',
                'portal' => 'staff',
                'role_id' => $role->id,
                'password' => 'short',
                'password_confirmation' => 'short',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('password');

        $this->assertNull(User::query()->where('email', 'short@riverbend.test')->first());
    }

    public function test_a_password_can_be_changed_without_replacing_it_when_left_blank(): void
    {
        $admin = $this->makeUser('super-admin');
        $person = $this->makeUser('office-executive');
        $original = $person->password;
        $role = Role::query()->where('slug', 'office-executive')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.users.update', $person), [
                'name' => 'Renamed Executive',
                'email' => $person->email,
                'portal' => 'staff',
                'role_id' => $role->id,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame($original, $person->fresh()->password);
        $this->assertSame('Renamed Executive', $person->fresh()->name);
    }

    public function test_staff_and_admin_can_change_their_own_profile(): void
    {
        $executive = $this->makeUser('office-executive');
        $admin = $this->makeUser('super-admin');
        $adminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();

        $this->actingAs($executive, 'staff')
            ->put(route('staff.profile.update'), [
                'name' => 'Updated Executive',
                'email' => $executive->email,
            ])
            ->assertRedirect(route('staff.profile.edit'));

        $this->assertSame('Updated Executive', $executive->fresh()->name);

        $this->actingAs($executive, 'staff')
            ->from(route('staff.profile.edit'))
            ->put(route('staff.profile.update'), [
                'name' => 'Updated Executive',
                'email' => $executive->email,
                'current_password' => 'password',
                'password' => 'Staff#Profile#2026',
                'password_confirmation' => 'Staff#Profile#2026',
            ])
            ->assertRedirect(route('staff.profile.edit'));

        $this->assertTrue(Hash::check('Staff#Profile#2026', $executive->fresh()->password));

        $this->actingAs($admin, 'admin')
            ->put(route('admin.profile.update'), [
                'name' => 'Renamed Admin',
                'email' => 'dynamic-admin@riverbend.test',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $this->app['auth']->forgetGuards();

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', 'dynamic-admin@riverbend.test')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));

        $this->app['auth']->forgetGuards();

        $this->actingAs($admin->fresh(), 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'Second Admin',
                'email' => 'second-admin@riverbend.test',
                'portal' => 'admin',
                'role_id' => $adminRole->id,
                'password' => 'Admin#Dynamic#2026',
                'password_confirmation' => 'Admin#Dynamic#2026',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->app['auth']->forgetGuards();

        Livewire::test(LoginForm::class, ['portal' => 'admin'])
            ->set('email', 'second-admin@riverbend.test')
            ->set('password', 'Admin#Dynamic#2026')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_profile_password_change_needs_the_current_password(): void
    {
        $executive = $this->makeUser('office-executive');

        $this->actingAs($executive, 'staff')
            ->from(route('staff.profile.edit'))
            ->put(route('staff.profile.update'), [
                'name' => $executive->name,
                'email' => $executive->email,
                'current_password' => 'wrong-password',
                'password' => 'Staff#Profile#2026',
                'password_confirmation' => 'Staff#Profile#2026',
            ])
            ->assertRedirect(route('staff.profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $executive->fresh()->password));
    }
}
