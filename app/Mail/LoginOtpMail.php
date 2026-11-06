<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
        public Carbon $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: [$this->user->emailaddress],
            subject: 'Your PRISM login verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-otp',
            with: [
                'user' => $this->user,
                'otp' => $this->otp,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
