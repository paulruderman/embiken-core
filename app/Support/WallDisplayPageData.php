<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\WallDisplayFeedDataProvider;
use App\Models\Device;
use App\Models\Display;
use App\Models\Location;
use InvalidArgumentException;

class WallDisplayPageData
{
    public function __construct(private FeedRegistry $feeds) {}

    /** @return array<string, mixed> */
    public function forDevice(Device $device): array
    {
        $location = $device->location()->firstOrFail();
        $display = $device->display;
        $fullDayStore = $display === null ? null : $this->dayStore($location);
        $displayState = $display === null
            ? ['state' => 'no_display', 'display' => null, 'feed' => null]
            : $this->forDisplay($display, $fullDayStore);

        return [
            ...$displayState,
            'dayStore' => $fullDayStore === null ? null : $this->wallDisplaySnapshot($fullDayStore),
            'copy' => [
                'page_title' => __('app.wall_display_ui.page_title'),
                'not_configured' => __('app.wall_display_ui.not_configured'),
                'feed_unavailable' => __('app.wall_display_ui.feed_unavailable'),
                'display_unassigned' => __('app.wall_display_ui.display_unassigned'),
                'standby' => __('app.wall_display_ui.standby'),
                'bikes' => __('app.wall_display_ui.bikes'),
                'reservations' => __('app.wall_display_ui.reservations'),
                'no_bikes' => __('app.wall_display_ui.no_bikes'),
                'no_reservations' => __('app.wall_display_ui.no_reservations'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $dayStore
     * @return array{state: string, display: array<string, mixed>, feed: array<string, mixed>|null}
     */
    public function forDisplay(Display $display, ?array $dayStore = null): array
    {
        $feedSlug = $display->standby
            ? ($display->standby_feed_slug ?? 'standby')
            : $display->feed_slug;

        $displayData = [
            'id' => $display->id,
            'name' => $display->name,
            'feed_slug' => $display->feed_slug,
            'standby_feed_slug' => $display->standby_feed_slug,
            'standby' => $display->standby,
            'dark_mode' => $display->dark_mode,
        ];
        $location = $display->location()->firstOrFail();

        if ($feedSlug === null) {
            return ['state' => 'not_configured', 'display' => $displayData, 'feed' => null];
        }

        if ($feedSlug === 'standby') {
            return [
                'state' => 'ready',
                'display' => $displayData,
                'feed' => [
                    'slug' => 'standby',
                    'name' => __('app.wall_display_ui.standby'),
                    'component' => 'core:standby',
                    'data' => [],
                ],
            ];
        }

        $definition = $this->feeds->findBySlug($feedSlug);

        if ($definition === null) {
            return ['state' => 'feed_unavailable', 'display' => $displayData, 'feed' => null];
        }

        $data = [];
        $providerClass = $definition['provider'] ?? null;

        if ($providerClass !== null) {
            $provider = app($providerClass);

            if (! $provider instanceof WallDisplayFeedDataProvider) {
                throw new InvalidArgumentException("Feed provider [{$providerClass}] must implement [".WallDisplayFeedDataProvider::class.']');
            }

            $data = $provider->data($location, $dayStore ?? $this->dayStore($location));
        }

        return [
            'state' => 'ready',
            'display' => $displayData,
            'feed' => [
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'component' => $definition['component'],
                'data' => $data,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dayStore(Location $location): array
    {
        $snapshot = DayStore::forLocation($location);

        return [
            ...$snapshot,
            'bike_assignment_policy' => $location->bike_assignment_policy->value,
            'bikes' => $snapshot['modules']['fleet']['bikes'] ?? [],
            'reservations' => $snapshot['modules']['rentals']['reservations'] ?? [],
        ];
    }

    /** @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function wallDisplaySnapshot(array $snapshot): array
    {
        $bikes = array_map(static fn (array $bike): array => $bike, $snapshot['bikes']);
        $reservations = array_map(static function (array $reservation): array {
            $reservation['customer'] = ['id' => 0, 'name' => ''];
            $reservation['owed_minor_units'] = 0;
            $reservation['paid_minor_units'] = 0;
            $reservation['myrental_token'] = null;
            $reservation['waiver_accepted_at'] = null;
            $reservation['lines'] = array_map(static function (array $line): array {
                $line['rider_name'] = null;
                $line['rider_height_cm'] = null;

                return $line;
            }, $reservation['lines']);

            return $reservation;
        }, $snapshot['reservations']);

        return [
            'tenant_id' => $snapshot['tenant_id'],
            'location_id' => $snapshot['location_id'],
            'schedule_date' => $snapshot['schedule_date'],
            'schedule_starts_at' => $snapshot['schedule_starts_at'],
            'schedule_ends_at' => $snapshot['schedule_ends_at'],
            'business_hours' => $snapshot['business_hours'],
            'minimum_turnaround_buffer_minutes' => $snapshot['minimum_turnaround_buffer_minutes'],
            'bike_assignment_policy' => $snapshot['bike_assignment_policy'],
            'reservation_readiness_window_minutes' => $snapshot['reservation_readiness_window_minutes'],
            'timezone' => $snapshot['timezone'],
            'currency' => $snapshot['currency'],
            'return_situation' => $snapshot['return_situation'],
            'bikes' => $bikes,
            'reservations' => $reservations,
        ];
    }
}
