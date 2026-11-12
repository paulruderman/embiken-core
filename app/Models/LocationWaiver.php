<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LocationWaiverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\Translatable\HasTranslations;

/** @property int $id @property int $location_id @property int $version @property string $content_sha256 */
#[Fillable(['location_id', 'version', 'content', 'content_sha256'])]
class LocationWaiver extends Model
{
    /** @use HasFactory<LocationWaiverFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['content'];

    public bool $useFallbackLocale = false;

    protected static function newFactory(): LocationWaiverFactory
    {
        return LocationWaiverFactory::new();
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Location waiver versions are immutable.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Location waiver versions are immutable.');
        });
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
