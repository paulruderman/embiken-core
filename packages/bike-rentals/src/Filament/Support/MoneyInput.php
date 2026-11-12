<?php

namespace Embiken\BikeRentals\Filament\Support;

use App\Support\Money;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\ClosureValidationRule;
use InvalidArgumentException;

class MoneyInput
{
    /**
     * @param  Closure(): string  $currency
     */
    public static function make(string $name, Closure $currency, int $minimumMinorUnits = 0): TextInput
    {
        return TextInput::make($name)
            ->type('text')
            ->inputMode('decimal')
            ->prefix(fn (): string => self::currencyLabel($currency()))
            ->helperText(fn (): string => self::hasValidCurrency($currency)
                ? 'Enter the amount using your language’s number format.'
                : 'The configured currency is invalid. Correct the shop or package currency before entering an amount.')
            ->formatStateUsing(function (mixed $state) use ($currency): string {
                if ($state === null || $state === '') {
                    return '';
                }

                try {
                    return Money::formatInput((int) $state, $currency());
                } catch (InvalidArgumentException) {
                    return '';
                }
            })
            ->rules([
                new ClosureValidationRule(function (string $attribute, mixed $value, Closure $fail) use ($currency, $minimumMinorUnits): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    try {
                        $minorUnits = Money::toMinorUnits((string) $value, $currency());

                        if ($minorUnits < $minimumMinorUnits) {
                            $fail('The amount must be at least '.Money::format($minimumMinorUnits, $currency()).'.');
                        }
                    } catch (InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                }),
            ])
            ->dehydrateStateUsing(function (mixed $state) use ($currency): ?int {
                if ($state === null || $state === '') {
                    return null;
                }

                try {
                    return Money::toMinorUnits((string) $state, $currency());
                } catch (InvalidArgumentException) {
                    return null;
                }
            });
    }

    /**
     * @param  Closure(): string  $currency
     */
    private static function hasValidCurrency(Closure $currency): bool
    {
        try {
            Money::normalizeCurrency($currency());

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private static function currencyLabel(string $currency): string
    {
        try {
            return Money::currencyLabel($currency);
        } catch (InvalidArgumentException) {
            return 'Invalid currency';
        }
    }
}
