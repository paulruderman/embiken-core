<?php

namespace App\Filament\Shop\Resources\Staff\Tables;

use App\Models\Staff;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge(),
                IconColumn::make('is_platform_manager')
                    ->label('Platform')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Staff $record): bool => ! $record->is_platform_manager),
            ])
            ->toolbarActions([]);
    }
}
