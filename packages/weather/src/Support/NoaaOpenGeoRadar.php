<?php

declare(strict_types=1);

namespace Embiken\Weather\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class NoaaOpenGeoRadar
{
    public const WMS_BASE_URL = 'https://opengeo.ncep.noaa.gov/geoserver/conus/ows';

    public const LAYER = 'conus_bref_qcd';

    public const STYLE = 'radar_reflectivity';

    public const PAST_MINUTES = 120;

    public const STRIDE_MINUTES = 5;

    /**
     * @return list<string>
     */
    public function availableFrameTimesIso(?int $pastMinutes = null, ?int $strideMinutes = null): array
    {
        $pastMinutes ??= self::PAST_MINUTES;
        $strideMinutes ??= self::STRIDE_MINUTES;

        $rawTimes = $this->fetchRawFrameTimesIso();
        $cutoff = CarbonImmutable::now('UTC')->subMinutes($pastMinutes);

        $filtered = array_values(array_filter(
            $rawTimes,
            static fn (string $time): bool => CarbonImmutable::parse($time)->greaterThanOrEqualTo($cutoff),
        ));

        if ($filtered === []) {
            return [];
        }

        return $this->applyStride($filtered, $strideMinutes);
    }

    /**
     * @return list<string>
     */
    private function fetchRawFrameTimesIso(): array
    {
        $response = Http::timeout(15)->get(self::WMS_BASE_URL, [
            'service' => 'WMS',
            'version' => '1.3.0',
            'request' => 'GetCapabilities',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('NOAA OpenGeo GetCapabilities request failed.');
        }

        $xml = @simplexml_load_string($response->body());

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('NOAA OpenGeo GetCapabilities response was not valid XML.');
        }

        $xml->registerXPathNamespace('wms', 'http://www.opengis.net/wms');

        /** @var array<int, SimpleXMLElement> $dimensions */
        $dimensions = $xml->xpath("//wms:Layer[wms:Name='".self::LAYER."']/wms:Dimension[@name='time']") ?: [];

        if ($dimensions === []) {
            throw new RuntimeException('NOAA OpenGeo capabilities did not include time dimension for conus_bref_qcd.');
        }

        $values = trim((string) $dimensions[0]);

        if ($values === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $values))));
    }

    /**
     * @param  list<string>  $times
     * @return list<string>
     */
    private function applyStride(array $times, int $strideMinutes): array
    {
        $result = [];
        $lastKept = null;

        foreach ($times as $time) {
            $instant = CarbonImmutable::parse($time);

            if ($lastKept === null || $lastKept->diffInMinutes($instant) >= $strideMinutes) {
                $result[] = $time;
                $lastKept = $instant;
            }
        }

        return $result;
    }
}
