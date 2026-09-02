<?php

namespace App\Filament\Shop\Resources\Customers\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CustomerForm
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
                    ->required(),
                TextInput::make('phone')
                    ->required(),
                Placeholder::make('owed_paid')
                    ->label('Owed / paid')
                    ->content(function (?Model $record): string {
                        if ($record === null) {
                            return '—';
                        }

                        $owed = (int) $record->reservations()->sum('owed');
                        $paid = (int) $record->reservations()->sum('paid');

                        return "{$owed} owed / {$paid} paid (cents)";
                    }),
            ]);
    }
}
