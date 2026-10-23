<?php

declare(strict_types=1);

namespace Embiken\Weather\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use Embiken\Weather\Enums\RadarSource;
use Embiken\Weather\Http\Resources\WeatherSnapshotResource;
use Embiken\Weather\Services\WeatherReader;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Validation\Rule;

class GetRadarFramesAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'weather:radar {--tenant=} {--source=}';

    public function __construct(private WeatherReader $reader) {}

    /**
     * @return array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}
     */
    public function handle(?RadarSource $source = null): array
    {
        return $this->reader->radarFrames($source);
    }

    /**
     * @return array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}
     */
    public function asController(Request $request): array
    {
        $source = $request->query('source');

        return $this->handle(
            is_string($source) && $source !== '' ? RadarSource::from($source) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source' => ['sometimes', Rule::enum(RadarSource::class)],
        ];
    }

    /**
     * @param  array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}  $frames
     */
    public function jsonResponse(array $frames): WeatherSnapshotResource
    {
        return new WeatherSnapshotResource($frames);
    }

    public static function routes(Router $router): void
    {
        $router->get('/weather/radar', static::class)->name('weather.radar');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $sourceOption = $command->option('source');
        $source = is_string($sourceOption) && $sourceOption !== ''
            ? RadarSource::from($sourceOption)
            : null;
        $payload = $this->handle($source);

        $command->info('Source: '.$payload['source']);
        $command->table(
            ['Time', 'ISO', 'Path'],
            collect($payload['frames'])->map(fn (array $frame): array => [
                $frame['time'],
                $frame['timeIso'] ?? '',
                $frame['path'] ?? '',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
