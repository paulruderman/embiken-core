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
                    ->label(__('app.rentals_ui.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('app.rentals_ui.fields.email'))
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label(__('app.rentals_ui.fields.phone'))
                    ->required(),
                Placeholder::make('owed_paid')
                    ->label(__('app.rentals_ui.fields.owed_paid'))
                    ->content(function (?Customer $record): string {
                        if ($record === null) {
                            return '—';
                        }

                        return $record->reservations()
                            ->selectRaw('currency, SUM(owed) AS owed, SUM(paid) AS paid')
                            ->groupBy('currency')
                            ->get()
                            ->map(fn (object $totals): string => __('app.rentals_ui.money_totals', [
                                'owed' => Money::format((int) $totals->owed, $totals->currency),
                                'paid' => Money::format((int) $totals->paid, $totals->currency),
                            ]))
                            ->implode('; ');
                    }),
            ]);
    }
}
