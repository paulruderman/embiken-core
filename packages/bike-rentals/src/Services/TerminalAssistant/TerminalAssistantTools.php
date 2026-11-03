<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Services\TerminalAssistant;

use App\Models\Location;
use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Enums\BikeSituation;
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
                    'name' => 'summarize_shop_day',
                    'description' => 'For shop-wide questions about today, return the reservation count, reservation starts by local hour, and current fleet situation and service availability counts. Report facts; do not classify the shop as busy or quiet.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_todays_reservations',
                    'description' => 'List Reservations intersecting today in the shop timezone. Contact details are never returned.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass,
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
            'summarize_shop_day' => $this->summarizeShopDay(),
            'list_todays_reservations' => $this->listTodaysReservations(),
            'get_reservation' => $this->getReservation($arguments),
            'check_bike_availability' => $this->checkBikeAvailability($arguments),
            default => throw ValidationException::withMessages(['message' => 'That assistant tool is not available.']),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeShopDay(): array
    {
        $location = Location::query()->firstOrFail();
        $checkedAt = CarbonImmutable::now($location->timezone);
        $dayStart = $checkedAt->startOfDay();
        $dayEnd = $dayStart->addDay();

        $reservations = Reservation::query()
            ->withCount('bikeReservations')
            ->where('starts_at', '<', $dayEnd->utc())
            ->where('ends_at', '>=', $dayStart->utc())
            ->orderBy('starts_at')
            ->get(['id', 'stage', 'starts_at', 'ends_at']);

        $reservationLineCount = 0;
        $reservationsStartingByHour = [];
        $schedule = [];

        foreach ($reservations as $reservation) {
            $lineCount = (int) $reservation->getAttribute('bike_reservations_count');
            $reservationLineCount += $lineCount;
            $startsAtLocal = $reservation->starts_at->setTimezone($location->timezone);
            $endsAtLocal = $reservation->ends_at->setTimezone($location->timezone);

            $schedule[] = [
                'starts_at_local' => $startsAtLocal->format('Y-m-d H:i'),
                'ends_at_local' => $endsAtLocal->format('Y-m-d H:i'),
                'stage' => $reservation->stage->value,
                'bike_line_count' => $lineCount,
            ];

            if ($startsAtLocal->lt($dayStart) || $startsAtLocal->gte($dayEnd)) {
                continue;
            }

            $hour = $startsAtLocal->format('H:00');
            $reservationsStartingByHour[$hour] ??= [
                'hour_local' => $hour,
                'reservation_count' => 0,
                'bike_line_count' => 0,
            ];
            $reservationsStartingByHour[$hour]['reservation_count']++;
            $reservationsStartingByHour[$hour]['bike_line_count'] += $lineCount;
        }

        $activeBlockingRequests = ServiceRequest::query()
            ->where('blocks_usage', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $checkedAt->utc()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $checkedAt->utc()))
            ->get(['bike_id', 'stage'])
            ->filter(fn (ServiceRequest $request): bool => $request->stage->occupiesWhenBlocking());

        $blockingServiceStagesByBike = [];

        foreach ($activeBlockingRequests as $request) {
            $blockingServiceStagesByBike[(int) $request->bike_id][] = $request->stage->value;
        }

        $bikes = Bike::query()
            ->with('variant.bikeModel')
            ->orderBy('bid')
            ->get();
        $situationCounts = array_fill_keys(array_map(
            fn (BikeSituation $situation): string => $situation->value,
            BikeSituation::cases(),
        ), 0);
        $inServiceBikeCount = 0;
        $outOfServiceBikeCount = 0;
        $affectedBikes = [];

        foreach ($bikes as $bike) {
            $situationCounts[$bike->bike_situation_state->value]++;
            $inServiceBikeCount += (int) $bike->in_service;
            $outOfServiceBikeCount += (int) ! $bike->in_service;
            $blockingStages = $blockingServiceStagesByBike[(int) $bike->getKey()] ?? [];

            if ($bike->in_service && $blockingStages === []) {
                continue;
            }

            $affectedBikes[] = [
                'bid' => $bike->bid,
                'model' => $bike->variant->bikeModel->name,
                'variant' => $bike->variant->size,
                'in_service' => $bike->in_service,
                'blocking_service_stages' => array_values(array_unique($blockingStages)),
            ];
        }

        return [
            'date' => $dayStart->toDateString(),
            'timezone' => $location->timezone,
            'checked_at_local' => $checkedAt->format('Y-m-d H:i'),
            'reservations' => [
                'intersecting_today_count' => $reservations->count(),
                'bike_line_count' => $reservationLineCount,
                'starts_by_hour' => array_values($reservationsStartingByHour),
                'schedule' => $schedule,
            ],
            'fleet' => [
                'bike_count' => $bikes->count(),
                'in_service_bike_count' => $inServiceBikeCount,
                'out_of_service_bike_count' => $outOfServiceBikeCount,
                'blocking_service_bike_count' => count($blockingServiceStagesByBike),
                'unavailable_bike_count' => count($affectedBikes),
                'situation_counts' => $situationCounts,
                'affected_bikes' => $affectedBikes,
            ],
        ];
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
            'reservations' => array_values($reservations->map(
                fn (Reservation $reservation): array => $this->reservationSummary($reservation, $location),
            )->all()),
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

        if ($startsAt === null || $endsAt === null || ! $endsAt->greaterThan($startsAt)) {
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
        return array_values($lines->map(fn (BikeReservation $line): array => [
            'model' => $line->product?->bikeModel?->name,
            'variant' => $line->product?->size,
            'bid' => $line->bike?->bid,
            'line_status' => $line->status->value,
            'bike_situation' => $line->bike?->bike_situation_state?->value,
        ])->all());
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
