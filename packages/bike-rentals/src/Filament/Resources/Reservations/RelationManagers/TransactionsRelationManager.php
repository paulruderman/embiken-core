<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use App\Support\Money;
use Embiken\BikeRentals\Filament\Resources\Reservations\Schemas\TransactionInfolist;
use Embiken\BikeRentals\Models\Transaction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('kind')
                    ->label(__('app.rentals_ui.fields.transaction_type'))
                    ->badge()
                    ->color(fn (Transaction $record): string => $record->kind->getColor()),
                TextColumn::make('status')
                    ->label(__('app.rentals_ui.fields.payment_status'))
                    ->badge()
                    ->color(fn (Transaction $record): string => $record->status->getColor()),
                TextColumn::make('amount_minor_units')
                    ->label(__('app.rentals_ui.fields.amount'))
                    ->formatStateUsing(fn (?int $state, Transaction $record): string => Money::format($state ?? 0, $record->currency)),
                TextColumn::make('note')
                    ->label(__('app.rentals_ui.fields.note'))
                    ->placeholder('—'),
                LocalizedDateTimeColumn::make('captured_at')
                    ->label(__('app.rentals_ui.fields.captured_at'))
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->infolist(fn (Schema $schema): Schema => TransactionInfolist::configure($schema)),
            ]);
    }
}
