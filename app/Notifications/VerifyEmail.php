<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $minutes = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Confirm your Siberian Wine Garden email')
            ->greeting("Welcome to Siberian Wine Garden, {$notifiable->name}!")
            ->line('Thank you for creating an account. Confirm your email address to open your personal cellar and use marketplace features.')
            ->action('Confirm email address', $verificationUrl)
            ->line("This secure link expires in {$minutes} minutes.")
            ->line('If you did not create this account, you can safely ignore this message.');
    }
}
