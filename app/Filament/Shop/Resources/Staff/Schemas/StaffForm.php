<?php

namespace App\Filament\Shop\Resources\Staff\Schemas;

use App\Enums\StaffRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->options(StaffRole::class)
                    ->required(),
            ]);
    }
}
