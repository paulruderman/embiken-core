<?php

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Http\Resources\DayPatchResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;

class AllocateLineAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'reservations:allocate-line {reservation?} {product?} {--tenant=} {--bike=} {--rider-name=} {--rider-height=}';

    public function handle(
        Reservation $reservation,
        ?BikeModelVariant $product = null,
        ?Bike $bike = null,
        ?string $riderName = null,
        ?int $riderHeightCm = null,
    ): BikeReservation {
        $bike?->loadMissing('variant');
        $product ??= $bike?->variant;

        if ($product === null) {
            throw ValidationException::withMessages(['product_id' => 'Allocate needs a variant or a bike.']);
        }

        return app(Availability::class)->allocate(
            $reservation,
            $product,
            ReservationChannel::Terminal,
            $bike,
            $riderName,
            $riderHeightCm,
        );
    }

    public function asController(Request $request, Reservation $reservation): DayPatchResource
    {
        $data = $request->validate([
            'product_id' => ['required_without:bike_id', 'nullable', 'integer', 'exists:bike_model_variants,id'],
            'bike_id' => ['required_without:product_id', 'nullable', 'integer', 'exists:bikes,id'],
            'rider_name' => ['nullable', 'string', 'max:255'],
            'rider_height_cm' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $line = $this->handle(
                $reservation,
                isset($data['product_id']) ? BikeModelVariant::query()->findOrFail($data['product_id']) : null,
                isset($data['bike_id']) ? Bike::query()->findOrFail($data['bike_id']) : null,
                $data['rider_name'] ?? null,
                isset($data['rider_height_cm']) ? (int) $data['rider_height_cm'] : null,
            );
        } catch (OccupancyUnavailable $exception) {
            throw ValidationException::withMessages(['product_id' => $exception->reason]);
        }

        $reservation->refresh()->load(['customer', 'bikeReservations.product.bikeModel']);

        return new DayPatchResource([
            'reservation' => $reservation,
            'bikes' => array_filter([$line->bike]),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/reservations/{reservation}/lines', static::class)->name('reservations.allocate-line');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $bike = $command->option('bike') ? Bike::query()->findOrFail($command->option('bike')) : null;
        $productArgument = $command->argument('product');
        $line = $this->handle(
            Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            $productArgument
                ? BikeModelVariant::query()->findOrFail($productArgument)
                : ($bike === null ? BikeModelVariant::query()->findOrFail(\Laravel\Prompts\text('Variant id')) : null),
            $bike,
            $command->option('rider-name') ?: null,
            $command->option('rider-height') !== null ? (int) $command->option('rider-height') : null,
        );
        $command->info("Allocated line {$line->id}");

        return self::SUCCESS;
    }
}
