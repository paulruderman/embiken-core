<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\Weather\Http\Resources\WeatherSnapshotResource;
use Embiken\Weather\Services\WeatherReader;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class GetWeatherAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'weather:show {--tenant=} {--site=}';

    public function __construct(private WeatherReader $reader) {}

    /**
     * @return array{sites: list<array<string, mixed>>, settings: array{radar_source: string, stale_forecast_retention_minutes: int}}
     */
    public function handle(?string $siteCode = null): array
    {
        return $this->reader->snapshot($siteCode);
    }

    /**
     * @return array{sites: list<array<string, mixed>>, settings: array{radar_source: string, stale_forecast_retention_minutes: int}}
     */
    public function asController(Request $request): array
    {
        return $this->handle($request->query('site'));
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
        $router->get('/weather', static::class)->name('weather.show');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $site = $command->option('site');
        $snapshot = $this->handle(is_string($site) && $site !== '' ? $site : null);

        $command->table(
            ['Code', 'Label', '°F', '°C', 'Forecast', 'Wind'],
            collect($snapshot['sites'])->map(fn (array $site): array => [
                $site['code'],
                $site['label'],
                $site['current']['temperature_f'] ?? '—',
                $site['current']['temperature_c'] ?? '—',
                $site['current']['short_forecast'] ?? '—',
                trim(($site['current']['wind_direction'] ?? '').' '.($site['current']['wind_speed'] ?? '')) ?: '—',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
