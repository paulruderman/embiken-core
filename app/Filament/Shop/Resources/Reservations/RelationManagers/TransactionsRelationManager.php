<?php

namespace App\Filament\Shop\Resources\Reservations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
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
                TextColumn::make('amount_cents')
                    ->label('Amount'),
                TextColumn::make('note')
                    ->placeholder('—'),
                TextColumn::make('captured_at')
                    ->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
