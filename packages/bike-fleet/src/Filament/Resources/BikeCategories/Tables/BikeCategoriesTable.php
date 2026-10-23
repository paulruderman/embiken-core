<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeCategories\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\BrandColor;
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(40),
                ColorColumn::make('interface_color')
                    ->label('Interface color')
                    ->getStateUsing(static fn (?string $state): ?string => $state === null
                        ? null
                        : BrandColor::swatch($state, '#000000')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::BikeCategories,
            'No bike categories yet',
        );
    }
}
