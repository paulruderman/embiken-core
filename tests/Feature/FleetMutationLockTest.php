<?php

use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function (): void {
    $this->migrateTenantSchema();
});

test('fleet mutation locks acquire multiple bikes in id order with their variants', function (): void {
    $firstBike = Bike::factory()->create();
    $secondBike = Bike::factory()->recycle($firstBike->location)->create();

    $lockedBikes = DB::transaction(fn () => app(FleetMutationLock::class)
        ->lockBikesForMutation([$secondBike->id, $firstBike->id]));

    expect($lockedBikes->pluck('id')->all())->toBe([$firstBike->id, $secondBike->id]);
});
