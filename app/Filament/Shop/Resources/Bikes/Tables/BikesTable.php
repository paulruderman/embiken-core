<?php

namespace App\Filament\Shop\Resources\Bikes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BikesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
    }
}
