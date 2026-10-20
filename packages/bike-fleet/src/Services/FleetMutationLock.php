<?php

namespace Embiken\BikeFleet\Services;

use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class FleetMutationLock
{
    /**
     * @param  array<int, int>  $variantIds
     * @return Collection<int, BikeModelVariant>
     */
    public function lockVariants(array $variantIds): Collection
    {
        $this->ensureTransaction();

        $variants = collect($variantIds)
            ->unique()
            ->sort()
            ->map(fn (int $variantId): BikeModelVariant => BikeModelVariant::query()
                ->whereKey($variantId)
                ->lockForUpdate()
                ->firstOrFail());

        return $variants->values();
    }

    /**
     * @param  array<int, int>  $bikeIds
     * @return Collection<int, Bike>
     */
    public function lockBikes(array $bikeIds): Collection
    {
        $this->ensureTransaction();

        $ids = collect($bikeIds)->unique()->sort()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $bikes = Bike::query()
            ->whereKey($ids->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($bikes->count() !== $ids->count()) {
            throw (new ModelNotFoundException)->setModel(Bike::class, $ids->all());
        }

        return $ids->map(fn (int $id): Bike => $bikes->get($id))->values();
    }

    /**
     * Lock a bike's current variant and bike row, plus any destination variants,
     * using the same variant-before-bike ordering as Availability.
     *
     * @param  array<int, int>  $additionalVariantIds
     */
    public function lockBikeForMutation(Bike $bike, array $additionalVariantIds = []): Bike
    {
        return $this->lockBikesForMutation([(int) $bike->getKey()], $additionalVariantIds)->firstOrFail();
    }

    /**
     * Lock all bikes and their current variants in stable variant-before-bike order.
     *
     * @param  array<int, int>  $bikeIds
     * @param  array<int, int>  $additionalVariantIds
     * @return Collection<int, Bike>
     */
    public function lockBikesForMutation(array $bikeIds, array $additionalVariantIds = []): Collection
    {
        $this->ensureTransaction();

        $ids = collect($bikeIds)->unique()->sort()->values();
        $currentVariants = Bike::query()
            ->whereKey($ids->all())
            ->orderBy('id')
            ->pluck('bike_model_variant_id', 'id');

        if ($currentVariants->count() !== $ids->count() || $currentVariants->contains(null)) {
            throw (new ModelNotFoundException)->setModel(Bike::class, $ids->all());
        }

        $variantIds = array_map('intval', [...$additionalVariantIds, ...$currentVariants->values()->all()]);
        $this->lockVariants($variantIds);
        $lockedBikes = $this->lockBikes($ids->all())->keyBy('id');

        foreach ($currentVariants as $bikeId => $variantId) {
            if ($lockedBikes->get($bikeId)?->bike_model_variant_id !== (int) $variantId) {
                throw new LogicException('The bike variant changed while its mutation lock was being acquired.');
            }
        }

        return $ids->map(fn (int $id): Bike => $lockedBikes->get($id))->values();
    }

    private function ensureTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Fleet mutation locks must be acquired inside a transaction.');
        }
    }
}
