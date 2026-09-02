<?php

namespace App\Filament\Shop\Resources\RentalPackages\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('rate_cents')
                    ->numeric()
                    ->required()
                    ->minValue(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('size')
            ->columns([
                TextColumn::make('bikeModel.name')
                    ->label('Model'),
                TextColumn::make('size'),
                TextColumn::make('rate_cents')
                    ->label('Rate (cents)'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('rate_cents')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
