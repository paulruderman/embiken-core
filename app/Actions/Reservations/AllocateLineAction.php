<?php

namespace App\Actions\Reservations;

use App\Actions\Action;
use App\Enums\ReservationChannel;
use App\Models\Bike;
use App\Models\BikeModelVariant;
use App\Models\BikeReservation;
use App\Models\Reservation;
use App\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllocateLineAction extends Action
{
    public string $commandSignature = 'reservations:allocate-line {reservation?} {product?} {--tenant=} {--bike=} {--rider-name=} {--rider-height=}';

    public function handle(
        Reservation $reservation,
        BikeModelVariant $product,
        ?Bike $bike = null,
        ?string $riderName = null,
        ?int $riderHeightCm = null,
    ): BikeReservation {
        return app(Availability::class)->allocate(
            $reservation,
            $product,
            ReservationChannel::Terminal,
            $bike,
            $riderName,
            $riderHeightCm,
        );
    }

    public function asController(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:bike_model_variants,id'],
            'bike_id' => ['nullable', 'integer', 'exists:bikes,id'],
            'rider_name' => ['nullable', 'string', 'max:255'],
            'rider_height_cm' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json($this->handle(
            $reservation,
            BikeModelVariant::query()->findOrFail($data['product_id']),
            isset($data['bike_id']) ? Bike::query()->findOrFail($data['bike_id']) : null,
            $data['rider_name'] ?? null,
            isset($data['rider_height_cm']) ? (int) $data['rider_height_cm'] : null,
        ), 201);
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $line = $this->handle(
            Reservation::query()->findOrFail($command->argument('reservation') ?: \Laravel\Prompts\text('Reservation id')),
            BikeModelVariant::query()->findOrFail($command->argument('product') ?: \Laravel\Prompts\text('Variant id')),
            $command->option('bike') ? Bike::query()->findOrFail($command->option('bike')) : null,
            $command->option('rider-name') ?: null,
            $command->option('rider-height') !== null ? (int) $command->option('rider-height') : null,
        );
        $command->info("Allocated line {$line->id}");

        return self::SUCCESS;
    }
}
