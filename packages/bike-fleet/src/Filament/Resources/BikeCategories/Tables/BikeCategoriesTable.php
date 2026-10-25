<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\BrandColor;
use Embiken\BikeFleet\Models\BikeCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BikeCategoriesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.fleet.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('app.fleet.fields.description'))
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state !== null && mb_strlen($state) > 40 ? $state : null),
                ColorColumn::make('interface_color')
                    ->label(__('app.fleet.fields.interface_color'))
                    ->getStateUsing(static fn (BikeCategory $record): ?string => $record->interface_color === null
                        ? null
                        : BrandColor::swatch($record->interface_color, '#000000')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::BikeCategories,
            __('app.fleet.empty_states.bike_categories'),
        );
    }
}
