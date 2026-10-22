<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BikesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('bid')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('variant.bikeModel.name')
                    ->label('Model'),
                TextColumn::make('variant.size')
                    ->label('Size'),
                IconColumn::make('in_service')
                    ->boolean(),
                IconColumn::make('self_bookable')
                    ->boolean(),
                TextColumn::make('bike_situation_state')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::Bikes,
            'No bikes in your fleet yet',
        );
    }
}
