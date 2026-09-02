<?php

namespace App\Filament\Platform\Resources\Users\Tables;

use App\Actions\Platform\DisableUserAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                IconColumn::make('disabled_at')
                    ->label('Disabled')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->disabled_at !== null),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('disable')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->disabled_at === null)
                    ->action(fn (User $record) => app(DisableUserAction::class)($record)),
            ])
            ->toolbarActions([]);
    }
}
