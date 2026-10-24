<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers;

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
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('amount_minor_units')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state, Transaction $record): string => Money::format($state ?? 0, $record->currency)),
                TextColumn::make('note')
                    ->placeholder('—'),
                TextColumn::make('captured_at')
                    ->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->infolist(fn (Schema $schema): Schema => TransactionInfolist::configure($schema)),
            ]);
    }
}
