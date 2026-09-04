<?php

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

class ShowTerminalPrototypeAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'terminal:prototype {--tenant=}';

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $location = Location::query()->firstOrFail();

        $snapshot = DayStore::forLocation($location);

        return [
            ...$snapshot,
            'bikes' => $snapshot['modules']['fleet']['bikes'] ?? [],
            'reservations' => $snapshot['modules']['rentals']['reservations'] ?? [],
        ];
    }

    public function asController(Request $request): array
    {
        return $this->handle();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function htmlResponse(array $snapshot, Request $request): InertiaResponse
    {
        return Inertia::render('Terminal/prototype/Index', [
            ...$snapshot,
            'variant' => $request->query('variant', 'A'),
        ]);
    }

    public static function routes(Router $router): void
    {
        $router->get('/prototype/terminal', static::class)->name('prototype.terminal');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $snapshot = $this->handle();
        $command->info('Bikes: '.count($snapshot['bikes']).', reservations: '.count($snapshot['reservations']));

        return self::SUCCESS;
    }
}
