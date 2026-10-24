<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers\RelationManagers;

use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('Reservation')
                    ->sortable(),
                TextColumn::make('stage')
                    ->badge(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime(),
                TextColumn::make('owed')
                    ->label('Owed (cents)'),
                TextColumn::make('paid')
                    ->label('Paid (cents)'),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('viewReservation')
                    ->label('View')
                    ->url(fn (Reservation $record): string => ReservationResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([]);
    }
}
