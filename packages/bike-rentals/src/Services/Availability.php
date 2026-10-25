<?php

namespace Embiken\BikeRentals\Services;

use App\Models\Location;
use Carbon\CarbonInterface;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

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
            $product = $this->lockProduct($product);
            $this->healStaleOutLines($product);
            $this->lockReservationLines($reservation, $bike === null ? [] : [$bike]);
            $reservation = $this->lockReservation($reservation);

            if ($reservation->stage === ReservationStage::Cancelled) {
                throw new OccupancyUnavailable('A Cancelled reservation cannot receive another line.');
            }

            $this->assertPackageMembership($reservation, $product);
            $this->assertIntervalFree($reservation, $reservation->starts_at, $reservation->ends_at, $channel, $product, $bike);

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
            $line->loadMissing('reservation');
            $lines = $this->lockReservationLines($line->reservation);
            $line = $lines->firstWhere('id', $line->getKey());

            if ($line === null) {
                return;
            }

            $line->load(['product', 'bike', 'reservation']);

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

    /**
     * @return Collection<int, Bike>
     */
    public function swapCandidates(BikeReservation $line, ReservationChannel $channel): Collection
    {
        $line->loadMissing(['reservation.rentalPackage', 'product.bikeModel', 'bike', 'reservation.bikeReservations']);
        $reservation = $line->reservation;
        $taken = $reservation->bikeReservations
            ->where('id', '!=', $line->id)
            ->pluck('bike_id')
            ->filter()
            ->all();

        $packageVariantIds = null;

        if ($reservation->rental_package_id !== null) {
            $packageVariantIds = $reservation->rentalPackage?->variants()->pluck('bike_model_variants.id');
        }

        return Bike::query()
            ->with(['variant.bikeModel'])
            ->where('in_service', true)
            ->when($line->bike_id, fn ($query) => $query->whereKeyNot($line->bike_id))
            ->when($taken !== [], fn ($query) => $query->whereKeyNot($taken))
            ->when($packageVariantIds !== null, fn ($query) => $query->whereIn('bike_model_variant_id', $packageVariantIds))
            ->get()
            ->reject(fn (Bike $bike): bool => $this->bikeHasBlockingService($bike))
            ->sort(function (Bike $left, Bike $right) use ($line): int {
                return $this->swapRank($line, $left) <=> $this->swapRank($line, $right)
                    ?: strcmp($left->bid, $right->bid);
            })
            ->values();
    }

    public function swapAsset(BikeReservation $line, Bike $bike, ReservationChannel $channel): BikeReservation
    {
        return DB::transaction(function () use ($line, $bike, $channel): BikeReservation {
            $line->loadMissing('reservation');
            $lockedLines = $this->lockReservationLines($line->reservation, [$bike]);
            $line = $lockedLines->firstWhere('id', $line->getKey());

            if ($line === null) {
                throw new OccupancyUnavailable('That reservation line no longer exists.');
            }

            $line->load(['reservation.rentalPackage', 'bike', 'product']);
            $reservation = $line->reservation;
            $from = $line->bike;
            $product = $this->lockProduct(BikeModelVariant::query()->findOrFail($bike->bike_model_variant_id));
            $bike = $this->lockBike($bike);

            if ($bike->bike_model_variant_id !== $product->id) {
                throw new OccupancyUnavailable('That bike changed variants while the swap was being checked.');
            }

            $product->load('bikeModel');
            $this->healStaleOutLines($product);
            $this->assertPackageMembership($reservation, $product);
            $this->assertIntervalFree(
                $reservation,
                $reservation->starts_at,
                $reservation->ends_at,
                $channel,
                $product,
                $bike,
                $line,
            );

            if ($from !== null && $from->id !== $bike->id && $from->bike_situation_reservation_id === $reservation->id) {
                $from->bike_situation_state = BikeSituation::Home;
                $from->bike_situation_reservation_id = null;
                $from->save();
            }

            $line->product()->associate($product);
            $line->bike()->associate($bike);
            $line->save();

            if ($line->status === BikeReservationStatus::Out) {
                $bike->bike_situation_state = BikeSituation::RentedOut;
                $bike->bike_situation_reservation_id = $reservation->id;
                $bike->save();
            } elseif ($line->status === BikeReservationStatus::Assigned) {
                $bike->bike_situation_state = BikeSituation::Prepping;
                $bike->bike_situation_reservation_id = $reservation->id;
                $bike->save();
            }

            $this->bumpExpiresAt($reservation);

            return $line->refresh();
        });
    }

    private function swapRank(BikeReservation $line, Bike $bike): int
    {
        if ($bike->bike_model_variant_id === $line->product_id) {
            return 0;
        }

        if ($bike->variant?->bike_model_id === $line->product?->bike_model_id) {
            return 1;
        }

        return 2;
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
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Availability checks that reserve capacity must run inside a transaction.');
        }

        if ($product === null) {
            $reservation->loadMissing('bikeReservations.product', 'bikeReservations.bike');

            $lines = $reservation->bikeReservations
                ->sortBy(fn (BikeReservation $line): string => sprintf('%010d:%010d', $line->product_id, $line->bike_id ?? 0));

            foreach ($lines as $line) {
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

        $product = $this->lockProduct($product);

        if ($bike !== null) {
            $bike = $this->lockBike($bike);
            $this->assertBikeAssignable($bike, $product, $channel);
        }

        $this->assertClassCapacity($product, $startsAt, $endsAt, $channel, $reservation->id, $ignoreLine?->id);

        if ($bike !== null) {
            $this->assertBikeInterval($bike, $startsAt, $endsAt, $reservation->id, $ignoreLine?->id);
        }
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

        $buffer = $this->effectiveBufferMinutes($bike);

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

        $buffer = $this->effectiveBufferMinutesForProduct($product);

        $held = BikeReservation::query()
            ->with('reservation')
            ->where('product_id', $product->id)
            ->when($ignoreLineId, fn ($builder) => $builder->whereKeyNot($ignoreLineId))
            ->whereHas('reservation', fn ($builder) => $builder
                ->whereKeyNot($ignoreReservationId)
                ->where('stage', '!=', ReservationStage::Cancelled))
            ->get()
            ->filter(function (BikeReservation $line) use ($startsAt, $endsAt, $buffer, $product): bool {
                $lineBuffer = $line->reservation->stage === ReservationStage::NoShow
                    ? $this->effectiveBufferMinutesForProduct($product)
                    : $buffer;
                $occupiedUntil = $line->reservation->ends_at->copy()->addMinutes($lineBuffer);

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

    private function lockProduct(BikeModelVariant $product): BikeModelVariant
    {
        return app(FleetMutationLock::class)
            ->lockVariants([(int) $product->getKey()])
            ->firstOrFail();
    }

    private function lockBike(Bike $bike): Bike
    {
        return app(FleetMutationLock::class)
            ->lockBikes([(int) $bike->getKey()])
            ->firstOrFail();
    }

    public function lockBikeForMutation(Bike $bike): Bike
    {
        return app(FleetMutationLock::class)->lockBikeForMutation($bike);
    }

    public function lockReservation(Reservation $reservation): Reservation
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Reservations must be locked inside a transaction.');
        }

        return Reservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * Heal Out lines whose rental interval and turnaround buffer have passed.
     *
     * The variant lock serializes these updates with allocation checks for the
     * same variant. Cancelled reservations are intentionally left untouched.
     */
    public function healStaleOutLines(BikeModelVariant $product, ?CarbonInterface $now = null): int
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Stale Out lines must be healed inside a transaction.');
        }

        $product = $this->lockProduct($product);
        $now ??= now();
        $healed = 0;

        $lineIds = BikeReservation::query()
            ->where('product_id', $product->id)
            ->where('status', BikeReservationStatus::Out)
            ->whereHas('reservation', fn ($query) => $query->where('stage', '!=', ReservationStage::Cancelled))
            ->orderBy('id')
            ->pluck('id');

        foreach ($lineIds as $lineId) {
            $line = BikeReservation::query()
                ->whereKey($lineId)
                ->lockForUpdate()
                ->first();

            if ($line === null || $line->status !== BikeReservationStatus::Out) {
                continue;
            }

            $reservation = $line->reservation()->lockForUpdate()->firstOrFail();

            if ($reservation->stage === ReservationStage::Cancelled) {
                continue;
            }

            $line->load(['bike', 'product.bikeModel', 'reservation.location']);
            $buffer = $line->bike !== null
                ? $this->effectiveBufferMinutes($line->bike)
                : max(
                    $line->reservation->location->minimum_turnaround_buffer_minutes,
                    $line->product->bikeModel->padding_minutes ?? 0,
                );

            if ($reservation->ends_at->copy()->addMinutes($buffer)->isAfter($now)) {
                continue;
            }

            $bike = $line->bike === null ? null : $this->lockBike($line->bike);

            $line->status = BikeReservationStatus::In;
            $line->checked_in_at = null;
            $line->save();

            if ($bike !== null && $bike->bike_situation_reservation_id === $reservation->id) {
                $bike->bike_situation_state = BikeSituation::Home;
                $bike->bike_situation_reservation_id = null;
                $bike->save();
            }

            $reservation->recomputeStageCache();
            $healed++;
        }

        return $healed;
    }

    /**
     * Lock every line's availability rows in a stable order before a reservation
     * lifecycle transition writes to the reservation itself.
     *
     * @param  list<Bike>  $additionalBikes
     * @return Collection<int, BikeReservation>
     */
    public function lockReservationLines(Reservation $reservation, array $additionalBikes = []): Collection
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Reservation lines must be locked inside a transaction.');
        }

        $lines = BikeReservation::query()
            ->where('reservation_id', $reservation->id)
            ->with(['product', 'bike'])
            ->orderBy('product_id')
            ->orderBy('bike_id')
            ->orderBy('id')
            ->get();

        $fleetLock = app(FleetMutationLock::class);
        $additionalVariantIdsByBike = collect($additionalBikes)
            ->mapWithKeys(fn (Bike $bike): array => [
                $bike->getKey() => (int) Bike::query()->whereKey($bike->getKey())->value('bike_model_variant_id'),
            ]);
        $variantIds = $lines->pluck('product_id')
            ->merge($additionalVariantIdsByBike->values())
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $fleetLock->lockVariants($variantIds);

        $bikeIds = $lines->pluck('bike_id')
            ->merge(collect($additionalBikes)->map(fn (Bike $bike): int => (int) $bike->getKey()))
            ->filter()
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $lockedBikes = $fleetLock->lockBikes($bikeIds)->keyBy('id');

        foreach ($additionalVariantIdsByBike as $bikeId => $variantId) {
            if ($lockedBikes->get($bikeId)?->bike_model_variant_id !== $variantId) {
                throw new LogicException('A bike variant changed while reservation locks were being acquired.');
            }
        }

        $lockedLines = $lines->map(fn (BikeReservation $line): BikeReservation => BikeReservation::query()
            ->whereKey($line->getKey())
            ->lockForUpdate()
            ->firstOrFail());
        $this->lockReservation($reservation);

        return $lockedLines;
    }
}
