<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\Weather\Http\Resources\WeatherSnapshotResource;
use Embiken\Weather\Models\WeatherSite;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Throwable;

class UpsertWeatherSiteAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'weather:site {code?} {--tenant=} {--label=} {--lat=} {--lng=} {--enabled=} {--sort=}';

    public function __construct(private ResolveSiteGridAction $resolveGrid) {}

    public function handle(
        string $code,
        string $label,
        float $latitude,
        float $longitude,
        bool $enabled = true,
        ?int $sortOrder = null,
    ): WeatherSite {
        $site = WeatherSite::query()->where('code', $code)->first();

        $coordinatesChanged = $site === null
            || (float) $site->latitude !== $latitude
            || (float) $site->longitude !== $longitude;

        $site ??= new WeatherSite(['code' => $code]);
        $site->code = $code;
        $site->label = $label;
        $site->latitude = (string) $latitude;
        $site->longitude = (string) $longitude;
        $site->enabled = $enabled;
        $site->sort_order = $sortOrder ?? $site->sort_order ?? 0;
        $site->save();

        if ($coordinatesChanged || ! $site->hasGrid()) {
            try {
                $this->resolveGrid->resolve($site);
            } catch (Throwable) {
                // Grid stays editable when NWS lookup fails.
            }
        }

        return $site->refresh();
    }

    public function asController(Request $request): WeatherSite
    {
        return $this->handle(
            $request->string('code')->toString(),
            $request->string('label')->toString(),
            $request->float('latitude'),
            $request->float('longitude'),
            $request->boolean('enabled', true),
            $request->filled('sort_order') ? $request->integer('sort_order') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
            'label' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function jsonResponse(WeatherSite $site): WeatherSnapshotResource
    {
        return new WeatherSnapshotResource([
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
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->post('/weather/sites', static::class)->name('weather.sites.upsert');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $code = $command->argument('code') ?: \Laravel\Prompts\text('Site code');
        $label = $command->option('label') ?: \Laravel\Prompts\text('Label');
        $lat = $command->option('lat') ?: \Laravel\Prompts\text('Latitude');
        $lng = $command->option('lng') ?: \Laravel\Prompts\text('Longitude');
        $enabledOption = $command->option('enabled');
        $enabled = $enabledOption === null || $enabledOption === ''
            ? true
            : filter_var($enabledOption, FILTER_VALIDATE_BOOLEAN);
        $sort = $command->option('sort');

        $site = $this->handle(
            (string) $code,
            (string) $label,
            (float) $lat,
            (float) $lng,
            $enabled,
            is_numeric($sort) ? (int) $sort : null,
        );

        $command->info("Saved weather site {$site->code}.");

        return self::SUCCESS;
    }
}
