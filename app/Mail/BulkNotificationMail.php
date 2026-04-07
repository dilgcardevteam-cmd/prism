<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public string $titleText,
        public string $messageText,
        public string $actionUrl,
        public string $senderName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address'),
            subject: 'Bulk Notification - ' . $this->titleText,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bulk-notification',
            with: [
                'recipient' => $this->recipient,
                'titleText' => $this->titleText,
                'messageText' => $this->messageText,
                'actionUrl' => $this->actionUrl,
                'senderName' => $this->senderName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
