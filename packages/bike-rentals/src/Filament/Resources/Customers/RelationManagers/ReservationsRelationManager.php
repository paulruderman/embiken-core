<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers\RelationManagers;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
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
                    ->label(__('app.rentals_ui.fields.reservation'))
                    ->sortable(),
                TextColumn::make('stage')
                    ->label(__('app.rentals_ui.fields.stage'))
                    ->badge(),
                LocalizedDateTimeColumn::make('starts_at')
                    ->label(__('app.rentals_ui.fields.starts_at'))
                    ->sortable(),
                LocalizedDateTimeColumn::make('ends_at')
                    ->label(__('app.rentals_ui.fields.ends_at'))
                    ->sortable(),
                TextColumn::make('owed')
                    ->label(__('app.rentals_ui.fields.owed'))
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextColumn::make('paid')
                    ->label(__('app.rentals_ui.fields.paid'))
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('viewReservation')
                    ->label(__('app.rentals_ui.actions.view'))
                    ->url(fn (Reservation $record): string => ReservationResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([]);
    }
}
