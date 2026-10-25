<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Tables;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceChecklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('items')->label(__('app.maintenance_ui.items'))->getStateUsing(fn ($record): int => count($record->items ?? [])),
                LocalizedDateTimeColumn::make('updated_at')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
