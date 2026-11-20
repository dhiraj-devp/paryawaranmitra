<?php

namespace Tests\Feature;

use App\Actions\Access\AssignRole;
use App\Exceptions\PortalMismatchException;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\AccessCatalog;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\InteractsWithAccess;
use Tests\TestCase;

class SearchAccessAndSecurityTest extends TestCase
{
    use InteractsWithAccess;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_admin_search_finds_people_and_staff_search_does_not_reveal_them(): void
    {
        $admin = $this->makeUser('super-admin');
        $person = User::factory()->create([
            'name' => 'Asha Kulkarni',
            'email' => 'asha.unique@example.com',
        ]);
        $executive = $this->makeUser('office-executive');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.search', ['q' => 'Asha']))
            ->assertOk()
            ->assertSee($person->email);

        $this->actingAs($executive, 'staff')
            ->get(route('staff.search', ['q' => 'Asha']))
            ->assertOk()
            ->assertDontSee($person->email);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.search', ['q' => 'A']))
            ->assertSee(__('ui.search.too_short'));
    }

    public function test_role_permissions_follow_the_matrix(): void
    {
        $executive = $this->makeUser('office-executive');
        $accounts = $this->makeUser('accounts');
        $consultant = $this->makeUser('consultant');
        $admin = $this->makeUser('super-admin');

        $this->assertTrue($executive->hasPermission('enquiries.manage'));
        $this->assertFalse($executive->hasPermission('invoices.manage'));
        $this->assertFalse($executive->hasPermission('users.view'));
        $this->assertFalse($executive->hasPermission('audit.view'));

        $this->assertTrue($accounts->hasPermission('invoices.manage'));
        $this->assertFalse($accounts->hasPermission('applications.manage'));

        $this->assertTrue($consultant->hasPermission('applications.manage'));
        $this->assertFalse($consultant->hasPermission('payments.manage'));

        $this->assertTrue($admin->hasPermission('audit.view'));
        $this->assertTrue($admin->hasPermission('invoices.manage'));
    }

    public function test_permission_sync_is_idempotent_and_roles_stay_inside_their_portal(): void
    {
        $count = Permission::query()->count();
        app(AccessCatalog::class)->sync();
        $this->assertSame($count, Permission::query()->count());

        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('slug', 'office-executive')->firstOrFail();

        $this->expectException(PortalMismatchException::class);
        app(AssignRole::class)->execute($admin, $role);
    }

    public function test_system_roles_and_audit_logs_cannot_be_removed(): void
    {
        $role = Role::query()->where('slug', 'super-admin')->firstOrFail();

        try {
            $role->delete();
            $this->fail('A system role was deleted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('System roles cannot be deleted.', $exception->getMessage());
        }

        $log = AuditLog::query()->create([
            'action' => 'auth.login',
            'created_at' => now(),
        ]);

        try {
            $log->action = 'changed';
            $log->save();
            $this->fail('An audit log was updated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit logs are immutable.', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $log->delete();
    }

    public function test_audit_redaction_and_hidden_two_factor_secrets(): void
    {
        $clean = app(AuditLogger::class)->redact([
            'password' => 'secret',
            'locale' => 'mr',
            'nested' => ['two_factor_secret' => 'abc'],
        ]);

        $this->assertSame('[redacted]', $clean['password']);
        $this->assertSame('mr', $clean['locale']);
        $this->assertSame('[redacted]', $clean['nested']['two_factor_secret']);

        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => 'pending-secret'])->save();
        $fresh = $user->fresh();

        $this->assertArrayNotHasKey('two_factor_secret', $fresh->toArray());
        $this->assertArrayNotHasKey('password', $fresh->toArray());
        $this->assertFalse($fresh->hasConfirmedTwoFactor());
        $this->assertSame('pending-secret', $fresh->two_factor_secret);
    }

    public function test_responses_send_baseline_security_headers_and_documents_stay_private(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $root = str_replace('\\', '/', config('filesystems.disks.documents.root'));

        $this->assertStringContainsString('/private/documents', $root);
        $this->assertStringNotContainsString('/public/', $root);
        $this->assertFalse(config('filesystems.disks.documents.serve'));
        $this->assertSame('private', config('filesystems.disks.documents.visibility'));
        $this->assertSame('documents', config('security.documents_disk'));
    }
}
