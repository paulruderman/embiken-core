<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

use App\Models\Location;
use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Actions\BrowseTerminalBikeCandidatesAction;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TerminalAssistantTools
{
    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_todays_reservations',
                    'description' => 'List Reservations intersecting today in the shop timezone. Contact details are never returned.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_reservation',
                    'description' => 'Get one Reservation by its public reference. Contact details and private access tokens are never returned.',
                    'parameters' => [
                        'type' => 'object',
                        'required' => ['reference'],
                        'properties' => [
                            'reference' => ['type' => 'string', 'description' => 'The public Reservation reference shown in Terminal.'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'check_bike_availability',
                    'description' => 'Check available Terminal bikes for a local shop-time interval. This tool never assigns a bike.',
                    'parameters' => [
                        'type' => 'object',
                        'required' => ['starts_at_local', 'ends_at_local'],
                        'properties' => [
                            'starts_at_local' => ['type' => 'string', 'description' => 'Shop local time in YYYY-MM-DDTHH:mm format.'],
                            'ends_at_local' => ['type' => 'string', 'description' => 'Shop local time in YYYY-MM-DDTHH:mm format.'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(string $name, array $arguments): array
    {
        return match ($name) {
            'list_todays_reservations' => $this->listTodaysReservations(),
            'get_reservation' => $this->getReservation($arguments),
            'check_bike_availability' => $this->checkBikeAvailability($arguments),
            default => throw ValidationException::withMessages(['message' => 'That assistant tool is not available.']),
        };
    }

    /**
     * @return array{date: string, timezone: string, reservations: list<array<string, mixed>>}
     */
    private function listTodaysReservations(): array
    {
        $location = Location::query()->firstOrFail();
        $dayStart = CarbonImmutable::now($location->timezone)->startOfDay();
        $dayEnd = $dayStart->addDay();

        $reservations = Reservation::query()
            ->with(['bikeReservations.product.bikeModel', 'bikeReservations.bike'])
            ->where(function ($query) use ($dayStart, $dayEnd): void {
                $query->where('starts_at', '<', $dayEnd->utc())
                    ->where('ends_at', '>=', $dayStart->utc());
            })
            ->orderBy('starts_at')
            ->limit(30)
            ->get();

        return [
            'date' => $dayStart->toDateString(),
            'timezone' => $location->timezone,
            'reservations' => $reservations->map(fn (Reservation $reservation): array => $this->reservationSummary($reservation, $location))->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function getReservation(array $arguments): array
    {
        $reference = $arguments['reference'] ?? null;

        if (! is_string($reference) || trim($reference) === '') {
            throw ValidationException::withMessages(['reference' => 'Provide a Reservation reference.']);
        }

        $location = Location::query()->firstOrFail();
        $reservation = Reservation::query()
            ->with(['bikeReservations.product.bikeModel', 'bikeReservations.bike'])
            ->where('public_id', trim($reference))
            ->firstOrFail();

        return $this->reservationSummary($reservation, $location);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function checkBikeAvailability(array $arguments): array
    {
        $startsAtLocal = $arguments['starts_at_local'] ?? null;
        $endsAtLocal = $arguments['ends_at_local'] ?? null;

        if (! is_string($startsAtLocal) || ! is_string($endsAtLocal)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $startsAtLocal)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $endsAtLocal)) {
            throw ValidationException::withMessages(['interval' => 'Provide start and end in YYYY-MM-DDTHH:mm shop time.']);
        }

        $location = Location::query()->firstOrFail();
        $startsAt = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $startsAtLocal, $location->timezone);
        $endsAt = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $endsAtLocal, $location->timezone);

        if ($startsAt === false || $endsAt === false || ! $endsAt->greaterThan($startsAt)) {
            throw ValidationException::withMessages(['interval' => 'The end must be after the start.']);
        }

        $candidates = app(BrowseTerminalBikeCandidatesAction::class)->handle(
            $startsAt->format('Y-m-d\TH:i'),
            $endsAt->format('Y-m-d\TH:i'),
        );

        return [
            'starts_at_local' => $startsAt->format('Y-m-d\TH:i'),
            'ends_at_local' => $endsAt->format('Y-m-d\TH:i'),
            'timezone' => $location->timezone,
            'bikes' => $candidates['candidates']->map(fn (array $candidate): array => $this->candidateSummary($candidate))->all(),
        ];
    }

    /**
     * @param  Collection<int, BikeReservation>  $lines
     * @return list<array<string, mixed>>
     */
    private function reservationLines(Collection $lines): array
    {
        return $lines->map(fn (BikeReservation $line): array => [
            'model' => $line->product?->bikeModel?->name,
            'variant' => $line->product?->size,
            'bid' => $line->bike?->bid,
            'line_status' => $line->status->value,
            'bike_situation' => $line->bike?->bike_situation_state?->value,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function reservationSummary(Reservation $reservation, Location $location): array
    {
        return [
            'reference' => $reservation->public_id,
            'stage' => $reservation->stage->value,
            'starts_at_local' => $reservation->starts_at->setTimezone($location->timezone)->format('Y-m-d H:i'),
            'ends_at_local' => $reservation->ends_at->setTimezone($location->timezone)->format('Y-m-d H:i'),
            'owed_minor_units' => $reservation->owed,
            'paid_minor_units' => $reservation->paid,
            'currency' => strtolower($reservation->currency),
            'lines' => $this->reservationLines($reservation->bikeReservations),
        ];
    }

    /**
     * @param  array{bike: Bike, conflict_reason: string|null}  $candidate
     * @return array<string, mixed>
     */
    private function candidateSummary(array $candidate): array
    {
        $bike = $candidate['bike'];

        return [
            'bid' => $bike->bid,
            'model' => $bike->variant?->bikeModel?->name,
            'variant' => $bike->variant?->size,
            'availability' => $candidate['conflict_reason'] ?? 'available',
        ];
    }
}
