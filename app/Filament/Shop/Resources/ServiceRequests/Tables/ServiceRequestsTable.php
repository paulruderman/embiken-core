<?php

namespace App\Filament\Shop\Resources\ServiceRequests\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bike.bid')
                    ->label('Bike')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('stage')
                    ->badge(),
                IconColumn::make('blocks_usage')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
