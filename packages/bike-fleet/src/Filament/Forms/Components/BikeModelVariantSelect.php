<?php

namespace Embiken\BikeFleet\Filament\Forms\Components;

use Embiken\BikeFleet\Models\BikeModelVariant;
use Filament\Forms\Components\Select;

class BikeModelVariantSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options(fn (): array => BikeModelVariant::query()
                ->with('bikeModel')
                ->get()
                ->mapWithKeys(fn (BikeModelVariant $variant): array => [
                    $variant->getKey() => self::labelFor($variant),
                ])
                ->all())
            ->searchable();
    }

    public static function labelFor(BikeModelVariant $variant): string
    {
        $model = $variant->bikeModel;
        $label = collect([
            $model?->manufacturer,
            $model?->name,
            $variant->size,
        ])->filter()->implode(' ');

        return filled($variant->color_name)
            ? $label.' ('.$variant->color_name.')'
            : $label;
    }
}
