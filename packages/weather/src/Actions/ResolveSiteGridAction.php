<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\Weather\Models\WeatherSite;
use Embiken\Weather\Services\NwsClient;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class ResolveSiteGridAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'weather:resolve-grid {--tenant=} {--site=}';

    public function __construct(private NwsClient $nws) {}

    public function handle(?string $siteCode = null): int
    {
        $query = WeatherSite::query()->enabled();

        if ($siteCode !== null && $siteCode !== '') {
            $query->where('code', $siteCode);
        }

        $resolved = 0;

        foreach ($query->get() as $site) {
            $this->resolve($site);
            $resolved++;
        }

        return $resolved;
    }

    public function resolve(WeatherSite $site): WeatherSite
    {
        $grid = $this->nws->points((float) $site->latitude, (float) $site->longitude);

        $site->nws_office = $grid['office'];
        $site->nws_grid_x = $grid['gridX'];
        $site->nws_grid_y = $grid['gridY'];
        $site->save();

        return $site->refresh();
    }

    public function asController(Request $request): array
    {
        $count = $this->handle($request->string('site')->toString() ?: null);

        return ['resolved' => $count];
    }

    public static function routes(Router $router): void
    {
        $router->post('/weather/resolve-grid', static::class)->name('weather.resolve-grid');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $site = $command->option('site');
        $count = $this->handle(is_string($site) && $site !== '' ? $site : null);
        $command->info("Resolved grid for {$count} site(s).");

        return self::SUCCESS;
    }
}
