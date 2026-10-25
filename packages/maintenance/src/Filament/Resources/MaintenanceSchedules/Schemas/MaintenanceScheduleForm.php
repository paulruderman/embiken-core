<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Schemas;

use Embiken\BikeFleet\Models\Bike;
use Embiken\Maintenance\Models\MaintenanceChecklist;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MaintenanceScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bike_id')
                    ->label(__('app.maintenance_ui.bike'))
                    ->options(fn (): array => Bike::query()->orderBy('bid')->pluck('bid', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')
                    ->required(),
                TextInput::make('title')
                    ->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('due_at')
                    ->label(__('app.maintenance_ui.due_date'))
                    ->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')
                    ->required(),
                Select::make('status')
                    ->options(fn (?MaintenanceSchedule $record): array => [
                        'scheduled' => __('app.maintenance_ui.statuses.scheduled'),
                        'cancelled' => __('app.maintenance_ui.statuses.cancelled'),
                    ] + ($record?->status === 'completed' ? [
                        'completed' => __('app.maintenance_ui.statuses.completed'),
                    ] : []))
                    ->default('scheduled')
                    ->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')
                    ->required(),
                Select::make('maintenance_checklist_id')
                    ->label(__('app.maintenance_ui.checklist_template'))
                    ->options(fn (): array => MaintenanceChecklist::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')
                    ->afterStateUpdated(function (int|string|null $state, Set $set): void {
                        $checklist = MaintenanceChecklist::query()->find($state);
                        $items = $checklist instanceof MaintenanceChecklist ? $checklist->items : [];

                        $set('checklist_items', array_map(fn (string $label): array => [
                            'label' => $label,
                            'completed' => false,
                        ], $items));
                    })
                    ->helperText(__('app.maintenance_ui.template_copy_help')),
                Repeater::make('checklist_items')
                    ->label(__('app.maintenance_ui.scheduled_checklist'))
                    ->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')
                    ->schema([
                        TextInput::make('label')->required()->maxLength(255),
                        Toggle::make('completed')->default(false),
                    ])
                    ->columns(2)
                    ->addActionLabel(__('app.maintenance_ui.add_checklist_item'))
                    ->columnSpanFull(),
                Textarea::make('notes')->rows(3)->disabled(fn (?MaintenanceSchedule $record): bool => $record?->status === 'completed')->columnSpanFull(),
            ]);
    }
}
