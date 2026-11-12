<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EarlyAccessReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(public string $mailLocale) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.early_access_email.receipt_subject', locale: $this->mailLocale))
            ->greeting(__('app.early_access_email.greeting', locale: $this->mailLocale))
            ->line(__('app.early_access_email.receipt_body', locale: $this->mailLocale))
            ->line(__('app.early_access_email.no_account', locale: $this->mailLocale));
    }
}
