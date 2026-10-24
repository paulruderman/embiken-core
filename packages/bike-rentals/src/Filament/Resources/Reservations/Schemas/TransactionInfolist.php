<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Schemas;

use App\Support\Money;
use Embiken\BikeRentals\Models\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->schema([
                        TextEntry::make('kind')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('amount_minor_units')
                            ->label('Amount')
                            ->formatStateUsing(fn (?int $state, Transaction $record): ?string => $state === null
                                ? null
                                : Money::format($state, $record->currency)),
                        TextEntry::make('captured_at')
                            ->dateTime()
                            ->placeholder('Not captured'),
                        TextEntry::make('failed_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('note')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Reservation and customer')
                    ->schema([
                        TextEntry::make('reservation.id')
                            ->label('Reservation'),
                        TextEntry::make('reservation.customer.name')
                            ->label('Customer')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Stripe Connect')
                    ->schema([
                        TextEntry::make('payment_intent_id')
                            ->label('Payment Intent')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('charge_id')
                            ->label('Charge')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('original_transaction_id')
                            ->label('Original Transaction')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
