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
                    ->label(__('app.rentals_ui.fields.customer')),
                TextEntry::make('stage')
                    ->label(__('app.rentals_ui.fields.stage'))
                    ->badge()
                    ->color(fn (Reservation $record): string => $record->stage->getColor()),
                TextEntry::make('starts_at')
                    ->label(__('app.rentals_ui.fields.starts_at'))
                    ->dateTime(),
                TextEntry::make('ends_at')
                    ->label(__('app.rentals_ui.fields.ends_at'))
                    ->dateTime(),
                TextEntry::make('owed')
                    ->label(__('app.rentals_ui.fields.owed'))
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextEntry::make('paid')
                    ->label(__('app.rentals_ui.fields.paid'))
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextEntry::make('rentalPackage.name')
                    ->label(__('app.rentals_ui.fields.rental_package'))
                    ->placeholder('—'),
                TextEntry::make('notes')
                    ->label(__('app.rentals_ui.fields.notes'))
                    ->placeholder('—'),
                TextEntry::make('damage_notes')
                    ->label(__('app.rentals_ui.fields.damage_notes'))
                    ->placeholder('—'),
            ]);
    }
}
