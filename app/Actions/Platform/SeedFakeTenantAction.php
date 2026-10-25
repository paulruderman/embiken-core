<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Actions\Shop\UpdateShopBrandingAction;
use App\Actions\Staff\InviteStaffAction;
use App\Enums\StaffRole;
use App\Models\Location;
use App\Models\Staff;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SeedFakeTenantAction extends Action
{
    private const array PROFILES = [
        'city-shop' => ['label' => 'City commuter and cargo shop'],
        'e-bike-adventure' => ['label' => 'E-bike adventure and trail shop'],
        'family-rentals' => ['label' => 'Family and kids rental shop'],
        'jimbos' => ['label' => "Jimbo's multi-category rental shop"],
        'showroom-demo' => ['label' => 'Retail showroom with test-ride bikes'],
    ];

    public string $commandSignature = 'db:seed-fake-tenant {--profile=} {--all} {--domain=} {--name=} {--timezone=} {--confirm-demo} {--resume}';

    public string $commandDescription = 'Provision a demo shop Tenant with profile data, catalog, fleet, Customers, and Reservations.';

    public function __construct(
        private CreateTenantAction $createTenant,
        private AllocateLineAction $allocateLine,
        private CatalogFleetImport $catalogFleetImport,
        private PackageRateImport $packageRateImport,
        private LocationMetadataImport $locationMetadataImport,
        private UpdateShopBrandingAction $updateShopBranding,
    ) {}

    public function handle(
        string $domain = '',
        string $name = '',
        string $timezone = '',
        bool $confirmed = false,
        string $profile = 'city-shop',
        bool $resume = false,
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

        $existingTenant = Tenant::query()
            ->whereHas('domains', fn ($query) => $query->where('domain', $domain))
            ->first();

        if ($existingTenant !== null && ! $resume) {
            throw ValidationException::withMessages([
                'domain' => "The domain {$domain} is already taken.",
            ]);
        }

        if ($existingTenant !== null
            && ($existingTenant->name !== $name
                || $existingTenant->isSuspended()
                || $existingTenant->charges_enabled
                || $existingTenant->transfers_active)) {
            throw ValidationException::withMessages([
                'domain' => 'Resume is allowed only for a matching, active demo Tenant with payments disabled.',
            ]);
        }

        $tenant = $existingTenant ?? ($this->createTenant)(
            $name,
            $domain,
            $timezone,
            $metadata['shop']['currency'],
            'Mina Manager',
            'manager@example.com',
        );

        $tenant->run(function () use ($timezone, $profile, $metadataJson, $resume): void {
            $this->seedShop($timezone, $profile, $metadataJson, $resume);
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
        $allProfiles = (bool) $command->option('all');
        $profile = $command->option('profile');

        if ($allProfiles && (
            (is_string($profile) && $profile !== '')
            || $this->optionalStringValue($command->option('domain'), 'Domain') !== null
            || $this->optionalStringValue($command->option('name'), 'Name') !== null
            || $this->optionalStringValue($command->option('timezone'), 'Timezone') !== null
            || $command->option('resume')
        )) {
            throw ValidationException::withMessages([
                'all' => 'Use --all by itself; do not combine it with --profile, --domain, --name, --timezone, or --resume.',
            ]);
        }

        $confirmed = (bool) $command->option('confirm-demo');

        if (! $confirmed) {
            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages([
                    'confirm_demo' => 'Pass --confirm-demo to create a demo Tenant in a non-interactive run.',
                ]);
            }

            $confirmed = \Laravel\Prompts\confirm(
                'Create '.($allProfiles ? 'fully seeded demo Tenants for every profile' : 'a fully seeded demo Tenant').' in '.app()->environment().'? Payments stay disabled; demo Staff use password 11111111.',
                false,
            );
        }

        if (! $confirmed) {
            $command->info('Demo Tenant creation cancelled.');

            return self::SUCCESS;
        }

        config()->set('broadcasting.default', 'null');

        if ($allProfiles) {
            $command->info('Creating one demo Tenant for each profile:');

            foreach (self::PROFILES as $profile => $profileConfig) {
                $tenant = $this->handle(
                    confirmed: true,
                    profile: $profile,
                );

                $command->info("{$profileConfig['label']}: {$tenant->domains->first()?->domain}");
            }

            $command->info('Demo Shop Manager: manager@example.com / 11111111');
            $command->info('Demo Shop Counter: counter@example.com / 11111111');

            return self::SUCCESS;
        }

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
            (bool) $command->option('resume'),
        );

        $command->info("Fake tenant {$tenant->id} ({$profile}) at {$tenant->domains->first()?->domain}");
        $command->info('Platform Users are not created by demo Tenant seeding.');
        $command->info('Demo Shop Manager: manager@example.com / 11111111');
        $command->info('Demo Shop Counter: counter@example.com / 11111111');

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

    private function seedShop(string $timezone, string $profile, string $metadataJson, bool $resume): void
    {
        $location = Location::query()->firstOrFail();

        if ($resume && (Customer::query()->exists() || Reservation::query()->exists())) {
            $catalogFiles = $this->readDemoCsvFiles($profile, ['categories', 'models', 'variants']);
            $catalogFiles['bikes'] = "bid,variant_key,serial_number,in_service,self_bookable\n";
            $catalogReport = $this->catalogFleetImport->apply(
                $catalogFiles,
                'update',
                'all-or-nothing',
                $this->readDemoPhotoAssets(),
                "demo-{$profile}",
            );

            if (! $catalogReport['applied']) {
                throw new RuntimeException('The demo catalog and fleet CSV files could not be refreshed: '.json_encode($catalogReport['issues']));
            }

            $this->seedShopBranding($profile, $location->name);

            return;
        }

        if (! Staff::query()->where('email', 'counter@example.com')->exists()) {
            app(InviteStaffAction::class)(
                location: $location,
                name: 'Casey Counter',
                email: 'counter@example.com',
                role: StaffRole::Counter,
            );
        }

        Staff::query()->where('email', 'manager@example.com')->firstOrFail()->update([
            'password' => '11111111',
        ]);
        Staff::query()->where('email', 'counter@example.com')->firstOrFail()->update([
            'password' => '11111111',
        ]);

        $metadataReport = $this->locationMetadataImport->apply($metadataJson);

        if (! $metadataReport['applied']) {
            throw new RuntimeException('The demo shop metadata could not be applied: '.json_encode($metadataReport['issues']));
        }

        $this->seedShopBranding($profile, $location->fresh()->name);

        [, $packages] = $this->seedCatalogAndPackages($profile, $resume);
        $bikes = Bike::query()->with('variant')->orderBy('id')->get();
        $customerCount = min(150, max(12, (int) ceil($bikes->count() / 2)));
        $customers = Customer::factory()->count($customerCount)->create();

        $this->seedTodayFloor($location, $timezone, $packages, $customers, $bikes);
        $this->seedUpcomingReservations($location, $timezone, $packages, $customers, $bikes);

        $parked = $bikes->last();
        $parked->in_service = false;
        $parked->save();
    }

    /**
     * @return array{list<BikeModelVariant>, list<RentalPackage>}
     */
    private function seedCatalogAndPackages(string $profile, bool $resume): array
    {
        $catalogFiles = $this->readDemoCsvFiles($profile, ['categories', 'models', 'variants', 'bikes']);
        $catalogReport = $this->catalogFleetImport->apply(
            $catalogFiles,
            $resume ? 'skip' : 'report',
            'all-or-nothing',
            $this->readDemoPhotoAssets(),
            "demo-{$profile}",
        );

        if (! $catalogReport['applied']) {
            throw new RuntimeException('The demo catalog and fleet CSV files could not be imported: '.json_encode($catalogReport['issues']));
        }

        $packageFiles = $this->readDemoCsvFiles($profile, ['packages', 'package_rates']);
        $packageReport = $this->packageRateImport->apply($packageFiles, $resume ? 'skip' : 'report', 'all-or-nothing');

        if (! $packageReport['applied']) {
            throw new RuntimeException('The demo package and rate CSV files could not be imported: '.json_encode($packageReport['issues']));
        }

        return [
            array_values(BikeModelVariant::query()->orderBy('id')->get()->all()),
            array_values(RentalPackage::query()->orderBy('sort_order')->orderBy('id')->get()->all()),
        ];
    }

    /** @return array<string, string> */
    private function readDemoPhotoAssets(): array
    {
        $photos = [];
        $paths = glob(database_path('seeders/demo-tenants/assets/*.png'));

        if ($paths === false) {
            throw new RuntimeException('The demo photo assets could not be listed.');
        }

        foreach ($paths as $path) {
            if (str_starts_with(basename($path), 'brand-')) {
                continue;
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException("The demo photo asset {$path} could not be read.");
            }

            $photos['images/'.basename($path)] = $contents;
        }

        return $photos;
    }

    private function seedShopBranding(string $profile, string $brandName): void
    {
        $path = database_path("seeders/demo-tenants/{$profile}/branding.json");
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("The demo branding file {$path} could not be read.");
        }

        /** @var array{primary_color: string, brand_gray_color: string, logo_file: string} $branding */
        $branding = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! preg_match('/\\Abrand-[A-Za-z0-9][A-Za-z0-9._-]*\\.png\\z/', $branding['logo_file'])) {
            throw new RuntimeException('The demo branding logo filename is invalid.');
        }

        $logoContents = file_get_contents(database_path('seeders/demo-tenants/assets/'.$branding['logo_file']));

        if ($logoContents === false) {
            throw new RuntimeException("The demo branding logo {$branding['logo_file']} could not be read.");
        }

        $logoPath = "branding/demo-{$profile}.png";

        if (! Storage::disk('public')->put($logoPath, $logoContents)) {
            throw new RuntimeException("The demo branding logo {$branding['logo_file']} could not be stored.");
        }

        $this->updateShopBranding->handle([
            'brand_name' => $brandName,
            'primary_color' => $branding['primary_color'],
            'brand_gray_color' => $branding['brand_gray_color'],
            'logo_path' => $logoPath,
            'favicon_path' => null,
        ]);
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
        return $profile.'.localhost';
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
        $slots = match (true) {
            $bikes->count() > 150 => [[8, 10], [10, 12], [12, 14], [14, 16], [16, 18]],
            $bikes->count() > 50 => [[9, 11], [11, 13], [13, 15], [15, 17]],
            default => [[10, 12], [14, 16]],
        };

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
