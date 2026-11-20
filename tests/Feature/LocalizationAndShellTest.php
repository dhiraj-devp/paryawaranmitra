<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class LocalizationAndShellTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_a_guest_can_switch_the_workspace_language(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'mr'])
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('कर्मचारी');
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'hi'])
            ->assertSessionHasErrors('locale');
    }

    public function test_an_authenticated_language_choice_is_saved_and_audited(): void
    {
        $staff = $this->makeUser('office-executive', ['locale' => 'en']);

        $this->actingAs($staff, 'staff')
            ->from(route('staff.dashboard'))
            ->post(route('locale.update'), ['locale' => 'mr'])
            ->assertRedirect(route('staff.dashboard'));

        $this->assertSame('mr', $staff->fresh()->locale);

        $log = AuditLog::query()->where('action', 'profile.locale_changed')->first();
        $this->assertNotNull($log);
        $this->assertSame('en', $log->old_values['locale']);
        $this->assertSame('mr', $log->new_values['locale']);

        $this->actingAs($staff->fresh(), 'staff')
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('डॅशबोर्ड')
            ->assertSee('चौकशी');
    }

    public function test_the_shell_shows_planned_modules_without_fake_create_actions(): void
    {
        $admin = $this->makeUser('super-admin');
        $executive = $this->makeUser('office-executive');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('ui.nav.items.enquiries'))
            ->assertSee(__('ui.nav.items.bank_guarantees'))
            ->assertSee(__('ui.nav.items.users'))
            ->assertSee('/admin/users', false)
            ->assertSee('/admin/clients/create', false)
            ->assertSee(__('ui.nav.later'))
            ->assertDontSee(__('ui.header.no_quick_actions'));

        $this->actingAs($executive, 'staff')
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee(__('ui.nav.items.enquiries'))
            ->assertDontSee(__('ui.nav.items.users'))
            ->assertDontSee(__('ui.nav.items.invoices'));
    }

    public function test_quick_create_only_lists_registered_actions_the_user_can_perform(): void
    {
        app('router')
            ->get('/staff/invoices/create', fn () => 'ready')
            ->name('staff.invoices.create');
        app('router')->getRoutes()->refreshNameLookups();

        config()->set('navigation.quick_actions', [[
            'label' => 'ui.nav.items.invoices',
            'route' => 'invoices.create',
            'permission' => 'invoices.manage',
            'portals' => ['staff'],
        ]]);

        $accounts = $this->makeUser('accounts');
        $executive = $this->makeUser('office-executive');

        $this->actingAs($accounts, 'staff')
            ->get(route('staff.dashboard'))
            ->assertSee('/staff/invoices/create', false);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.dashboard'))
            ->assertDontSee('/staff/invoices/create', false);
    }
}
