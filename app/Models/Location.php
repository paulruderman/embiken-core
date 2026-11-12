<?php

namespace App\Models;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use Carbon\CarbonImmutable;
use Database\Factories\LocationFactory;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeRentals\Models\RentalPackage;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $timezone
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $administrative_area
 * @property string|null $postal_code
 * @property string|null $country_code
 * @property string $week_start_day
 * @property string|null $customer_contact_email
 * @property string $currency
 * @property int $minimum_turnaround_buffer_minutes
 * @property int $reservation_readiness_window_minutes
 * @property list<string> $enabled_content_locales
 * @property string $default_content_locale
 * @property BikeAssignmentPolicy $bike_assignment_policy
 * @property ReturnSituation $return_situation
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name',
    'timezone',
    'address_line_1',
    'address_line_2',
    'city',
    'administrative_area',
    'postal_code',
    'country_code',
    'week_start_day',
    'customer_contact_email',
    'currency',
    'minimum_turnaround_buffer_minutes',
    'reservation_readiness_window_minutes',
    'enabled_content_locales',
    'default_content_locale',
    'bike_assignment_policy',
    'return_situation',
])]
class Location extends Model
{
    public const string SETUP_DESCRIPTION = 'Book needs opening hours for customers to choose a rental interval.';

    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Location $location): void {
            $location->currency = strtolower(trim($location->currency));

            foreach ([
                'address_line_1',
                'address_line_2',
                'city',
                'administrative_area',
                'postal_code',
            ] as $attribute) {
                $value = $location->getAttribute($attribute);
                $location->setAttribute($attribute, is_string($value) && trim($value) !== '' ? trim($value) : null);
            }

            $countryCode = $location->country_code;
            $location->country_code = is_string($countryCode) && trim($countryCode) !== ''
                ? strtoupper(trim($countryCode))
                : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_turnaround_buffer_minutes' => 'integer',
            'reservation_readiness_window_minutes' => 'integer',
            'enabled_content_locales' => 'array',
            'bike_assignment_policy' => BikeAssignmentPolicy::class,
            'return_situation' => ReturnSituation::class,
        ];
    }

    /** @return list<string> */
    public function getEnabledContentLocalesAttribute(mixed $value): array
    {
        $supported = ['en', 'es', 'fr'];
        $locales = is_array($value)
            ? $value
            : (is_string($value) ? json_decode($value, true) : null);
        $locales = is_array($locales) ? $locales : ['en'];
        $locales = array_values(array_intersect($supported, $locales));

        return $locales === [] ? ['en'] : $locales;
    }

    public function getDefaultContentLocaleAttribute(mixed $value): string
    {
        return in_array($value, $this->enabled_content_locales, true) ? $value : $this->enabled_content_locales[0];
    }

    public function formattedAddress(): string
    {
        $street = array_values(array_filter([
            trim((string) $this->address_line_1),
            trim((string) $this->address_line_2),
        ]));
        $locality = trim(implode(', ', array_filter([
            trim((string) $this->city),
            trim((string) $this->administrative_area),
        ])));
        $locality = trim(implode(' ', array_filter([
            $locality,
            trim((string) $this->postal_code),
        ])));

        return implode(', ', array_values(array_filter([
            ...$street,
            $locality,
            strtoupper(trim((string) $this->country_code)),
        ])));
    }

    /**
     * @return HasMany<LocationHour, $this>
     */
    public function hours(): HasMany
    {
        return $this->hasMany(LocationHour::class);
    }

    /**
     * @return HasMany<LocationClosedDate, $this>
     */
    public function closedDates(): HasMany
    {
        return $this->hasMany(LocationClosedDate::class);
    }

    /**
     * @return HasMany<Bike, $this>
     */
    public function bikes(): HasMany
    {
        return $this->hasMany(Bike::class);
    }

    /**
     * @return HasMany<RentalPackage, $this>
     */
    public function rentalPackages(): HasMany
    {
        return $this->hasMany(RentalPackage::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<LocationWaiver, $this> */
    public function waivers(): HasMany
    {
        return $this->hasMany(LocationWaiver::class)->orderByDesc('version');
    }

    public function currentWaiver(): ?LocationWaiver
    {
        return $this->waivers()->first();
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /** @return HasMany<Display, $this> */
    public function displays(): HasMany
    {
        return $this->hasMany(Display::class);
    }

    /**
     * @return HasMany<Staff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
