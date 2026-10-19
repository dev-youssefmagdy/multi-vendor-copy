<?php

declare(strict_types=1);

namespace App\Notifications\Tenant;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetAdminPasswordNotification extends Notification
{
    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('tenant.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset your vendor panel password')
            ->line('We received a request to reset the password for your vendor panel account.')
            ->action('Reset password', $url)
            ->line('This link expires in 60 minutes. If you did not request it, you can ignore this email.');
    }
}
