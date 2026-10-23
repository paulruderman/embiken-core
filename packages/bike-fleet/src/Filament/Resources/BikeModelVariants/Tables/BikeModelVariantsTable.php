<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\BrandColor;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BikeModelVariantsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('bikeModel.name')
                    ->label('Model')
                    ->searchable(),
                TextColumn::make('size')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('color_name')
                    ->label('Color')
                    ->toggleable(),
                ColorColumn::make('color_value')
                    ->label('Swatch')
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
            ShopSetupReadiness::BikeModelVariants,
            'No bike model variants yet',
        );
    }
}
