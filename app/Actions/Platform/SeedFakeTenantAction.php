<?php

namespace App\Actions\Platform;

use App\Actions\Action;
use App\Actions\Reservations\AllocateLineAction;
use App\Enums\ConfirmThreshold;
use App\Enums\PackageMeter;
use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Enums\Weekday;
use App\Models\Bike;
use App\Models\BikeCategory;
use App\Models\BikeModel;
use App\Models\BikeModelVariant;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\RentalPackage;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SeedFakeTenantAction extends Action
{
    public string $commandSignature = 'db:seed-fake-tenant {--domain=demo.localhost} {--name=Harbor Demo Bikes} {--timezone=America/New_York}';

    public string $commandDescription = 'Provision a demo shop Tenant with catalog, hours, packages, and upcoming Reservations.';

    public function __construct(
        private CreateTenantAction $createTenant,
        private AllocateLineAction $allocateLine,
    ) {}

    public function handle(
        string $domain = 'demo.localhost',
        string $name = 'Harbor Demo Bikes',
        string $timezone = 'America/New_York',
    ): Tenant {
        Notification::fake();
        fake()->seed(20260902);

        if (Domain::query()->where('domain', $domain)->exists()) {
            throw ValidationException::withMessages([
                'domain' => "The domain {$domain} is already taken.",
            ]);
        }

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $tenant = ($this->createTenant)(
            $name,
            $domain,
            $timezone,
            'usd',
            'Mina Manager',
            'manager@example.com',
        );

        $tenant->charges_enabled = true;
        $tenant->transfers_active = true;
        $tenant->save();

        $tenant->run(function () use ($timezone): void {
            $this->seedShop($timezone);
        });

        return $tenant->refresh();
    }

    public function asController(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        return $this->jsonResponse($this->handle(
            $data['domain'],
            $data['name'],
            $data['timezone'],
        ));
    }

    public function jsonResponse(Tenant $tenant): JsonResponse
    {
        return response()->json($tenant->load('domains'));
    }

    public function asCommand(Command $command): int
    {
        $tenant = $this->handle(
            (string) $command->option('domain'),
            (string) $command->option('name'),
            (string) $command->option('timezone'),
        );

        $command->info("Fake tenant {$tenant->id} at {$command->option('domain')}");
        $command->info('Platform User: test@example.com / password');
        $command->info('Shop Manager: manager@example.com / password');
        $command->info('Shop Counter: counter@example.com / password');

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
        ];
    }

    private function seedShop(string $timezone): void
    {
        $location = Location::query()->firstOrFail();

        $manager = Staff::query()->where('email', 'manager@example.com')->firstOrFail();
        $manager->password = 'password';
        $manager->save();

        Staff::factory()->counter()->recycle($location)->create([
            'name' => 'Casey Counter',
            'email' => 'counter@example.com',
            'password' => 'password',
        ]);

        foreach (Weekday::cases() as $weekday) {
            LocationHour::query()->create([
                'location_id' => $location->id,
                'weekday' => $weekday,
                'opens_at' => '09:00:00',
                'closes_at' => '18:00:00',
                'closes_next_day' => false,
            ]);
        }

        $variants = $this->seedCatalog($location);
        $packages = $this->seedPackages($location, $variants);
        $customers = Customer::factory()->count(12)->create();
        $bikes = Bike::query()->orderBy('id')->get();

        $this->seedUpcomingReservations($location, $timezone, $packages, $customers, $bikes);
    }

    /**
     * @return list<BikeModelVariant>
     */
    private function seedCatalog(Location $location): array
    {
        $catalog = [
            'City' => ['Commuter', 'Cruiser'],
            'E-bike' => ['City Plus', 'Trail'],
            'Kids' => ['Mini'],
        ];

        $variants = [];
        $bid = 1;

        foreach ($catalog as $categoryName => $models) {
            $category = BikeCategory::query()->create([
                'name' => $categoryName,
                'description' => $categoryName.' rentals',
            ]);

            foreach ($models as $modelName) {
                $model = BikeModel::query()->create([
                    'bike_category_id' => $category->id,
                    'name' => $modelName,
                    'padding_minutes' => $categoryName === 'E-bike' ? 20 : null,
                ]);

                $sizes = $categoryName === 'Kids' ? ['S', 'M'] : ['S', 'M', 'L'];

                foreach ($sizes as $size) {
                    $variant = BikeModelVariant::query()->create([
                        'bike_model_id' => $model->id,
                        'size' => $size,
                        'min_ideal_rider_height' => 140,
                        'max_ideal_rider_height' => 190,
                        'min_extended_rider_height' => 130,
                        'max_extended_rider_height' => 200,
                    ]);

                    $variants[] = $variant;

                    for ($n = 0; $n < 3; $n++) {
                        Bike::factory()->recycle($location)->recycle($variant)->create([
                            'bid' => sprintf('%s-%02d', Str::upper(Str::substr($categoryName, 0, 1)), $bid),
                        ]);
                        $bid++;
                    }
                }
            }
        }

        return $variants;
    }

    /**
     * @param  list<BikeModelVariant>  $variants
     * @return list<RentalPackage>
     */
    private function seedPackages(Location $location, array $variants): array
    {
        $hourly = RentalPackage::factory()->recycle($location)->create([
            'name' => 'Hourly',
            'meter' => PackageMeter::PerHour,
            'confirm_threshold' => ConfirmThreshold::Full,
            'book_visible' => true,
            'sort_order' => 1,
        ]);

        $day = RentalPackage::factory()->recycle($location)->create([
            'name' => 'Day pass',
            'meter' => PackageMeter::PerCalendarDay,
            'confirm_threshold' => ConfirmThreshold::Deposit,
            'deposit_percent' => 50,
            'book_visible' => true,
            'sort_order' => 2,
        ]);

        $free = RentalPackage::factory()->recycle($location)->free()->create([
            'name' => 'Complimentary',
            'book_visible' => true,
            'sort_order' => 3,
        ]);

        $packages = [$hourly, $day, $free];

        foreach ($packages as $package) {
            $rate = match ($package->meter) {
                PackageMeter::None => 0,
                PackageMeter::PerHour => 2500,
                PackageMeter::PerCalendarDay, PackageMeter::PerLine => 8000,
            };

            foreach ($variants as $variant) {
                $package->variants()->attach($variant->id, ['rate_cents' => $rate]);
            }
        }

        return $packages;
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
        $startsAt = Carbon::now($timezone)->addDays($dayOffset)->setTime($startHour, 0, 0);
        $endsAt = Carbon::now($timezone)->addDays($dayOffset)->setTime($endHour, 0, 0);
        $partySize = 1 + ($cursor % 3);
        $package = $packages[$cursor % count($packages)];
        $customer = $customers[$cursor % $customers->count()];

        $reservation = $location->reservations()->create([
            'customer_id' => $customer->id,
            'rental_package_id' => $package->id,
            'channel' => ReservationChannel::Terminal,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'stage' => $stage,
            'owed' => $package->meter === PackageMeter::None ? 0 : 5000,
            'paid' => $stage === ReservationStage::Confirmed ? 5000 : 0,
            'expires_at' => $stage === ReservationStage::Provisional
                ? now()->addMinutes((int) config('embiken.provisional_ttl_minutes'))
                : null,
            'myrental_token' => Str::random(40),
        ]);

        for ($n = 0; $n < $partySize; $n++) {
            $bike = $bikes[($cursor + $n) % $bikes->count()];
            ($this->allocateLine)($reservation, $bike->variant, $bike);
        }

        $cursor += $partySize;
    }
}
