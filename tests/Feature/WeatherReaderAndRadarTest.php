<?php

use App\Models\Location;
use App\Support\DayStore;
use Embiken\Weather\Actions\GetWeatherAction;
use Embiken\Weather\Enums\RadarSource;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSite;
use Embiken\Weather\Services\RadarFrameService;
use Embiken\Weather\Services\WeatherReader;
use Embiken\Weather\Support\NoaaOpenGeoRadar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
});

test('weather reader uses the current period then falls back to upcoming', function () {
    $site = WeatherSite::factory()->create(['code' => 'SH']);
    WeatherPeriod::factory()->recycle($site)->create([
        'starts_at' => now()->addHours(2),
        'ends_at' => now()->addHours(3),
        'short_forecast' => 'Later',
    ]);

    $current = app(WeatherReader::class)->current('SH');

    expect($current['current']['short_forecast'])->toBe('Later');
});

test('get weather omits disabled sites', function () {
    WeatherSite::factory()->create(['code' => 'ON']);
    WeatherSite::factory()->disabled()->create(['code' => 'OFF']);

    $snapshot = app(GetWeatherAction::class)();

    expect(collect($snapshot['sites'])->pluck('code')->all())->toBe(['ON']);
});

test('day store includes weather when the module is installed', function () {
    $location = Location::factory()->create();
    WeatherSite::factory()->create(['code' => 'SH']);

    $snapshot = DayStore::forLocation($location);

    expect($snapshot['modules'])->toHaveKey('weather')
        ->and($snapshot['modules']['weather']['sites'][0]['code'])->toBe('SH');
});

test('radar frame service maps rainviewer periods', function () {
    Http::fake([
        'api.rainviewer.com/*' => Http::response([
            'radar' => [
                'past' => [
                    ['time' => 1_700_000_000, 'path' => '/v2/radar/aaa'],
                ],
                'nowcast' => [],
            ],
        ]),
    ]);

    $frames = app(RadarFrameService::class)->frames(RadarSource::Rainviewer);

    expect($frames['source'])->toBe('rainviewer')
        ->and($frames['frames'][0]['path'])->toBe('/v2/radar/aaa');
});

test('noaa radar parses time dimensions from capabilities xml', function () {
    $now = now('UTC')->startOfMinute()->toIso8601String();

    Http::fake([
        'opengeo.ncep.noaa.gov/*' => Http::response(<<<XML
<?xml version="1.0"?>
<WMS_Capabilities xmlns="http://www.opengis.net/wms">
  <Capability>
    <Layer>
      <Layer>
        <Name>conus_bref_qcd</Name>
        <Dimension name="time">{$now}</Dimension>
      </Layer>
    </Layer>
  </Capability>
</WMS_Capabilities>
XML),
    ]);

    $times = app(NoaaOpenGeoRadar::class)->availableFrameTimesIso();

    expect($times)->toContain($now);
});

test('the weather refresh command is scheduled every ten minutes', function () {
    $events = collect(Schedule::events())
        ->filter(fn ($event): bool => str_contains($event->command ?? $event->description ?? '', 'weather:refresh'));

    expect($events)->not->toBeEmpty();
});
