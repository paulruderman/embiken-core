<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BikeModelsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.fleet.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manufacturer')
                    ->label(__('app.fleet.fields.manufacturer'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('bikeCategory.name')
                    ->label(__('app.fleet.fields.category')),
                TextColumn::make('padding_minutes')
                    ->label(__('app.fleet.fields.padding_minutes')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::BikeModels,
            __('app.fleet.empty_states.bike_models'),
        );
    }
}
