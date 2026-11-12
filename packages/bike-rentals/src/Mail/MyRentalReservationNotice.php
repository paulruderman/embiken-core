<?php

namespace Embiken\BikeRentals\Mail;

use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MyRentalReservationNotice extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $myRentalUrl,
        public string $notice = 'Reservation confirmed',
    ) {}

    public function envelope(): Envelope
    {
        $location = $this->reservation->location;

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $location->name ?: (string) config('mail.from.name'),
            ),
            replyTo: filled($location->customer_contact_email)
                ? [new Address($location->customer_contact_email, $location->name)]
                : [],
            subject: $this->notice,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bike-rentals::mail.my-rental-reservation-notice',
            text: 'bike-rentals::mail.my-rental-reservation-notice-text',
        );
    }
}
