<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->label(__('app.fleet.fields.staff'))
                    ->relationship('staff', 'name')
                    ->default(function (): ?int {
                        $id = Auth::guard('staff')->id();

                        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int) $id : null);
                    }),
                Textarea::make('notes')->label(__('app.fleet.fields.notes')),
                TextInput::make('labor_minutes')
                    ->label(__('app.fleet.fields.labor_minutes'))
                    ->numeric()
                    ->minValue(0),
                DateTimePicker::make('work_started_at')->label(__('app.fleet.fields.work_started_at')),
                DateTimePicker::make('work_completed_at')->label(__('app.fleet.fields.work_completed_at')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notes')
            ->columns([
                TextColumn::make('staff.name')->label(__('app.fleet.fields.staff')),
                TextColumn::make('notes')
                    ->label(__('app.fleet.fields.notes'))
                    ->limit(40),
                TextColumn::make('labor_minutes')->label(__('app.fleet.fields.labor_minutes')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
