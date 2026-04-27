<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the channels the notification should be delivered on.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        // Use token-based verification URL
        return url('/email/verify/token/' . $notifiable->verification_token);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Verify Your Email Address - PRISM')
            ->greeting('Hello ' . $notifiable->fname . ' ' . $notifiable->lname . '!')
            ->line('Thank you for registering with PRISM.')
            ->line('Your account has been successfully created. Please verify your email address by clicking the button below.')
            ->line('After verification, your account will remain pending until an administrator approves it.')
            ->action('Verify Email Address', $verificationUrl, '#ffffff')
            ->line('This verification link will expire in 60 minutes.')
            ->line('If you did not create an account, no further action is required.')
            ->line('If the button above does not work, copy and paste this URL into your web browser:')
            ->line($verificationUrl)
            ->salutation('Best regards,')
            ->from(config('mail.from.address'), config('mail.from.name'));
    }
}
