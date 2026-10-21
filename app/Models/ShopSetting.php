<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $brand_name
 * @property string|null $primary_color
 * @property string|null $brand_gray_color
 * @property string|null $logo_path
 * @property string|null $favicon_path
 */
#[Fillable(['brand_name', 'primary_color', 'brand_gray_color', 'logo_path', 'favicon_path'])]
class ShopSetting extends Model
{
    use HasFactory;

    public const int SINGLETON_ID = 1;

    public const string DEFAULT_PRIMARY_COLOR = '#336699';

    public const string DEFAULT_GRAY_COLOR = '#6B7280';

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => self::SINGLETON_ID],
            [
                'primary_color' => self::DEFAULT_PRIMARY_COLOR,
                'brand_gray_color' => self::DEFAULT_GRAY_COLOR,
            ],
        );
    }
}
