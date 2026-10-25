<?php

namespace App\Filament\Shop\Resources\Locations\Tables;

use App\Models\Location;
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
                TextColumn::make('currency')
                    ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state)),
                TextColumn::make('minimum_turnaround_buffer_minutes')
                    ->label('Buffer (min)'),
                TextColumn::make('reservation_readiness_window_minutes')
                    ->label(__('app.location_ui.readiness_window_short')),
                TextColumn::make('bike_assignment_policy')
                    ->badge()
                    ->color(fn (Location $record): string => $record->bike_assignment_policy->getColor()),
                TextColumn::make('return_situation')
                    ->badge()
                    ->color(fn (Location $record): string => $record->return_situation->getColor()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
