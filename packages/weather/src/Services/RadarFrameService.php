<?php

declare(strict_types=1);

namespace Embiken\Weather\Services;

use Carbon\CarbonImmutable;
use Embiken\Weather\Enums\RadarSource;
use Embiken\Weather\Support\NoaaOpenGeoRadar;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RadarFrameService
{
    /**
     * @return array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}
     */
    public function frames(RadarSource $source): array
    {
        return match ($source) {
            RadarSource::Noaa => $this->noaaFrames(),
            RadarSource::Rainviewer => $this->rainViewerFrames(),
        };
    }

    /**
     * @return array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}
     */
    private function rainViewerFrames(): array
    {
        $response = Http::timeout(15)->get('https://api.rainviewer.com/public/weather-maps.json');

        if (! $response->successful()) {
            throw new RuntimeException('RainViewer weather maps request failed.');
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        $periods = [
            ...($data['radar']['past'] ?? []),
            ...($data['radar']['nowcast'] ?? []),
        ];

        $frames = [];

        foreach ($periods as $period) {
            if (! is_array($period) || ! isset($period['time'], $period['path'])) {
                continue;
            }

            $frames[] = [
                'time' => (int) $period['time'],
                'timeIso' => CarbonImmutable::createFromTimestampUTC((int) $period['time'])->toIso8601String(),
                'path' => (string) $period['path'],
            ];
        }

        return [
            'source' => RadarSource::Rainviewer->value,
            'frames' => $frames,
        ];
    }

    /**
     * @return array{source: string, frames: list<array{time: int, timeIso: string|null, path: string|null}>}
     */
    private function noaaFrames(): array
    {
        $times = app(NoaaOpenGeoRadar::class)->availableFrameTimesIso();

        $frames = array_map(
            static fn (string $timeIso): array => [
                'time' => CarbonImmutable::parse($timeIso)->getTimestamp(),
                'timeIso' => $timeIso,
                'path' => null,
            ],
            $times,
        );

        return [
            'source' => RadarSource::Noaa->value,
            'frames' => $frames,
        ];
    }
}
