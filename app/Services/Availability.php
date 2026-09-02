<?php

namespace App\Services;

use App\Enums\BikeReservationStatus;
use App\Enums\BikeSituation;
use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Exceptions\OccupancyUnavailable;
use App\Models\Bike;
use App\Models\BikeModelVariant;
use App\Models\BikeReservation;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\ServiceRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class Availability
{
    public function allocate(
        Reservation $reservation,
        BikeModelVariant $product,
        ReservationChannel $channel,
        ?Bike $bike = null,
        ?string $riderName = null,
        ?int $riderHeightCm = null,
    ): BikeReservation {
        return DB::transaction(function () use ($reservation, $product, $channel, $bike, $riderName, $riderHeightCm): BikeReservation {
            $this->assertPackageMembership($reservation, $product);
            $this->assertIntervalFree($reservation, $reservation->starts_at, $reservation->ends_at, $channel, $product, $bike);

            if ($bike !== null) {
                $this->assertBikeAssignable($bike, $product, $channel);
            }

            $line = new BikeReservation;
            $line->reservation()->associate($reservation);
            $line->product()->associate($product);
            $line->bike()->associate($bike);
            $line->status = BikeReservationStatus::Assigned;
            $line->rider_name = $riderName;
            $line->rider_height_cm = $riderHeightCm;
            $line->save();

            $this->bumpExpiresAt($reservation);

            return $line;
        });
    }

    public function release(BikeReservation $line): void
    {
        DB::transaction(function () use ($line): void {
            $line->refresh();

            if ($line->status === BikeReservationStatus::Out) {
                throw new OccupancyUnavailable('An Out line cannot be released. Return it first.', $line->id);
            }

            $reservation = $line->reservation;
            $bike = $line->bike;

            $line->delete();

            if ($bike !== null && $bike->bike_situation_reservation_id === $reservation->id) {
                $bike->bike_situation_state = BikeSituation::Home;
                $bike->bike_situation_reservation_id = null;
                $bike->save();
            }

            $this->bumpExpiresAt($reservation);
        });
    }

    public function assertIntervalFree(
        Reservation $reservation,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ReservationChannel $channel,
        ?BikeModelVariant $product = null,
        ?Bike $bike = null,
        ?BikeReservation $ignoreLine = null,
    ): void {
        $honorBuffer = $channel === ReservationChannel::Book;

        if ($bike !== null) {
            $this->assertBikeInterval($bike, $startsAt, $endsAt, $honorBuffer, $reservation->id, $ignoreLine?->id);

            return;
        }

        if ($product === null) {
            $reservation->loadMissing('bikeReservations.product', 'bikeReservations.bike');

            foreach ($reservation->bikeReservations as $line) {
                $this->assertIntervalFree(
                    $reservation,
                    $startsAt,
                    $endsAt,
                    $channel,
                    $line->product,
                    $line->bike,
                    $line,
                );
            }

            return;
        }

        $this->assertClassCapacity($product, $startsAt, $endsAt, $honorBuffer, $channel, $reservation->id, $ignoreLine?->id);
    }

    private function assertPackageMembership(Reservation $reservation, BikeModelVariant $product): void
    {
        if ($reservation->rental_package_id === null) {
            return;
        }

        $onOffer = $reservation->rentalPackage?->variants()->whereKey($product->id)->exists() ?? false;

        if (! $onOffer) {
            throw new OccupancyUnavailable('That variant is not on the package.');
        }
    }

    private function assertBikeAssignable(Bike $bike, BikeModelVariant $product, ReservationChannel $channel): void
    {
        if ($bike->bike_model_variant_id !== $product->id) {
            throw new OccupancyUnavailable('That bike is not this variant.');
        }

        if (! $bike->in_service) {
            throw new OccupancyUnavailable('That bike is parked.');
        }

        if ($channel === ReservationChannel::Book && ! $bike->self_bookable) {
            throw new OccupancyUnavailable('That bike is not self-bookable.');
        }

        if ($this->bikeHasBlockingService($bike)) {
            throw new OccupancyUnavailable('That bike has blocking service.');
        }
    }

    private function assertBikeInterval(
        Bike $bike,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        bool $honorBuffer,
        int $ignoreReservationId,
        ?int $ignoreLineId,
    ): void {
        $cancelledOut = BikeReservation::query()
            ->where('bike_id', $bike->id)
            ->where('status', BikeReservationStatus::Out)
            ->whereHas('reservation', fn ($builder) => $builder->where('stage', ReservationStage::Cancelled))
            ->exists();

        if ($cancelledOut) {
            throw new OccupancyUnavailable('That bike is still Out on a Cancelled reservation.');
        }

        $buffer = $honorBuffer ? $this->effectiveBufferMinutes($bike) : 0;

        $conflict = BikeReservation::query()
            ->with('reservation')
            ->where('bike_id', $bike->id)
            ->when($ignoreLineId, fn ($builder) => $builder->whereKeyNot($ignoreLineId))
            ->whereHas('reservation', fn ($builder) => $builder
                ->whereKeyNot($ignoreReservationId)
                ->where('stage', '!=', ReservationStage::Cancelled))
            ->get()
            ->contains(function (BikeReservation $line) use ($startsAt, $endsAt, $buffer): bool {
                $occupiedUntil = $line->reservation->ends_at->copy()->addMinutes($buffer);

                return $line->reservation->starts_at->lte($endsAt) && $occupiedUntil->gte($startsAt);
            });

        if ($conflict) {
            throw new OccupancyUnavailable('That interval overlaps another reservation.');
        }
    }

    private function assertClassCapacity(
        BikeModelVariant $product,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        bool $honorBuffer,
        ReservationChannel $channel,
        int $ignoreReservationId,
        ?int $ignoreLineId,
    ): void {
        $fleetCount = Bike::query()
            ->where('bike_model_variant_id', $product->id)
            ->where('in_service', true)
            ->when($channel === ReservationChannel::Book, fn ($builder) => $builder->where('self_bookable', true))
            ->get()
            ->reject(fn (Bike $bike): bool => $this->bikeHasBlockingService($bike))
            ->count();

        $buffer = $honorBuffer ? $this->effectiveBufferMinutesForProduct($product) : 0;

        $held = BikeReservation::query()
            ->with('reservation')
            ->where('product_id', $product->id)
            ->when($ignoreLineId, fn ($builder) => $builder->whereKeyNot($ignoreLineId))
            ->whereHas('reservation', fn ($builder) => $builder
                ->whereKeyNot($ignoreReservationId)
                ->where('stage', '!=', ReservationStage::Cancelled))
            ->get()
            ->filter(function (BikeReservation $line) use ($startsAt, $endsAt, $buffer): bool {
                $occupiedUntil = $line->reservation->ends_at->copy()->addMinutes($buffer);

                return $line->reservation->starts_at->lte($endsAt) && $occupiedUntil->gte($startsAt);
            })
            ->count();

        if ($held >= $fleetCount) {
            throw new OccupancyUnavailable('No remaining bikes of that variant for the interval.');
        }
    }

    private function bikeHasBlockingService(Bike $bike): bool
    {
        return ServiceRequest::query()
            ->where('bike_id', $bike->id)
            ->where('blocks_usage', true)
            ->get()
            ->contains(fn (ServiceRequest $ticket): bool => $ticket->stage->occupiesWhenBlocking());
    }

    private function effectiveBufferMinutes(Bike $bike): int
    {
        $locationMinimum = $bike->location->minimum_turnaround_buffer_minutes;
        $padding = $bike->variant->bikeModel->padding_minutes ?? 0;

        return max($locationMinimum, $padding);
    }

    private function effectiveBufferMinutesForProduct(BikeModelVariant $product): int
    {
        $locationMinimum = (int) Location::query()->value('minimum_turnaround_buffer_minutes');
        $padding = $product->bikeModel->padding_minutes ?? 0;

        return max($locationMinimum, $padding);
    }

    private function bumpExpiresAt(Reservation $reservation): void
    {
        if ($reservation->stage !== ReservationStage::Provisional) {
            return;
        }

        $reservation->expires_at = now()->addMinutes((int) config('embiken.provisional_ttl_minutes'));
        $reservation->save();
    }
}
