<p>Hello {{ $reservation->customer->name }},</p>

<p>Here is your private link to view your reservation at {{ $reservation->location->name }}.</p>

<p><a href="{{ $myRentalUrl }}">View your reservation</a></p>

<p>This private link works until {{ $reservation->ends_at->addDays(3)->toDayDateTimeString() }}.</p>
