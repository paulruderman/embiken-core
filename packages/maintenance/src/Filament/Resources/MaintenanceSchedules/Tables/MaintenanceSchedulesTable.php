<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bike.bid')->label('Bike')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('due_at')->date()->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'completed' => 'success',
                    'cancelled' => 'gray',
                    default => 'warning',
                }),
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
