Hello {{ $reservation->customer->name }},

Here is your private link to view your reservation at {{ $reservation->location->name }}.

View your reservation: {{ $myRentalUrl }}

This private link works until {{ $reservation->ends_at->addDays(3)->toDayDateTimeString() }}.
