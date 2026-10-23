<?php

namespace App\Models;

use Database\Factories\ShopLandingPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $layout
 * @property array<string, mixed> $layout_data
 * @property string $theme
 * @property array<string, mixed>|null $custom_content
 * @property list<array{message: string, tone: string}>|null $booking_notices
 */
#[Fillable(['layout', 'layout_data', 'theme', 'custom_content', 'booking_notices'])]
class ShopLandingPage extends Model
{
    /** @use HasFactory<ShopLandingPageFactory> */
    use HasFactory;

    public const int SINGLETON_ID = 1;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'layout_data' => 'array',
            'custom_content' => 'array',
            'booking_notices' => 'array',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->find(self::SINGLETON_ID);
    }
}
