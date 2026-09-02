<?php

namespace App\Filament\Shop\Resources\RentalPackages\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RentalPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('meter')
                    ->badge(),
                TextColumn::make('confirm_threshold')
                    ->badge(),
                IconColumn::make('book_visible')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
