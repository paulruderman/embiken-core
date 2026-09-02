<?php

namespace App\Filament\Shop\Resources\Reservations\Schemas;

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
                    ->label('Owed (cents)'),
                TextEntry::make('paid')
                    ->label('Paid (cents)'),
                TextEntry::make('rentalPackage.name')
                    ->placeholder('—'),
                TextEntry::make('notes')
                    ->placeholder('—'),
                TextEntry::make('damage_notes')
                    ->placeholder('—'),
            ]);
    }
}
