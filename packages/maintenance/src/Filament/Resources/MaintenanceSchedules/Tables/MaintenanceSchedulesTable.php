<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Tables;

use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bike.bid')->label(__('app.maintenance_ui.bike'))->searchable()->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('due_at')->date()->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'completed' => 'success',
                    'cancelled' => 'gray',
                    default => 'warning',
                }),
                TextColumn::make('serviceRequest.description')
                    ->label(__('app.maintenance_ui.work_history'))
                    ->url(fn ($record): ?string => $record->service_request_id === null ? null : ServiceRequestResource::getUrl('edit', ['record' => $record->service_request_id]))
                    ->openUrlInNewTab(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
