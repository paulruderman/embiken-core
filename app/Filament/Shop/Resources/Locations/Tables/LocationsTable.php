<?php

namespace App\Filament\Shop\Resources\Locations\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('timezone'),
                TextColumn::make('currency'),
                TextColumn::make('minimum_turnaround_buffer_minutes')
                    ->label('Buffer (min)'),
                TextColumn::make('bike_assignment_policy')
                    ->badge(),
                TextColumn::make('return_situation')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
