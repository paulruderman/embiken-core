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
use Embiken\BikeRentals\Mail\MyRentalReservationNotice;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Embiken\BikeRentals\Services\MyRentalLink;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;

class CreateWalkInReservationAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:walk-in {--tenant=} {--name=} {--email=} {--phone=} {--bike=*} {--rider-height=*} {--owed-minor-units=} {--starts-at=} {--ends-at=} {--allow-availability-override}';

    /**
     * @param  list<int>  $bikeIds
     * @param  list<int|null>  $riderHeightsCm
     * @param  list<string|null>  $riderNames
     */
    public function handle(
        string $name,
        string $email,
        string $phone,
        array $bikeIds,
        int $owedMinorUnits,
        string $startsAtLocal,
        string $endsAtLocal,
        array $riderHeightsCm = [],
        array $riderNames = [],
        bool $allowAvailabilityOverride = false,
    ): Reservation {
        if ($riderHeightsCm !== [] && count($riderHeightsCm) !== count($bikeIds)) {
            throw ValidationException::withMessages([
                'rider_heights_cm' => 'Provide one rider height for each selected bike.',
            ]);
        }

        if ($riderNames !== [] && count($riderNames) !== count($bikeIds)) {
            throw ValidationException::withMessages([
                'rider_names' => 'Provide one rider name for each selected bike.',
            ]);
        }

        $location = Location::query()->firstOrFail();
        $availability = app(Availability::class);
        $pickupOnly = $location->bike_assignment_policy === BikeAssignmentPolicy::PickupOnly;

        $reservation = DB::transaction(function () use ($location, $availability, $bikeIds, $name, $email, $phone, $owedMinorUnits, $startsAtLocal, $endsAtLocal, $riderHeightsCm, $riderNames, $allowAvailabilityOverride, $pickupOnly): Reservation {
            $customer = Customer::query()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]);

            $startsAt = Carbon::parse($startsAtLocal, $location->timezone)->utc();
            $endsAt = Carbon::parse($endsAtLocal, $location->timezone)->utc();

            $reservation = $location->reservations()->create([
                'customer_id' => $customer->id,
                'rental_package_id' => null,
                'channel' => ReservationChannel::Terminal,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'stage' => ReservationStage::Confirmed,
                'owed' => $owedMinorUnits,
                'paid' => 0,
                'currency' => $location->currency,
                'expires_at' => null,
                'myrental_token' => Str::random(40),
            ]);

            foreach ($bikeIds as $index => $bikeId) {
                $bike = Bike::query()->with('variant')->findOrFail($bikeId);

                try {
                    if ($pickupOnly) {
                        $availability->assertIntervalFree(
                            $reservation,
                            $reservation->starts_at,
                            $reservation->ends_at,
                            ReservationChannel::Terminal,
                            $bike->variant,
                            $bike,
                            allowOverride: $allowAvailabilityOverride,
                        );
                    }

                    $availability->allocate(
                        $reservation,
                        $bike->variant,
                        ReservationChannel::Terminal,
                        $pickupOnly ? null : $bike,
                        $riderNames[$index] ?? null,
                        $riderHeightsCm[$index] ?? null,
                        $allowAvailabilityOverride,
                    );
                } catch (OccupancyUnavailable $exception) {
                    throw ValidationException::withMessages(["bike_ids.{$index}" => $exception->reason]);
                }

                if (
                    ! $pickupOnly
                    && ! $allowAvailabilityOverride
                    && $bike->in_service
                    && $bike->bike_situation_state === BikeSituation::Home
                    && $bike->bike_situation_reservation_id === null
                ) {
                    $bike->bike_situation_state = BikeSituation::Prepping;
                    $bike->bike_situation_reservation_id = $reservation->id;
                    $bike->save();
                }
            }

            return $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel', 'bikeReservations.bike']);
        });

        $reservation->load(['customer', 'location']);
        $url = app(MyRentalLink::class)->for($reservation);

        if ($url !== null) {
            Mail::to($reservation->customer->email)->queue(
                (new MyRentalReservationNotice($reservation, $url))->afterCommit(),
            );
        }

        return $reservation;
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
            'rider_heights_cm' => ['sometimes', 'array'],
            'rider_heights_cm.*' => ['nullable', 'integer', 'min:0', 'max:250'],
            'rider_names' => ['sometimes', 'array'],
            'rider_names.*' => ['nullable', 'string', 'max:255'],
            'allow_availability_override' => ['sometimes', 'boolean'],
            'confirm_availability_override' => ['sometimes', 'accepted'],
            'owed_minor_units' => ['required', 'integer', 'min:0'],
            'starts_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at_local' => ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at_local'],
        ];
    }

    public function asController(ActionRequest $request): DayPatchResource
    {
        /** @var array{name: string, email: string, phone: string, bike_ids: list<int>, rider_heights_cm?: list<int|null>, rider_names?: list<string|null>, allow_availability_override?: bool, confirm_availability_override?: bool, owed_minor_units: int, starts_at_local: string, ends_at_local: string} $data */
        $data = $request->validated();
        $allowAvailabilityOverride = (bool) ($data['allow_availability_override'] ?? false);

        if ($allowAvailabilityOverride && ! ($data['confirm_availability_override'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_availability_override' => 'Confirm that you understand this override may cause overlapping reservations or select a bike that is not ready.',
            ]);
        }

        $reservation = $this->handle(
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['bike_ids'],
            $data['owed_minor_units'],
            $data['starts_at_local'],
            $data['ends_at_local'],
            $data['rider_heights_cm'] ?? [],
            $data['rider_names'] ?? [],
            $allowAvailabilityOverride,
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
        $riderHeightOptions = $command->option('rider-height');
        $riderHeightsCm = is_array($riderHeightOptions) && $riderHeightOptions !== []
            ? array_map('intval', $riderHeightOptions)
            : [];
        $allowAvailabilityOverride = (bool) $command->option('allow-availability-override');

        if ($allowAvailabilityOverride) {
            if ($command->option('no-interaction')) {
                throw new \RuntimeException('An availability override requires interactive confirmation.');
            }

            if (! \Laravel\Prompts\confirm('Extreme override: this may double-book a bike or assign one that is not physically ready. Continue?', default: false)) {
                return self::FAILURE;
            }
        }
        $owedInput = $this->requiredStringInput($command, $command->option('owed-minor-units'), 'Owed minor units');
        $startsAtInput = $this->requiredStringInput($command, $command->option('starts-at'), 'Start time in the shop timezone');
        $endsAtInput = $this->requiredStringInput($command, $command->option('ends-at'), 'End time in the shop timezone');

        $validated = Validator::make([
            'name' => $this->requiredStringInput($command, $command->option('name'), 'Name'),
            'email' => $this->requiredStringInput($command, $command->option('email'), 'Email'),
            'phone' => $this->requiredStringInput($command, $command->option('phone'), 'Phone'),
            'bike_ids' => $bikeIds,
            'rider_heights_cm' => $riderHeightsCm,
            'owed_minor_units' => $owedInput,
            'starts_at_local' => $startsAtInput,
            'ends_at_local' => $endsAtInput,
        ], $this->rules())->validate();

        $reservation = $this->handle(
            $validated['name'],
            $validated['email'],
            $validated['phone'],
            $validated['bike_ids'],
            (int) $validated['owed_minor_units'],
            $validated['starts_at_local'],
            $validated['ends_at_local'],
            $validated['rider_heights_cm'] ?? [],
            [],
            $allowAvailabilityOverride,
        );
        $command->info("Walk-in {$reservation->id}");

        return self::SUCCESS;
    }
}
