<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Actions\Staff\InviteStaffAction;
use App\Enums\StaffRole;
use App\Models\Domain;
use App\Models\Location;
use App\Models\Tenant;
use App\Services\LocationMetadataImport;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeFleet\Services\CatalogFleetImport;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\PackageRateImport;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SeedFakeTenantAction extends Action
{
    private const array PROFILES = [
        'city-shop' => ['label' => 'City commuter and cargo shop'],
        'e-bike-adventure' => ['label' => 'E-bike adventure and trail shop'],
        'family-rentals' => ['label' => 'Family and kids rental shop'],
    ];

    public string $commandSignature = 'db:seed-fake-tenant {--profile=} {--domain=} {--name=} {--timezone=} {--confirm-demo}';

    public string $commandDescription = 'Provision a demo shop Tenant with catalog, hours, packages, and upcoming Reservations.';

    public function __construct(
        private CreateTenantAction $createTenant,
        private AllocateLineAction $allocateLine,
        private CatalogFleetImport $catalogFleetImport,
        private PackageRateImport $packageRateImport,
        private LocationMetadataImport $locationMetadataImport,
    ) {}

    public function handle(
        string $domain = '',
        string $name = '',
        string $timezone = '',
        bool $confirmed = false,
        string $profile = 'city-shop',
    ): Tenant {
        if (! $confirmed) {
            throw ValidationException::withMessages([
                'confirm_demo' => 'Explicit confirmation is required to create a demo Tenant.',
            ]);
        }

        Notification::fake();
        fake()->seed(20260902);

        $profileConfig = self::PROFILES[$profile] ?? null;

        if ($profileConfig === null) {
            throw ValidationException::withMessages([
                'profile' => "The demo profile {$profile} is not available.",
            ]);
        }

        $domain = $domain === '' ? $this->generateDomain($profile) : $domain;
        $metadataPath = database_path("seeders/demo-tenants/{$profile}/shop.json");
        $metadataJson = file_get_contents($metadataPath);

        if ($metadataJson === false) {
            throw new RuntimeException("The demo metadata file {$metadataPath} could not be read.");
        }

        $metadata = $this->locationMetadataImport->parse($metadataJson);
        $metadata['shop']['name'] = $name === '' ? $metadata['shop']['name'] : $name;
        $metadata['shop']['timezone'] = $timezone === '' ? $metadata['shop']['timezone'] : $timezone;
        $metadataJson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $name = $metadata['shop']['name'];
        $timezone = $metadata['shop']['timezone'];

        if (Domain::query()->where('domain', $domain)->exists()) {
            throw ValidationException::withMessages([
                'domain' => "The domain {$domain} is already taken.",
            ]);
        }

        $tenant = ($this->createTenant)(
            $name,
            $domain,
            $timezone,
            $metadata['shop']['currency'],
            'Mina Manager',
            'manager@example.com',
        );

        $tenant->run(function () use ($timezone, $profile, $metadataJson): void {
            $this->seedShop($timezone, $profile, $metadataJson);
        });

        return $tenant->refresh();
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        return $this->jsonResponse($this->handle(
            $data['domain'] ?? '',
            $data['name'] ?? '',
            $data['timezone'] ?? '',
            $data['confirm_demo'],
            $data['profile'],
        ));
    }

    public function jsonResponse(Tenant $tenant): JsonResponse
    {
        return response()->json($tenant->load('domains'));
    }

    public function asCommand(Command $command): int
    {
        $confirmed = (bool) $command->option('confirm-demo');

        if (! $confirmed) {
            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages([
                    'confirm_demo' => 'Pass --confirm-demo to create a demo Tenant in a non-interactive run.',
                ]);
            }

            $confirmed = \Laravel\Prompts\confirm(
                'Create a fully seeded demo Tenant in '.app()->environment().'? Payments will remain disabled.',
                false,
            );
        }

        if (! $confirmed) {
            $command->info('Demo Tenant creation cancelled.');

            return self::SUCCESS;
        }

        $profile = $command->option('profile');

        if (! is_string($profile) || $profile === '') {
            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages([
                    'profile' => 'Pass --profile to select a demo Tenant profile in a non-interactive run.',
                ]);
            }

            $profile = \Laravel\Prompts\select(
                'Choose a demo Tenant profile',
                collect(self::PROFILES)->mapWithKeys(fn (array $profile, string $key): array => [$key => $profile['label']])->all(),
                'city-shop',
            );
        }

        if (! array_key_exists($profile, self::PROFILES)) {
            throw ValidationException::withMessages([
                'profile' => 'Choose a supported demo Tenant profile.',
            ]);
        }

        $tenant = $this->handle(
            $this->optionalStringValue($command->option('domain'), 'Domain') ?? '',
            $this->optionalStringValue($command->option('name'), 'Name') ?? '',
            $this->optionalStringValue($command->option('timezone'), 'Timezone') ?? '',
            true,
            $profile,
        );

        $command->info("Fake tenant {$tenant->id} ({$profile}) at {$tenant->domains->first()?->domain}");
        $command->info('Platform Users are not created by demo Tenant seeding.');
        $command->info('Shop Manager: manager@example.com (set password through the invite or reset flow).');
        $command->info('Shop Counter: counter@example.com (set password through the reset flow).');

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'string', 'in:'.implode(',', array_keys(self::PROFILES))],
            'domain' => ['nullable', 'string', 'max:255', 'unique:domains,domain'],
            'name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'timezone'],
            'confirm_demo' => ['required', 'accepted'],
        ];
    }

    private function seedShop(string $timezone, string $profile, string $metadataJson): void
    {
        $location = Location::query()->firstOrFail();
        app(InviteStaffAction::class)(
            location: $location,
            name: 'Casey Counter',
            email: 'counter@example.com',
            role: StaffRole::Counter,
        );

        $metadataReport = $this->locationMetadataImport->apply($metadataJson);

        if (! $metadataReport['applied']) {
            throw new RuntimeException('The demo shop metadata could not be applied: '.json_encode($metadataReport['issues']));
        }

        [$variants, $packages] = $this->seedCatalogAndPackages($profile);
        $customers = Customer::factory()->count(12)->create();
        $bikes = Bike::query()->with('variant')->orderBy('id')->get();

        $this->seedTodayFloor($location, $timezone, $packages, $customers, $bikes);
        $this->seedUpcomingReservations($location, $timezone, $packages, $customers, $bikes);

        $parked = $bikes->last();
        $parked->in_service = false;
        $parked->save();
    }

    /**
     * @return array{list<BikeModelVariant>, list<RentalPackage>}
     */
    private function seedCatalogAndPackages(string $profile): array
    {
        $catalogFiles = $this->readDemoCsvFiles($profile, ['categories', 'models', 'variants', 'bikes']);
        $catalogReport = $this->catalogFleetImport->apply($catalogFiles, 'report', 'all-or-nothing');

        if (! $catalogReport['applied']) {
            throw new RuntimeException('The demo catalog and fleet CSV files could not be imported: '.json_encode($catalogReport['issues']));
        }

        $packageFiles = $this->readDemoCsvFiles($profile, ['packages', 'package_rates']);
        $packageReport = $this->packageRateImport->apply($packageFiles, 'report', 'all-or-nothing');

        if (! $packageReport['applied']) {
            throw new RuntimeException('The demo package and rate CSV files could not be imported: '.json_encode($packageReport['issues']));
        }

        return [
            array_values(BikeModelVariant::query()->orderBy('id')->get()->all()),
            array_values(RentalPackage::query()->orderBy('sort_order')->orderBy('id')->get()->all()),
        ];
    }

    /** @param list<string> $files
     *  @return array<string, string> */
    private function readDemoCsvFiles(string $profile, array $files): array
    {
        $contents = [];

        foreach ($files as $file) {
            $path = database_path("seeders/demo-tenants/{$profile}/{$file}.csv");
            $csv = file_get_contents($path);

            if ($csv === false) {
                throw new RuntimeException("The demo CSV file {$path} could not be read.");
            }

            $contents[$file] = $csv;
        }

        return $contents;
    }

    private function generateDomain(string $profile): string
    {
        do {
            $domain = 'demo-'.$profile.'-'.Str::lower(Str::random(8)).'.localhost';
        } while (Domain::query()->where('domain', $domain)->exists());

        return $domain;
    }

    /**
     * @param  list<RentalPackage>  $packages
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Bike>  $bikes
     */
    private function seedTodayFloor(
        Location $location,
        string $timezone,
        array $packages,
        $customers,
        $bikes,
    ): void {
        $situations = [
            BikeSituation::Prepping,
            BikeSituation::Staged,
            BikeSituation::RentedOut,
            BikeSituation::Back,
        ];

        foreach ($situations as $index => $situation) {
            $bike = $bikes[$index];
            $reservation = $this->createSeededReservation(
                $location,
                $timezone,
                $packages[$index % count($packages)],
                $customers[$index % $customers->count()],
                0,
                10,
                14,
                ReservationStage::Confirmed,
            );
            $line = ($this->allocateLine)($reservation, $bike->variant, $bike);
            $this->applyFloorSituation($line, $bike->fresh(), $reservation, $situation);
        }

        $unassignedBike = $bikes[4];
        $unassigned = $this->createSeededReservation(
            $location,
            $timezone,
            $packages[0],
            $customers[4 % $customers->count()],
            0,
            11,
            13,
            ReservationStage::Confirmed,
        );
        ($this->allocateLine)($unassigned, $unassignedBike->variant);
    }

    /**
     * @param  list<RentalPackage>  $packages
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Bike>  $bikes
     */
    private function seedUpcomingReservations(
        Location $location,
        string $timezone,
        array $packages,
        $customers,
        $bikes,
    ): void {
        $cursor = 0;
        $slots = [[10, 12], [14, 16]];

        foreach (range(1, 14) as $dayOffset) {
            foreach ($slots as $hours) {
                $this->allocateParty(
                    $location,
                    $timezone,
                    $packages,
                    $customers,
                    $bikes,
                    $cursor,
                    $dayOffset,
                    $hours[0],
                    $hours[1],
                    ReservationStage::Confirmed,
                );
            }
        }

        foreach (range(15, 16) as $dayOffset) {
            $this->allocateParty(
                $location,
                $timezone,
                $packages,
                $customers,
                $bikes,
                $cursor,
                $dayOffset,
                10,
                12,
                ReservationStage::Provisional,
            );
        }
    }

    /**
     * @param  list<RentalPackage>  $packages
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Bike>  $bikes
     */
    private function allocateParty(
        Location $location,
        string $timezone,
        array $packages,
        $customers,
        $bikes,
        int &$cursor,
        int $dayOffset,
        int $startHour,
        int $endHour,
        ReservationStage $stage,
    ): void {
        $partySize = 1 + ($cursor % 3);
        $package = $packages[$cursor % count($packages)];
        $customer = $customers[$cursor % $customers->count()];

        $reservation = $this->createSeededReservation(
            $location,
            $timezone,
            $package,
            $customer,
            $dayOffset,
            $startHour,
            $endHour,
            $stage,
        );

        for ($n = 0; $n < $partySize; $n++) {
            $bike = $bikes[($cursor + $n) % $bikes->count()];
            ($this->allocateLine)($reservation, $bike->variant, $bike);
        }

        $cursor += $partySize;
    }

    private function createSeededReservation(
        Location $location,
        string $timezone,
        RentalPackage $package,
        Customer $customer,
        int $dayOffset,
        int $startHour,
        int $endHour,
        ReservationStage $stage,
    ): Reservation {
        $startsAt = Carbon::now($timezone)->addDays($dayOffset)->setTime($startHour, 0, 0);
        $endsAt = Carbon::now($timezone)->addDays($dayOffset)->setTime($endHour, 0, 0);

        return $location->reservations()->create([
            'customer_id' => $customer->id,
            'rental_package_id' => $package->id,
            'channel' => ReservationChannel::Terminal,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'stage' => $stage,
            'owed' => $package->meter === PackageMeter::None ? 0 : 5000,
            'paid' => 0,
            'currency' => $location->currency,
            'expires_at' => $stage === ReservationStage::Provisional
                ? now()->addMinutes((int) config('embiken.provisional_ttl_minutes'))
                : null,
            'myrental_token' => Str::random(40),
        ]);
    }

    private function applyFloorSituation(
        BikeReservation $line,
        Bike $bike,
        Reservation $reservation,
        BikeSituation $situation,
    ): void {
        if ($situation === BikeSituation::RentedOut) {
            $line->status = BikeReservationStatus::Out;
            $line->checked_out_at = now();
            $line->save();
            $reservation->unsetRelation('bikeReservations');
            $reservation->recomputeStageCache();
        }

        if ($situation === BikeSituation::Back) {
            $line->status = BikeReservationStatus::In;
            $line->checked_in_at = now();
            $line->save();
            $reservation->unsetRelation('bikeReservations');
            $reservation->recomputeStageCache();
        }

        $bike->bike_situation_state = $situation;
        $bike->bike_situation_reservation_id = $situation === BikeSituation::Home
            ? null
            : $reservation->id;
        $bike->save();
    }
}
