<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers\Schemas;

use App\Support\Money;
use Embiken\BikeRentals\Models\Customer;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                    ->content(function (?Customer $record): string {
                        if ($record === null) {
                            return '—';
                        }

                        return $record->reservations()
                            ->selectRaw('currency, SUM(owed) AS owed, SUM(paid) AS paid')
                            ->groupBy('currency')
                            ->get()
                            ->map(fn (object $totals): string => Money::format((int) $totals->owed, $totals->currency)
                                .' owed / '.Money::format((int) $totals->paid, $totals->currency).' paid')
                            ->implode('; ');
                    }),
            ]);
    }
}
