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
                Section::make(__('app.rentals_ui.sections.transaction'))
                    ->schema([
                        TextEntry::make('kind')
                            ->label(__('app.rentals_ui.fields.transaction_type'))
                            ->badge()
                            ->color(fn (Transaction $record): string => $record->kind->getColor()),
                        TextEntry::make('status')
                            ->label(__('app.rentals_ui.fields.payment_status'))
                            ->badge()
                            ->color(fn (Transaction $record): string => $record->status->getColor()),
                        TextEntry::make('amount_minor_units')
                            ->label(__('app.rentals_ui.fields.amount'))
                            ->formatStateUsing(fn (?int $state, Transaction $record): ?string => $state === null
                                ? null
                                : Money::format($state, $record->currency)),
                        TextEntry::make('captured_at')
                            ->label(__('app.rentals_ui.fields.captured_at'))
                            ->dateTime()
                            ->placeholder(__('app.rentals_ui.table.not_captured')),
                        TextEntry::make('failed_at')
                            ->label(__('app.rentals_ui.fields.failed_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('note')
                            ->label(__('app.rentals_ui.fields.note'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('app.rentals_ui.sections.reservation_and_customer'))
                    ->schema([
                        TextEntry::make('reservation.public_id')
                            ->label(__('app.rentals_ui.fields.reservation')),
                        TextEntry::make('reservation.customer.name')
                            ->label(__('app.rentals_ui.fields.customer'))
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make(__('app.rentals_ui.sections.stripe_connect'))
                    ->schema([
                        TextEntry::make('payment_intent_id')
                            ->label(__('app.rentals_ui.fields.payment_intent'))
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('charge_id')
                            ->label(__('app.rentals_ui.fields.charge'))
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('original_transaction_id')
                            ->label(__('app.rentals_ui.fields.original_transaction'))
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
