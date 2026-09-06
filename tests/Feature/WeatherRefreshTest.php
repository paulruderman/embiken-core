<?php

use App\Events\LocationWeatherPatched;
use App\Models\Location;
use Embiken\Weather\Actions\RefreshWeatherAction;
use Embiken\Weather\Actions\ResolveSiteGridAction;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSettings;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
});

function nwsPointsPayload(): array
{
    return [
        'properties' => [
            'gridId' => 'BTV',
            'gridX' => 89,
            'gridY' => 57,
        ],
    ];
}

function nwsHourlyPayload(): array
{
    $start = now('UTC')->startOfHour();

    return [
        'properties' => [
            'periods' => [
                [
                    'startTime' => $start->toIso8601String(),
                    'endTime' => $start->copy()->addHour()->toIso8601String(),
                    'temperature' => 72,
                    'shortForecast' => 'Sunny',
                    'detailedForecast' => 'Sunny and mild.',
                    'windSpeed' => '8 mph',
                    'windDirection' => 'S',
                    'relativeHumidity' => ['value' => 40],
                    'icon' => 'https://example.test/icon',
                    'isDaytime' => true,
                ],
            ],
        ],
    ];
}

test('refresh upserts hourly periods and broadcasts a location weather patch', function () {
    Location::factory()->create();
    $site = WeatherSite::factory()->create(['code' => 'SH']);

    Http::fake([
        'api.weather.gov/gridpoints/*' => Http::response(nwsHourlyPayload()),
    ]);

    Event::fake([LocationWeatherPatched::class]);

    $snapshot = app(RefreshWeatherAction::class)();

    expect($snapshot['sites'])->toHaveCount(1)
        ->and($snapshot['sites'][0]['code'])->toBe('SH')
        ->and($snapshot['sites'][0]['current']['temperature_f'])->toBe(72)
        ->and(WeatherPeriod::query()->count())->toBe(1);

    Event::assertDispatched(LocationWeatherPatched::class);
});

test('refresh skips disabled sites', function () {
    Location::factory()->create();
    WeatherSite::factory()->disabled()->create(['code' => 'XX']);

    Http::fake();

    app(RefreshWeatherAction::class)();

    Http::assertNothingSent();
    expect(WeatherPeriod::query()->count())->toBe(0);
});

test('refresh can limit to one site code', function () {
    Location::factory()->create();
    WeatherSite::factory()->create(['code' => 'A']);
    WeatherSite::factory()->create(['code' => 'B']);

    Http::fake([
        'api.weather.gov/gridpoints/*' => Http::response(nwsHourlyPayload()),
    ]);

    app(RefreshWeatherAction::class)('A');

    expect(WeatherPeriod::query()->count())->toBe(1)
        ->and(WeatherPeriod::query()->first()->site->code)->toBe('A');
});

test('stale periods are deleted when nws fails and retention is zero', function () {
    Location::factory()->create();
    $site = WeatherSite::factory()->create(['code' => 'ST']);
    WeatherPeriod::factory()->recycle($site)->create();
    WeatherSettings::current()->update(['stale_forecast_retention_minutes' => 0]);

    Http::fake([
        'api.weather.gov/gridpoints/*' => Http::response('nope', 500),
    ]);

    app(RefreshWeatherAction::class)();

    expect(WeatherPeriod::query()->count())->toBe(0);
});

test('resolve grid persists office and coordinates from nws points', function () {
    $site = WeatherSite::factory()->unresolved()->create([
        'latitude' => 44.4759,
        'longitude' => -73.2121,
    ]);

    Http::fake([
        'api.weather.gov/points/*' => Http::response(nwsPointsPayload()),
    ]);

    $resolved = app(ResolveSiteGridAction::class)->resolve($site);

    expect($resolved->nws_office)->toBe('BTV')
        ->and($resolved->nws_grid_x)->toBe(89)
        ->and($resolved->nws_grid_y)->toBe(57);
});

test('refresh resolves a missing grid before fetching hourly', function () {
    Location::factory()->create();
    WeatherSite::factory()->unresolved()->create(['code' => 'NG']);

    Http::fake([
        'api.weather.gov/points/*' => Http::response(nwsPointsPayload()),
        'api.weather.gov/gridpoints/*' => Http::response(nwsHourlyPayload()),
    ]);

    app(RefreshWeatherAction::class)();

    expect(WeatherSite::query()->first()->hasGrid())->toBeTrue()
        ->and(WeatherPeriod::query()->count())->toBe(1);
});
