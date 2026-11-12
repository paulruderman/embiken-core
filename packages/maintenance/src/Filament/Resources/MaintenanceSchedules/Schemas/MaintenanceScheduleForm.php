<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Schemas;

use Embiken\BikeFleet\Models\Bike;
use Embiken\Maintenance\Models\MaintenanceChecklist;
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
                    ->label('Bike')
                    ->options(fn (): array => Bike::query()->orderBy('bid')->pluck('bid', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('due_at')
                    ->label('Due date')
                    ->required(),
                Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('scheduled')
                    ->required(),
                Select::make('maintenance_checklist_id')
                    ->label('Checklist template')
                    ->options(fn (): array => MaintenanceChecklist::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (int|string|null $state, Set $set): void {
                        $checklist = MaintenanceChecklist::query()->find($state);
                        $items = $checklist instanceof MaintenanceChecklist ? $checklist->items : [];

                        $set('checklist_items', array_map(fn (string $label): array => [
                            'label' => $label,
                            'completed' => false,
                        ], $items));
                    })
                    ->helperText('Items are copied to this schedule so later template edits do not change it.'),
                Repeater::make('checklist_items')
                    ->label('Scheduled checklist')
                    ->schema([
                        TextInput::make('label')->required()->maxLength(255),
                        Toggle::make('completed')->default(false),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add checklist item')
                    ->columnSpanFull(),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]);
    }
}
