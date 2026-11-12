<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceChecklists\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MaintenanceChecklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Repeater::make('items')
                    ->simple(TextInput::make('item')->required()->maxLength(255))
                    ->label('Checklist items')
                    ->required()
                    ->minItems(1)
                    ->addActionLabel('Add checklist item')
                    ->columnSpanFull(),
            ]);
    }
}
