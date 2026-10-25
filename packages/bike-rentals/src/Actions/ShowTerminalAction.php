<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Models\Location;
use App\Support\DayStore;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShowTerminalAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'terminal:show {--tenant=}';

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $location = Location::query()->firstOrFail();
        $snapshot = DayStore::forLocation($location);

        return [
            ...$snapshot,
            'bike_assignment_policy' => $location->bike_assignment_policy->value,
            'bikes' => $snapshot['modules']['fleet']['bikes'] ?? [],
            'reservations' => $snapshot['modules']['rentals']['reservations'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function asController(Request $request): array
    {
        return $this->handle();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function htmlResponse(array $snapshot): InertiaResponse
    {
        return Inertia::render('Terminal/Index', [
            ...$snapshot,
            'terminal_ui' => __('app.terminal_ui'),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->get('/terminal', static::class)->name('terminal');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $snapshot = $this->handle();
        $command->info('Bikes: '.count($snapshot['bikes']).', reservations: '.count($snapshot['reservations']));

        return self::SUCCESS;
    }
}
