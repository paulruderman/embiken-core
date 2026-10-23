<?php

use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Exceptions\OccupancyUnavailable;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function (): void {
    $this->migrateTenantSchema();
});

test('competing allocations serialize on the variant and only one gets the bike', function (): void {
    requirePostgresConcurrencyDriver();

    $bike = Bike::factory()->create();
    $firstReservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $waitingReservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create([
        'starts_at' => $firstReservation->starts_at,
        'ends_at' => $firstReservation->ends_at,
    ]);
    $fixture = concurrencyFixture($bike, [$firstReservation, $waitingReservation]);
    commitTestFixture();

    $applicationName = 'allocation-race-'.Str::uuid();
    $worker = null;
    DB::beginTransaction();
    app(FleetMutationLock::class)->lockVariants([$bike->bike_model_variant_id]);
    $worker = startConcurrentAllocation($applicationName, $waitingReservation, $bike);

    try {
        awaitWorkerLockWait($worker);
        app(AllocateLineAction::class)->handle($firstReservation, $bike->variant, $bike);
        DB::commit();

        expect(finishConcurrentAllocation($worker))->toBe('unavailable')
            ->and(DB::table('bike_reservation')->where('bike_id', $bike->id)->count())->toBe(1);
    } finally {
        stopConcurrentAllocation($worker);
        cleanupConcurrencyFixture($fixture);
    }
});

test('allocation waits for a parking mutation and then rejects the parked bike', function (): void {
    requirePostgresConcurrencyDriver();

    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $fixture = concurrencyFixture($bike, [$reservation]);
    commitTestFixture();

    $worker = null;
    DB::beginTransaction();
    app(FleetMutationLock::class)->lockBikeForMutation($bike);

    try {
        $worker = startConcurrentAllocation('parking-race-'.Str::uuid(), $reservation, $bike);
        awaitWorkerLockWait($worker);

        $bike = Bike::query()->whereKey($bike->id)->firstOrFail();
        $bike->in_service = false;
        $bike->save();
        DB::commit();

        expect(finishConcurrentAllocation($worker))->toBe('unavailable')
            ->and(DB::table('bike_reservation')->where('bike_id', $bike->id)->exists())->toBeFalse();
    } finally {
        stopConcurrentAllocation($worker);
        cleanupConcurrencyFixture($fixture);
    }
});

test('allocation waits for blocking service creation and then rejects the bike', function (): void {
    requirePostgresConcurrencyDriver();

    $bike = Bike::factory()->create();
    $reservation = Reservation::factory()->confirmed()->terminal()->recycle($bike->location)->create();
    $fixture = concurrencyFixture($bike, [$reservation]);
    commitTestFixture();

    $worker = null;
    DB::beginTransaction();
    app(FleetMutationLock::class)->lockBikeForMutation($bike);

    try {
        $worker = startConcurrentAllocation('service-race-'.Str::uuid(), $reservation, $bike);
        awaitWorkerLockWait($worker);

        ServiceRequest::query()->create([
            'bike_id' => $bike->id,
            'description' => 'Blocking repair',
            'blocks_usage' => true,
        ]);
        DB::commit();

        expect(finishConcurrentAllocation($worker))->toBe('unavailable')
            ->and(DB::table('bike_reservation')->where('bike_id', $bike->id)->exists())->toBeFalse();
    } finally {
        stopConcurrentAllocation($worker);
        cleanupConcurrencyFixture($fixture);
    }
});

function requirePostgresConcurrencyDriver(): void
{
    if (DB::connection()->getDriverName() !== 'pgsql') {
        test()->markTestSkipped('PostgreSQL row-lock concurrency coverage runs in the PostgreSQL CI job.');
    }

    if (! function_exists('pcntl_fork') || ! function_exists('stream_socket_pair')) {
        test()->markTestSkipped('The concurrency tests require process control and Unix socket support.');
    }
}

/**
 * @param  list<Reservation>  $reservations
 * @return array{bike_id: int, location_id: int, model_id: int, category_id: int, variant_id: int, reservation_ids: list<int>, customer_ids: list<int>}
 */
function concurrencyFixture(Bike $bike, array $reservations): array
{
    return [
        'bike_id' => (int) $bike->id,
        'location_id' => (int) $bike->location_id,
        'model_id' => (int) $bike->variant->bike_model_id,
        'category_id' => (int) $bike->variant->bikeModel->bike_category_id,
        'variant_id' => (int) $bike->bike_model_variant_id,
        'reservation_ids' => array_map(static fn (Reservation $reservation): int => (int) $reservation->id, $reservations),
        'customer_ids' => array_map(static fn (Reservation $reservation): int => (int) $reservation->customer_id, $reservations),
    ];
}

function commitTestFixture(): void
{
    while (DB::transactionLevel() > 0) {
        DB::commit();
    }
}

/**
 * @return array{pid: int, socket: resource, application_name: string}
 */
function startConcurrentAllocation(string $applicationName, Reservation $reservation, Bike $bike): array
{
    $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

    if ($sockets === false) {
        throw new RuntimeException('Unable to create sockets for the PostgreSQL concurrency test.');
    }

    $pid = pcntl_fork();

    if ($pid === -1) {
        throw new RuntimeException('Unable to start the PostgreSQL concurrency worker.');
    }

    if ($pid === 0) {
        fclose($sockets[0]);
        DB::purge();
        DB::connection()->select("select set_config('application_name', ?, false)", [$applicationName]);
        fwrite($sockets[1], "ready\n");
        fgets($sockets[1]);
        fwrite($sockets[1], "attempting\n");

        try {
            app(AllocateLineAction::class)->handle(
                Reservation::query()->findOrFail($reservation->id),
                $bike->variant()->firstOrFail(),
                Bike::query()->findOrFail($bike->id),
            );
            fwrite($sockets[1], "allocated\n");
        } catch (OccupancyUnavailable) {
            fwrite($sockets[1], "unavailable\n");
        } catch (Throwable $exception) {
            fwrite($sockets[1], 'error:'.$exception::class."\n");
        }

        fclose($sockets[1]);
        exit(0);
    }

    fclose($sockets[1]);
    expect(trim((string) fgets($sockets[0])))->toBe('ready');
    fwrite($sockets[0], "go\n");
    expect(trim((string) fgets($sockets[0])))->toBe('attempting');

    return [
        'pid' => $pid,
        'socket' => $sockets[0],
        'application_name' => $applicationName,
    ];
}

/** @param array{pid: int, socket: resource, application_name: string} $worker */
function awaitWorkerLockWait(array $worker): void
{
    for ($attempt = 0; $attempt < 300; $attempt++) {
        $isWaiting = DB::table('pg_stat_activity')
            ->where('application_name', $worker['application_name'])
            ->where('wait_event_type', 'Lock')
            ->exists();

        if ($isWaiting) {
            return;
        }

        usleep(10_000);
    }

    throw new RuntimeException('The allocation worker did not block on the variant row lock.');
}

/** @param array{pid: int, socket: resource, application_name: string} $worker */
function finishConcurrentAllocation(array $worker): string
{
    $outcome = trim((string) fgets($worker['socket']));
    fclose($worker['socket']);
    pcntl_waitpid($worker['pid'], $status);

    if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
        throw new RuntimeException('The PostgreSQL allocation worker exited unexpectedly.');
    }

    return $outcome;
}

/** @param array{pid: int, socket: resource, application_name: string}|null $worker */
function stopConcurrentAllocation(?array $worker): void
{
    if ($worker === null) {
        return;
    }

    if (is_resource($worker['socket'])) {
        fclose($worker['socket']);
    }

    $result = pcntl_waitpid($worker['pid'], $status, WNOHANG);

    if ($result === 0) {
        posix_kill($worker['pid'], SIGTERM);
        pcntl_waitpid($worker['pid'], $status);
    }

    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
}

/**
 * @param  array{bike_id: int, location_id: int, model_id: int, category_id: int, variant_id: int, reservation_ids: list<int>, customer_ids: list<int>}  $fixture
 */
function cleanupConcurrencyFixture(array $fixture): void
{
    if (DB::transactionLevel() === 0) {
        DB::beginTransaction();
    }

    DB::table('bike_reservation')->whereIn('reservation_id', $fixture['reservation_ids'])->delete();
    DB::table('service_requests')->where('bike_id', $fixture['bike_id'])->delete();
    DB::table('reservations')->whereIn('id', $fixture['reservation_ids'])->delete();
    DB::table('customers')->whereIn('id', $fixture['customer_ids'])->delete();
    DB::table('bikes')->where('id', $fixture['bike_id'])->delete();
    DB::table('bike_model_variants')->where('id', $fixture['variant_id'])->delete();
    DB::table('bike_models')->where('id', $fixture['model_id'])->delete();
    DB::table('bike_categories')->where('id', $fixture['category_id'])->delete();
    DB::table('locations')->where('id', $fixture['location_id'])->delete();

    DB::commit();
    DB::beginTransaction();
}
