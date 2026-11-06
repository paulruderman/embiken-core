<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use App\Support\CustomerDisplayTicketData;
use App\Support\DayStore;
use Carbon\CarbonImmutable;
use Embiken\BikeRentals\Http\Resources\LocationReservationResource;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShowTerminalAction extends Action
{
    use AuthorizesStaff;

    public function __construct(private CustomerDisplayTicketData $customerDisplayTickets) {}

    public string $commandSignature = 'terminal:show {--tenant=} {--date=}';

    /**
     * @return array<string, mixed>
     */
    public function handle(?string $scheduleDate = null, ?Device $device = null): array
    {
        $location = Location::query()->firstOrFail();
        $date = $scheduleDate === null
            ? CarbonImmutable::now($location->timezone)
            : CarbonImmutable::createFromFormat('!Y-m-d', $scheduleDate, $location->timezone);
        $snapshot = DayStore::forLocation($location, $date);
        $reservations = $snapshot['modules']['rentals']['reservations'] ?? [];
        $focusedReservationId = $device?->surface === DeviceSurface::Terminal
            ? $device->focused_reservation_id
            : null;

        $focusedReservationIsIncluded = false;
        foreach ($reservations as $reservationData) {
            if (($reservationData['id'] ?? null) === $focusedReservationId) {
                $focusedReservationIsIncluded = true;
                break;
            }
        }

        if ($focusedReservationId !== null && ! $focusedReservationIsIncluded) {
            $focusedReservation = Reservation::query()
                ->whereKey($focusedReservationId)
                ->where('location_id', $location->id)
                ->with(['customer', 'bikeReservations.product.bikeModel', 'bikeReservations.bike'])
                ->first();

            if ($focusedReservation !== null) {
                $reservations[] = LocationReservationResource::make($focusedReservation)->resolve();
            } else {
                $focusedReservationId = null;
            }
        }

        $customerDisplayTickets = $device?->surface === DeviceSurface::Terminal
            ? $this->customerDisplayTickets->forTerminal($device)
            : [];

        return [
            ...$snapshot,
            'bike_assignment_policy' => $location->bike_assignment_policy->value,
            'bikes' => $snapshot['modules']['fleet']['bikes'] ?? [],
            'reservations' => $reservations,
            'focused_reservation_id' => $focusedReservationId,
            'customer_display_device_ids' => array_keys($customerDisplayTickets),
            'customer_display_tickets' => $customerDisplayTickets,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function asController(Request $request): array
    {
        $data = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $device = $request->attributes->get('device');

        return $this->handle($data['date'] ?? null, $device instanceof Device ? $device : null);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function htmlResponse(array $snapshot): InertiaResponse
    {
        return Inertia::render('Terminal/Index', [
            ...$snapshot,
            'terminal_assistant_enabled' => config('services.terminal_assistant.driver') === 'ollama',
            'terminal_ui' => __('app.terminal_ui'),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->get('/terminal', static::class)->name('terminal');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $date = $command->option('date');
        $snapshot = $this->handle(is_string($date) && $date !== '' ? $date : null);
        $command->info('Bikes: '.count($snapshot['bikes']).', reservations: '.count($snapshot['reservations']));

        return self::SUCCESS;
    }
}
