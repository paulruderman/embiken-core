<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\Weather\Http\Resources\WeatherSnapshotResource;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

class ListWeatherSitesAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'weather:sites {--tenant=}';

    /**
     * @return Collection<int, WeatherSite>
     */
    public function handle(): Collection
    {
        return WeatherSite::query()->ordered()->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function asController(): array
    {
        return array_values($this->handle()->map(fn (WeatherSite $site): array => [
            'id' => $site->id,
            'code' => $site->code,
            'label' => $site->label,
            'latitude' => (float) $site->latitude,
            'longitude' => (float) $site->longitude,
            'nws_office' => $site->nws_office,
            'nws_grid_x' => $site->nws_grid_x,
            'nws_grid_y' => $site->nws_grid_y,
            'enabled' => $site->enabled,
            'sort_order' => $site->sort_order,
        ])->all());
    }

    /**
     * @param  list<array<string, mixed>>  $sites
     */
    public function jsonResponse(array $sites): WeatherSnapshotResource
    {
        return new WeatherSnapshotResource(['sites' => $sites]);
    }

    public static function routes(Router $router): void
    {
        $router->get('/weather/sites', static::class)->name('weather.sites');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $sites = $this->handle();

        $command->table(
            ['Code', 'Label', 'Lat', 'Lng', 'Office', 'Grid', 'Enabled', 'Sort'],
            $sites->map(fn (WeatherSite $site): array => [
                $site->code,
                $site->label,
                $site->latitude,
                $site->longitude,
                $site->nws_office ?? '—',
                $site->hasGrid() ? "{$site->nws_grid_x},{$site->nws_grid_y}" : '—',
                $site->enabled ? 'yes' : 'no',
                $site->sort_order,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
