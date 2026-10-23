<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Events\LocationWeatherPatched;
use App\Models\Location;
use App\Models\Tenant;
use App\Support\ModuleRegistry;
use Carbon\CarbonImmutable;
use Embiken\Weather\Http\Resources\WeatherSnapshotResource;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSettings;
use Embiken\Weather\Models\WeatherSite;
use Embiken\Weather\Services\NwsClient;
use Embiken\Weather\Services\WeatherReader;
use Embiken\Weather\Support\NwsPeriodMapper;
use Embiken\Weather\WeatherModule;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use RuntimeException;
use Throwable;

class RefreshWeatherAction extends Action
{
    use AuthorizesStaff;

    public const FORECAST_HOURS = 48;

    public string $commandSignature = 'weather:refresh {--tenant=} {--site=}';

    public function __construct(
        private NwsClient $nws,
        private ResolveSiteGridAction $resolveGrid,
        private WeatherReader $reader,
    ) {}

    /**
     * @return array{sites: list<array<string, mixed>>, settings: array{radar_source: string, stale_forecast_retention_minutes: int}}
     */
    public function handle(?string $siteCode = null): array
    {
        WeatherPeriod::query()->whereDoesntHave('site')->delete();

        WeatherPeriod::query()
            ->whereHas('site', fn ($query) => $query->where('enabled', false))
            ->delete();

        $query = WeatherSite::query()->enabled()->ordered();

        if ($siteCode !== null && $siteCode !== '') {
            $query->where('code', $siteCode);
        }

        foreach ($query->get() as $site) {
            try {
                $this->refreshSite($site);
            } catch (Throwable $exception) {
                $this->handleFailedSite($site);
            }
        }

        $snapshot = $this->reader->snapshot($siteCode ?: null);

        $locationId = Location::query()->value('id');

        if (is_int($locationId)) {
            event(new LocationWeatherPatched($snapshot, $locationId));
        }

        return $snapshot;
    }

    /**
     * @return array{sites: list<array<string, mixed>>, settings: array{radar_source: string, stale_forecast_retention_minutes: int}}
     */
    public function asController(Request $request): array
    {
        return $this->handle($request->string('site')->toString() ?: null);
    }

    /**
     * @param  array{sites: list<array<string, mixed>>, settings: array{radar_source: string, stale_forecast_retention_minutes: int}}  $snapshot
     */
    public function jsonResponse(array $snapshot): WeatherSnapshotResource
    {
        return new WeatherSnapshotResource($snapshot);
    }

    public static function routes(Router $router): void
    {
        $router->post('/weather/refresh', static::class)->name('weather.refresh');
    }

    public function asCommand(Command $command): int
    {
        $tenant = $command->option('tenant');
        $site = $command->option('site');
        $siteCode = is_string($site) && $site !== '' ? $site : null;

        if (is_string($tenant) && $tenant !== '') {
            tenancy()->initialize($this->resolveTenant($tenant));
            $this->handle($siteCode);
            $command->info('Weather refreshed.');

            return self::SUCCESS;
        }

        $this->refreshEnabledTenants($siteCode);
        $command->info('Weather refreshed for enabled tenants.');

        return self::SUCCESS;
    }

    private function refreshEnabledTenants(?string $siteCode): void
    {
        $registry = app(ModuleRegistry::class);

        foreach (Tenant::query()->get() as $tenant) {
            if (! $registry->isEnabled(WeatherModule::ID, $tenant)) {
                continue;
            }

            tenancy()->initialize($tenant);
            $this->handle($siteCode);
            tenancy()->end();
        }
    }

    private function refreshSite(WeatherSite $site): void
    {
        if (! $site->hasGrid()) {
            $this->resolveGrid->resolve($site);
            $site->refresh();
        }

        if (! $site->hasGrid()) {
            throw new RuntimeException("Weather site {$site->code} has no NWS grid.");
        }

        $periods = array_slice(
            $this->nws->hourlyForecast($site->nws_office, (int) $site->nws_grid_x, (int) $site->nws_grid_y),
            0,
            self::FORECAST_HOURS,
        );

        $persistedIds = [];

        foreach ($periods as $period) {
            $startTime = NwsPeriodMapper::parseStartTime($period);

            if ($startTime === null) {
                continue;
            }

            $attributes = NwsPeriodMapper::toAttributes($period);

            if ($attributes['starts_at'] === null || $attributes['ends_at'] === null) {
                continue;
            }

            $row = WeatherPeriod::query()->updateOrCreate(
                [
                    'weather_site_id' => $site->id,
                    'starts_at' => $startTime,
                ],
                $attributes,
            );

            $persistedIds[] = $row->id;
        }

        if ($persistedIds === []) {
            throw new RuntimeException("No valid forecast periods for {$site->code}");
        }

        WeatherPeriod::query()
            ->where('weather_site_id', $site->id)
            ->whereNotIn('id', $persistedIds)
            ->delete();
    }

    private function handleFailedSite(WeatherSite $site): void
    {
        $retentionMinutes = WeatherSettings::current()->stale_forecast_retention_minutes;

        if ($retentionMinutes === 0) {
            WeatherPeriod::query()->where('weather_site_id', $site->id)->delete();

            return;
        }

        $latestUpdatedAt = WeatherPeriod::query()
            ->where('weather_site_id', $site->id)
            ->max('updated_at');

        if ($latestUpdatedAt === null) {
            return;
        }

        $cutoff = CarbonImmutable::now('UTC')->subMinutes($retentionMinutes);

        if (CarbonImmutable::parse($latestUpdatedAt)->lessThan($cutoff)) {
            WeatherPeriod::query()->where('weather_site_id', $site->id)->delete();
        }
    }
}
