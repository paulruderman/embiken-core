<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeSituation;
use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Exceptions\OccupancyUnavailable;
use App\Http\Resources\DayPatchResource;
use App\Models\Bike;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateWalkInReservationAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:walk-in {--tenant=} {--name=Walk-in} {--email=} {--phone=} {--bike=}';

    public function handle(
        string $name,
        string $email,
        string $phone,
        ?Bike $bike = null,
    ): Reservation {
        $location = Location::query()->firstOrFail();
        $availability = app(AllocateLineAction::class);

        $homeBike = $bike ?? Bike::query()
            ->with('variant')
            ->where('in_service', true)
            ->where('bike_situation_state', BikeSituation::Home)
            ->orderBy('bid')
            ->first();

        if ($homeBike === null) {
            throw ValidationException::withMessages(['bike_id' => 'No in-service home bike is free.']);
        }

        $homeBike->loadMissing('variant');

        return DB::transaction(function () use ($location, $availability, $homeBike, $name, $email, $phone): Reservation {
            $customer = Customer::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]);

            $startsAt = Carbon::now($location->timezone);
            $endsAt = $startsAt->copy()->addHours(2);

            $reservation = $location->reservations()->create([
                'customer_id' => $customer->id,
                'rental_package_id' => null,
                'channel' => ReservationChannel::Terminal,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'stage' => ReservationStage::Provisional,
                'owed' => 0,
                'paid' => 0,
                'expires_at' => now()->addMinutes((int) config('embiken.provisional_ttl_minutes')),
                'myrental_token' => Str::random(40),
            ]);

            try {
                $line = $availability($reservation, $homeBike->variant, $homeBike);
            } catch (OccupancyUnavailable $exception) {
                throw ValidationException::withMessages(['bike_id' => $exception->reason]);
            }

            $line->loadMissing('bike');
            $bike = $line->bike;

            if ($bike === null) {
                throw ValidationException::withMessages(['bike_id' => 'Assign a bike before walk-in prep.']);
            }

            $bike->bike_situation_state = BikeSituation::Prepping;
            $bike->bike_situation_reservation_id = $reservation->id;
            $bike->save();

            return $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel', 'bikeReservations.bike']);
        });
    }

    public function asController(Request $request): DayPatchResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'bike_id' => ['nullable', 'integer', 'exists:bikes,id'],
        ]);

        $reservation = $this->handle(
            $data['name'] ?? 'Walk-in',
            $data['email'] ?? 'walk-in-'.Str::lower(Str::random(8)).'@desk.local',
            $data['phone'] ?? '0000000000',
            isset($data['bike_id']) ? Bike::query()->findOrFail($data['bike_id']) : null,
        );

        $bikes = $reservation->bikeReservations->pluck('bike')->filter();

        return new DayPatchResource([
            'reservation' => $reservation,
            'bikes' => $bikes,
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/walk-in', static::class)->name('reservations.walk-in');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $reservation = $this->handle(
            (string) ($command->option('name') ?: 'Walk-in'),
            (string) ($command->option('email') ?: 'walk-in-'.Str::lower(Str::random(8)).'@desk.local'),
            (string) ($command->option('phone') ?: '0000000000'),
            $command->option('bike') ? Bike::query()->findOrFail($command->option('bike')) : null,
        );
        $command->info("Walk-in {$reservation->id}");

        return self::SUCCESS;
    }
}
