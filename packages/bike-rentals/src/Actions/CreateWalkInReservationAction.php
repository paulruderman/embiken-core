<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\BikeAssignmentPolicy;
use App\Models\Location;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;

class CreateWalkInReservationAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:walk-in {--tenant=} {--name=} {--email=} {--phone=} {--bike=*} {--owed-cents=}';

    /**
     * @param  list<int>  $bikeIds
     */
    public function handle(
        string $name,
        string $email,
        string $phone,
        array $bikeIds,
        int $owedCents,
    ): Reservation {
        $location = Location::query()->firstOrFail();
        $availability = app(AllocateLineAction::class);
        $pickupOnly = $location->bike_assignment_policy === BikeAssignmentPolicy::PickupOnly;

        return DB::transaction(function () use ($location, $availability, $bikeIds, $name, $email, $phone, $owedCents, $pickupOnly): Reservation {
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
                'stage' => ReservationStage::Confirmed,
                'owed' => $owedCents,
                'paid' => 0,
                'expires_at' => null,
                'myrental_token' => Str::random(40),
            ]);

            foreach ($bikeIds as $index => $bikeId) {
                $bike = Bike::query()->with('variant')->findOrFail($bikeId);

                if (! $bike->in_service) {
                    throw ValidationException::withMessages(["bike_ids.{$index}" => 'Choose an in-service bike.']);
                }

                try {
                    $availability($reservation, $bike->variant, $pickupOnly ? null : $bike);
                } catch (OccupancyUnavailable $exception) {
                    throw ValidationException::withMessages(["bike_ids.{$index}" => $exception->reason]);
                }

                if (! $pickupOnly) {
                    $bike->bike_situation_state = BikeSituation::Prepping;
                    $bike->bike_situation_reservation_id = $reservation->id;
                    $bike->save();
                }
            }

            return $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel', 'bikeReservations.bike']);
        });
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'bike_ids' => ['required', 'array', 'min:1'],
            'bike_ids.*' => ['required', 'integer', 'distinct', 'exists:bikes,id'],
            'owed_cents' => ['required', 'integer', 'min:0'],
        ];
    }

    public function asController(ActionRequest $request): DayPatchResource
    {
        /** @var array{name: string, email: string, phone: string, bike_ids: list<int>, owed_cents: int} $data */
        $data = $request->validated();

        $reservation = $this->handle(
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['bike_ids'],
            $data['owed_cents'],
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

        $bikeOptions = $command->option('bike');
        $bikeInput = is_array($bikeOptions) && $bikeOptions !== []
            ? $bikeOptions
            : $this->requiredStringInput($command, is_string($bikeOptions) ? $bikeOptions : null, 'Bike IDs');
        $bikeIds = is_array($bikeInput)
            ? array_map('intval', $bikeInput)
            : array_map('intval', explode(',', $bikeInput));
        $owedInput = $this->requiredStringInput($command, $command->option('owed-cents'), 'Owed cents');

        $validated = Validator::make([
            'name' => $this->requiredStringInput($command, $command->option('name'), 'Name'),
            'email' => $this->requiredStringInput($command, $command->option('email'), 'Email'),
            'phone' => $this->requiredStringInput($command, $command->option('phone'), 'Phone'),
            'bike_ids' => $bikeIds,
            'owed_cents' => $owedInput,
        ], $this->rules())->validate();

        $reservation = $this->handle(
            $validated['name'],
            $validated['email'],
            $validated['phone'],
            $validated['bike_ids'],
            (int) $validated['owed_cents'],
        );
        $command->info("Walk-in {$reservation->id}");

        return self::SUCCESS;
    }
}
