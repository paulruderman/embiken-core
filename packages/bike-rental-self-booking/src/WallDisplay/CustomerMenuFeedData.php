<?php

declare(strict_types=1);

namespace Embiken\BikeRentalSelfBooking\WallDisplay;

use App\Contracts\WallDisplayFeedDataProvider;
use App\Enums\Weekday;
use App\Models\Location;
use App\Models\LocationHour;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Models\RentalPackage;

class CustomerMenuFeedData implements WallDisplayFeedDataProvider
{
    /**
     * @param  array<string, mixed>  $dayStore
     * @return array<string, mixed>
     */
    public function data(Location $location, array $dayStore): array
    {
        $packages = RentalPackage::query()
            ->where('book_visible', true)
            ->whereHas('variants')
            ->with('variants.bikeModel')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (RentalPackage $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'tagline' => $package->tagline,
                'blurb' => $package->blurb,
                'meter' => $package->meter->value,
                'meter_label' => $package->meter->getLabel(),
                'meter_unit' => match ($package->meter) {
                    PackageMeter::PerHour => __('app.wall_display_ui.customer_menu_meter_units.hour'),
                    PackageMeter::PerLine => __('app.wall_display_ui.customer_menu_meter_units.bike'),
                    PackageMeter::PerCalendarDay => __('app.wall_display_ui.customer_menu_meter_units.calendar_day'),
                    PackageMeter::None => null,
                },
                'currency' => $package->currency,
                'variants' => $package->variants->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'label' => trim($variant->bikeModel->name.' '.$variant->size),
                    'rate_minor_units' => (int) $variant->getRelationValue('pivot')->getAttribute('rate_minor_units'),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $hours = LocationHour::query()
            ->where('location_id', $location->id)
            ->orderBy('weekday')
            ->orderBy('opens_at')
            ->get()
            ->map(fn (LocationHour $hour): array => [
                'weekday' => __(match ($hour->weekday) {
                    Weekday::Sunday => 'app.wall_display_ui.weekdays.sunday',
                    Weekday::Monday => 'app.wall_display_ui.weekdays.monday',
                    Weekday::Tuesday => 'app.wall_display_ui.weekdays.tuesday',
                    Weekday::Wednesday => 'app.wall_display_ui.weekdays.wednesday',
                    Weekday::Thursday => 'app.wall_display_ui.weekdays.thursday',
                    Weekday::Friday => 'app.wall_display_ui.weekdays.friday',
                    Weekday::Saturday => 'app.wall_display_ui.weekdays.saturday',
                }),
                'opens_at' => substr($hour->opens_at, 0, 5),
                'closes_at' => substr($hour->closes_at, 0, 5),
                'closes_next_day' => $hour->closes_next_day,
            ])
            ->values()
            ->all();

        return [
            'currency' => strtolower($location->currency),
            'packages' => $packages,
            'hours' => $hours,
            'copy' => [
                'offers' => __('app.wall_display_ui.customer_menu_offers'),
                'hours' => __('app.wall_display_ui.customer_menu_hours'),
                'no_offers' => __('app.wall_display_ui.customer_menu_no_offers'),
                'no_hours' => __('app.wall_display_ui.customer_menu_no_hours'),
                'per' => __('app.wall_display_ui.customer_menu_per'),
            ],
        ];
    }
}
