<?php

namespace App\Filament\Shop\Resources\Displays\Tables;

use App\Support\FeedRegistry;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DisplaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.display_ui.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('feed_slug')
                    ->label(__('app.display_ui.feed'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === null
                        ? '—'
                        : (app(FeedRegistry::class)->findBySlug($state)['name'] ?? $state)),
                TextColumn::make('standby_feed_slug')
                    ->label(__('app.display_ui.standby_feed'))
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state === null
                        ? '—'
                        : (app(FeedRegistry::class)->findBySlug($state)['name'] ?? $state)),
                IconColumn::make('standby')
                    ->label(__('app.display_ui.standby'))
                    ->boolean(),
                IconColumn::make('dark_mode')
                    ->label(__('app.display_ui.dark_mode'))
                    ->boolean(),
                TextColumn::make('devices_count')
                    ->counts('devices')
                    ->label(__('app.device_ui.assigned_devices')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
