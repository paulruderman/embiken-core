<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EarlyAccessInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public string $url, public string $mailLocale) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.early_access_email.invitation_subject', locale: $this->mailLocale))
            ->greeting(__('app.early_access_email.greeting', locale: $this->mailLocale))
            ->line(__('app.early_access_email.invitation_body', locale: $this->mailLocale))
            ->action(__('app.early_access_email.invitation_action', locale: $this->mailLocale), $this->url)
            ->line(__('app.early_access_email.invitation_expiry', ['days' => 14], locale: $this->mailLocale));
    }
}
