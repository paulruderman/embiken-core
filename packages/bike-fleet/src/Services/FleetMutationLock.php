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
        $this->ensureTransaction();

        $currentVariantId = Bike::query()->whereKey($bike->getKey())->value('bike_model_variant_id');

        if ($currentVariantId === null) {
            throw (new ModelNotFoundException)->setModel(Bike::class, [$bike->getKey()]);
        }

        $variantIds = array_map('intval', [...$additionalVariantIds, $currentVariantId]);
        $this->lockVariants($variantIds);
        $lockedBike = $this->lockBikes([(int) $bike->getKey()])->firstOrFail();

        if ($lockedBike->bike_model_variant_id !== (int) $currentVariantId) {
            throw new LogicException('The bike variant changed while its mutation lock was being acquired.');
        }

        return $lockedBike;
    }

    private function ensureTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Fleet mutation locks must be acquired inside a transaction.');
        }
    }
}
