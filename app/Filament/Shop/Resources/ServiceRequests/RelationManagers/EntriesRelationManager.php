<?php

namespace App\Filament\Shop\Resources\ServiceRequests\RelationManagers;

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
                    ->relationship('staff', 'name')
                    ->default(fn (): ?int => Auth::guard('staff')->id()),
                Textarea::make('notes'),
                TextInput::make('labor_minutes')
                    ->numeric()
                    ->minValue(0),
                DateTimePicker::make('work_started_at'),
                DateTimePicker::make('work_completed_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notes')
            ->columns([
                TextColumn::make('staff.name'),
                TextColumn::make('notes')
                    ->limit(40),
                TextColumn::make('labor_minutes'),
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
