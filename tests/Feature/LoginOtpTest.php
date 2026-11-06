<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Support\SystemMaintenanceState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $maintenanceState = $this->createMock(SystemMaintenanceState::class);
        $maintenanceState->method('isEnabled')->willReturn(false);
        $this->instance(SystemMaintenanceState::class, $maintenanceState);
        Mail::fake();
        Queue::fake();
    }

    public function test_password_login_requires_an_otp_before_authenticating(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
            'remember_today' => '1',
        ])->assertRedirect(route('login.otp'));
        $this->assertGuest();
        Mail::assertNothingSent();

        Queue::assertNothingPushed();
        $this->get(route('login.otp'))->assertOk();
        $mail = Mail::sent(LoginOtpMail::class)->first();
        $this->assertSame($user->emailaddress, $mail->envelope()->to[0]->address);
        $this->assertSame(6, strlen($mail->otp));
        $this->assertTrue($mail->user->is($user));

        $this->post(route('login.otp.verify'), [
            'otp' => $mail->otp,
        ])->assertRedirect('/dashboard')
            ->assertCookie('daily_otp_verified');
        $this->assertAuthenticatedAs($user);
    }

    public function test_daily_trust_cookie_skips_the_otp_challenge(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();
        $userId = (string) $user->getKey();
        $signature = hash_hmac(
            'sha256',
            $userId.'|'.$date.'|'.$user->getAuthPassword(),
            (string) config('app.key'),
        );
        $cookieValue = $userId.'|'.$date.'|'.$signature;

        $this->withCookie('daily_otp_verified', $cookieValue)
            ->post(route('login'), [
                'username' => $user->username,
                'password' => 'password',
            ])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        Mail::assertNothingSent();
    }

    public function test_login_page_has_daily_remember_label_and_two_hour_web_session_lifetime(): void
    {
        $this->assertSame(120, (int) config('session.lifetime'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Remember me within the day')
            ->assertDontSee('Remember this browser today and skip OTP');
    }

    public function test_incorrect_otp_does_not_authenticate_and_is_limited(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), [
            'otp' => '000000',
        ])->assertSessionHasErrors('otp');
        $this->assertGuest();

        $this->assertTrue(session()->has('login_otp_challenge'));
    }

    public function test_repeated_password_submission_reuses_the_active_otp_and_resend_waits_five_minutes(): void
    {
        $user = User::factory()->create();

        $credentials = [
            'username' => $user->username,
            'password' => 'password',
        ];

        $this->post(route('login'), $credentials)->assertRedirect(route('login.otp'));
        $this->post(route('login'), $credentials)->assertRedirect(route('login.otp'));
        Mail::assertNothingSent();
        $this->get(route('login.otp'))->assertOk();
        $this->get(route('login.otp'))->assertOk();
        Queue::assertNothingPushed();
        $this->assertCount(1, Mail::sent(LoginOtpMail::class));

        $this->from(route('login.otp'))
            ->post(route('login.otp.resend'))
            ->assertRedirect(route('login.otp'))
            ->assertSessionHasErrors('resend');
        Queue::assertNothingPushed();
        $this->assertCount(1, Mail::sent(LoginOtpMail::class));

        $this->travel(5)->minutes();

        $this->post(route('login.otp.resend'))
            ->assertRedirect(route('login.otp'))
            ->assertSessionHas('otp_notice', 'A new verification code has been sent.');
        Queue::assertNothingPushed();
        $this->assertCount(2, Mail::sent(LoginOtpMail::class));
    }
}
