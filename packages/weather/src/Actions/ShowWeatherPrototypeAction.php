<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\Weather\Services\WeatherReader;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShowWeatherPrototypeAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'weather:prototype {--tenant=}';

    public function __construct(private WeatherReader $reader) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return $this->reader->snapshot();
    }

    /**
     * @return array<string, mixed>
     */
    public function asController(): array
    {
        return $this->handle();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function htmlResponse(array $snapshot, Request $request): InertiaResponse
    {
        return Inertia::render('Weather/prototype/Index', [
            ...$snapshot,
            'coverageNotice' => (string) __('app.weather_ui.coverage_notice'),
            'variant' => $request->query('variant', 'strip'),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->get('/prototype/weather', static::class)->name('prototype.weather');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $snapshot = $this->handle();
        $command->info('Sites: '.count($snapshot['sites']));

        return self::SUCCESS;
    }
}
