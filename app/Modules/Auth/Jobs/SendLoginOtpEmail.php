<?php

namespace App\Modules\Auth\Jobs;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendLoginOtpEmail
{
    use Dispatchable;

    private bool $handled = false;

    public function __construct(
        public int $userId,
        public string $otp,
        public Carbon $expiresAt,
    ) {}

    public function handle(): void
    {
        if ($this->handled) {
            return;
        }

        $this->handled = true;
        $user = User::query()->findOrFail($this->userId);

        try {
            Mail::send(new LoginOtpMail($user, $this->otp, $this->expiresAt));
        } catch (Throwable $exception) {
            Log::error('Unable to deliver login OTP email after the login response.', [
                'user_id' => $this->userId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
