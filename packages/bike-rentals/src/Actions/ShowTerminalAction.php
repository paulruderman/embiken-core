<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Models\Location;
use App\Support\DayStore;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShowTerminalAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'terminal:show {--tenant=} {--date=}';

    /**
     * @return array<string, mixed>
     */
    public function handle(?string $scheduleDate = null): array
    {
        $location = Location::query()->firstOrFail();
        $date = $scheduleDate === null
            ? CarbonImmutable::now($location->timezone)
            : CarbonImmutable::createFromFormat('!Y-m-d', $scheduleDate, $location->timezone);
        $snapshot = DayStore::forLocation($location, $date);

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
        $data = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        return $this->handle($data['date'] ?? null);
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
        $date = $command->option('date');
        $snapshot = $this->handle(is_string($date) && $date !== '' ? $date : null);
        $command->info('Bikes: '.count($snapshot['bikes']).', reservations: '.count($snapshot['reservations']));

        return self::SUCCESS;
    }
}
