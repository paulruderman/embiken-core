<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers\RelationManagers;

use App\Support\Money;
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
                    ->label('Owed')
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextColumn::make('paid')
                    ->label('Paid')
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
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
