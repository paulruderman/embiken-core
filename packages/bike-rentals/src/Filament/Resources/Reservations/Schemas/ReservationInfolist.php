<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Schemas;

use App\Support\Money;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReservationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('stage')
                    ->badge(),
                TextEntry::make('starts_at')
                    ->dateTime(),
                TextEntry::make('ends_at')
                    ->dateTime(),
                TextEntry::make('owed')
                    ->label('Owed')
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextEntry::make('paid')
                    ->label('Paid')
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextEntry::make('rentalPackage.name')
                    ->placeholder('—'),
                TextEntry::make('notes')
                    ->placeholder('—'),
                TextEntry::make('damage_notes')
                    ->placeholder('—'),
            ]);
    }
}
