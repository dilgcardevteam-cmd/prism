<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLogs\Models\ActivityLog;
use App\Modules\Users\Models\UserImpersonationAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_impersonate_an_active_lgu_user_and_restore_their_session(): void
    {
        $administrator = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);
        $target = User::factory()->create([
            'role' => User::ROLE_LGU,
            'status' => 'active',
            'username' => 'impersonated-user',
        ]);
        $targetPasswordHash = $target->password;
        $administratorPasswordConfirmedAt = now()->unix();

        $this->actingAs($administrator)->withSession([
            'auth.password_confirmed_at' => $administratorPasswordConfirmedAt,
        ]);
        $originalSessionId = $this->app['session']->driver()->getId();
        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('Impersonate')
            ->assertSee(route('users.impersonate.start', $target->idno));

        $this->post(route('users.impersonate.start', $target))
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($target);
        $this->assertNotSame($originalSessionId, $this->app['session']->driver()->getId());
        $this->assertSame((string) $target->getKey(), (string) auth()->id());
        $this->assertFalse(auth()->user()->isSuperAdmin());
        $this->assertFalse(session()->has('auth.password_confirmed_at'));
        $this->assertSame($targetPasswordHash, $target->fresh()->password);
        $audit = UserImpersonationAuditLog::query()->sole();
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('YOU ARE CURRENTLY IMPERSONATING')
            ->assertSee($target->username)
            ->assertSee($administrator->fullName());
        $activity = ActivityLog::query()
            ->where('user_id', $target->getKey())
            ->latest('id')
            ->firstOrFail();
        $this->assertSame((string) $administrator->getKey(), (string) $activity->properties['impersonator_user_id']);
        $this->assertSame((string) $audit->getKey(), (string) $activity->properties['impersonation_audit_id']);
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('users.impersonate.start', User::factory()->create()))
            ->assertForbidden();

        $this->assertSame((string) $administrator->getKey(), (string) $audit->admin_user_id);
        $this->assertSame((string) $target->getKey(), (string) $audit->target_user_id);
        $this->assertSame(UserImpersonationAuditLog::STATUS_ACTIVE, $audit->status);

        $impersonatedSessionId = $this->app['session']->driver()->getId();
        $this->post(route('users.impersonate.stop'))
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($administrator);
        $this->assertNotSame($impersonatedSessionId, $this->app['session']->driver()->getId());
        $this->assertSame($administratorPasswordConfirmedAt, session('auth.password_confirmed_at'));
        $this->assertSame(UserImpersonationAuditLog::STATUS_ENDED, $audit->fresh()->status);
    }

    public function test_non_admin_cannot_impersonate_a_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LGU]);
        $target = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($user)
            ->post(route('users.impersonate.start', $target))
            ->assertForbidden();

        $this->assertDatabaseCount('user_impersonation_audit_logs', 0);
    }

    public function test_super_admin_cannot_impersonate_themselves_or_another_privileged_account(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $anotherAdministrator = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->actingAs($administrator)
            ->post(route('users.impersonate.start', $administrator))
            ->assertForbidden();

        $this->post(route('users.impersonate.start', $anotherAdministrator))
            ->assertForbidden();

        $this->assertDatabaseCount('user_impersonation_audit_logs', 0);
    }

    public function test_inactive_and_password_reset_required_accounts_cannot_be_impersonated(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $inactiveTarget = User::factory()->create([
            'role' => User::ROLE_LGU,
            'status' => 'inactive',
        ]);
        $passwordResetTarget = User::factory()->create([
            'role' => User::ROLE_LGU,
            'must_change_password' => true,
        ]);
        $wildcardTarget = User::factory()->create([
            'role' => User::ROLE_LGU,
            'access' => User::ACCESS_SCOPE_ALL,
        ]);

        $this->actingAs($administrator)
            ->post(route('users.impersonate.start', $inactiveTarget))
            ->assertForbidden();

        $this->post(route('users.impersonate.start', $passwordResetTarget))
            ->assertForbidden();

        $this->post(route('users.impersonate.start', $wildcardTarget))
            ->assertForbidden();

        $this->assertDatabaseCount('user_impersonation_audit_logs', 0);
    }

    public function test_impersonation_audit_log_cannot_be_deleted_or_rewritten(): void
    {
        $log = UserImpersonationAuditLog::query()->create([
            'admin_user_id' => 1,
            'admin_name' => 'Administrator',
            'target_user_id' => 2,
            'target_username' => 'target',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'session_reference_hash' => hash('sha256', 'reference'),
            'status' => UserImpersonationAuditLog::STATUS_ACTIVE,
        ]);

        try {
            $log->delete();
            $this->fail('Deleting an impersonation audit log should be rejected.');
        } catch (\LogicException) {
            $this->assertDatabaseHas('user_impersonation_audit_logs', ['id' => $log->id]);
        }

        $log->admin_name = 'Tampered administrator';
        $this->expectException(\LogicException::class);
        $log->save();
    }

    public function test_a_user_without_an_impersonation_session_cannot_restore_an_administrator(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($user)
            ->post(route('users.impersonate.stop'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_expired_impersonation_sessions_are_logged_out_and_audited(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($administrator)
            ->post(route('users.impersonate.start', $target))
            ->assertRedirect('/dashboard');

        $this->travel((int) config('session.lifetime') + 1)->minutes();
        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(
            UserImpersonationAuditLog::STATUS_EXPIRED,
            UserImpersonationAuditLog::query()->sole()->status,
        );
    }

    public function test_normal_logout_ends_the_impersonation_audit_entry(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($administrator)
            ->post(route('users.impersonate.start', $target))
            ->assertRedirect('/dashboard');

        $this->post(route('logout'));

        $this->assertGuest();
        $this->assertSame(
            UserImpersonationAuditLog::STATUS_ENDED,
            UserImpersonationAuditLog::query()->sole()->status,
        );
    }

    public function test_admin_password_version_change_invalidates_impersonation_without_restoring_stale_auth(): void
    {
        $administrator = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($administrator)
            ->post(route('users.impersonate.start', $target))
            ->assertRedirect('/dashboard');

        $administrator->forceFill([
            'password_security_version' => (int) $administrator->password_security_version + 1,
        ])->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(
            UserImpersonationAuditLog::STATUS_EXPIRED,
            UserImpersonationAuditLog::query()->sole()->status,
        );
    }
}
