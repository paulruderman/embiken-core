<?php

declare(strict_types=1);

namespace Embiken\Weather\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NwsClient
{
    public const USER_AGENT = 'EmbikenWeather/1.0 (https://embiken.com; weather@embiken.com)';

    /**
     * @return array{office: string, gridX: int, gridY: int}
     */
    public function points(float $latitude, float $longitude): array
    {
        $lat = number_format($latitude, 4, '.', '');
        $lng = number_format($longitude, 4, '.', '');

        $response = $this->get("https://api.weather.gov/points/{$lat},{$lng}");

        $office = data_get($response, 'properties.gridId');
        $gridX = data_get($response, 'properties.gridX');
        $gridY = data_get($response, 'properties.gridY');

        if (! is_string($office) || $office === '' || $gridX === null || $gridY === null) {
            throw new RuntimeException('NWS points response did not include a grid.');
        }

        return [
            'office' => $office,
            'gridX' => (int) $gridX,
            'gridY' => (int) $gridY,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function hourlyForecast(string $office, int $gridX, int $gridY): array
    {
        $response = $this->get("https://api.weather.gov/gridpoints/{$office}/{$gridX},{$gridY}/forecast/hourly");

        $periods = data_get($response, 'properties.periods');

        if (! is_array($periods) || $periods === []) {
            throw new RuntimeException('NWS hourly forecast did not include periods.');
        }

        return array_values($periods);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $url): array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/geo+json',
            ])
            ->get($url);

        $this->assertSuccessful($response);

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('NWS response was not JSON.');
        }

        return $json;
    }

    private function assertSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException('NWS request failed with status '.$response->status());
    }
}
