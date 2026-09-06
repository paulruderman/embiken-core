<?php

declare(strict_types=1);

namespace Embiken\Weather\Enums;

use App\Enums\Concerns\HasFrontendMeta;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum RadarSource: string implements HasColor, HasDescription, HasLabel
{
    use HasFrontendMeta;

    case Rainviewer = 'rainviewer';
    case Noaa = 'noaa';

    public function getLabel(): string
    {
        return match ($this) {
            self::Rainviewer => 'RainViewer',
            self::Noaa => 'NOAA',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Rainviewer => 'sky',
            self::Noaa => 'blue',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Rainviewer => 'Global radar tiles.',
            self::Noaa => 'CONUS WMS radar.',
        };
    }
}
