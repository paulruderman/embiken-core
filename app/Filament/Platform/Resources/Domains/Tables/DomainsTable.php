<?php

namespace App\Filament\Platform\Resources\Domains\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->label('Hostname')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
