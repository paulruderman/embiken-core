<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceSurface;
use Carbon\CarbonImmutable;
use Database\Factories\DeviceFactory;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $location_id
 * @property int|null $display_id
 * @property int|null $paired_terminal_device_id
 * @property int|null $focused_reservation_id
 * @property string $name
 * @property DeviceSurface $surface
 * @property string|null $pairing_code_hash
 * @property CarbonImmutable|null $pairing_expires_at
 * @property CarbonImmutable|null $paired_at
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'location_id',
    'display_id',
    'paired_terminal_device_id',
    'focused_reservation_id',
    'name',
    'surface',
    'pairing_code_hash',
    'pairing_expires_at',
    'paired_at',
    'last_seen_at',
])]
#[Hidden(['pairing_code_hash'])]
class Device extends Model
{
    use HasApiTokens;

    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'surface' => DeviceSurface::class,
            'pairing_expires_at' => 'immutable_datetime',
            'paired_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<Display, $this> */
    public function display(): BelongsTo
    {
        return $this->belongsTo(Display::class);
    }

    /** @return BelongsTo<Device, $this> */
    public function pairedTerminalDevice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'paired_terminal_device_id');
    }

    /** @return BelongsTo<Reservation, $this> */
    public function focusedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'focused_reservation_id');
    }

    /** @return HasMany<Device, $this> */
    public function customerDisplays(): HasMany
    {
        return $this->hasMany(self::class, 'paired_terminal_device_id');
    }
}
