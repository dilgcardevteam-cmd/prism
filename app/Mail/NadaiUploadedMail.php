<?php

namespace App\Mail;

use App\Models\NadaiManagementDocument;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NadaiUploadedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public NadaiManagementDocument $document,
        public string $officeName,
        public string $province,
        public string $actionUrl,
        public string $senderName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'Received Notice of Authority to Debit Account Issued - ' . $this->officeName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nadai-uploaded',
            with: [
                'recipient' => $this->recipient,
                'document' => $this->document,
                'officeName' => $this->officeName,
                'province' => $this->province,
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
