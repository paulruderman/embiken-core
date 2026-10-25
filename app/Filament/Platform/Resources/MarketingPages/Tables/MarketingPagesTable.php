<?php

namespace App\Filament\Platform\Resources\MarketingPages\Tables;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketingPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('page_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'article' ? 'Journal article' : 'Page'),
                LocalizedDateTimeColumn::make('published_at')
                    ->label('Publication')
                    ->sortable()
                    ->placeholder('Draft'),
                LocalizedDateTimeColumn::make('updated_at')
                    ->label('Updated')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
