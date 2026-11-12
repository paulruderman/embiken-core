<?php

declare(strict_types=1);

namespace Embiken\BikeRentals\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Models\Location;
use Carbon\CarbonImmutable;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Http\Resources\TerminalBikeCandidatesResource;
use Embiken\BikeRentals\Services\Availability;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

class BrowseTerminalBikeCandidatesAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'terminal:bikes {--tenant=} {--starts-at=} {--ends-at=} {--include-unavailable}';

    /**
     * @return array{
     *     candidates: Collection<int, array{bike: Bike, conflict_reason: 'blocking_service'|'cancelled_out'|'currently_out'|'parked'|'reservation_conflict'|'variant_sold_out'|null}>,
     *     categories: Collection<int, array{id: int, name: string}>
     * }
     */
    public function handle(string $startsAtLocal, string $endsAtLocal, bool $includeUnavailable = false): array
    {
        $location = Location::query()->firstOrFail();
        $startsAt = CarbonImmutable::parse($startsAtLocal, $location->timezone)->utc();
        $endsAt = CarbonImmutable::parse($endsAtLocal, $location->timezone)->utc();

        return [
            'candidates' => app(Availability::class)->bikeCandidatesForInterval(
                $startsAt,
                $endsAt,
                ReservationChannel::Terminal,
                $includeUnavailable,
            ),
            'categories' => BikeCategory::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (BikeCategory $category): array => [
                    'id' => (int) $category->getKey(),
                    'name' => (string) $category->name,
                ])
                ->values(),
        ];
    }

    public function asController(Request $request): TerminalBikeCandidatesResource
    {
        $data = $request->validate([
            'starts_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at_local' => ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at_local'],
            'include_unavailable' => ['sometimes', 'boolean'],
        ]);

        return new TerminalBikeCandidatesResource($this->handle(
            $data['starts_at_local'],
            $data['ends_at_local'],
            (bool) ($data['include_unavailable'] ?? false),
        ));
    }

    public static function routes(Router $router): void
    {
        $router->get('/terminal/bike-candidates', static::class)->name('terminal.bike-candidates');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $candidates = $this->handle(
            $this->requiredStringInput($command, $command->option('starts-at'), 'Start time in the shop timezone'),
            $this->requiredStringInput($command, $command->option('ends-at'), 'End time in the shop timezone'),
            (bool) $command->option('include-unavailable'),
        );

        $command->table(
            ['Bid', 'Model', 'Variant', 'Conflict'],
            $candidates['candidates']->map(fn (array $candidate): array => [
                $candidate['bike']->bid,
                $candidate['bike']->variant->bikeModel->name,
                $candidate['bike']->variant->size,
                $candidate['conflict_reason'] ?? 'available',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
