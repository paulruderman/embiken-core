<?php

namespace App\Support;

use InvalidArgumentException;
use NumberFormatter;

/**
 * Currency-aware helpers for integer monetary amounts.
 * Amounts use Stripe's currency unit, not an assumed cent.
 */
final class Money
{
    /** @var list<string> */
    private const ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'isk', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf',
        'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    public static function exponent(string $currency): int
    {
        $currency = self::normalizeCurrency($currency);

        return match (true) {
            in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) => 0,
            default => 2,
        };
    }

    public static function normalizeCurrency(string $currency): string
    {
        $currency = strtolower(trim($currency));

        if (! preg_match('/^[a-z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO code.');
        }

        return $currency;
    }

    /** Parse a decimal major-unit input without floating-point arithmetic. */
    public static function toMinorUnits(string|int $amount, string $currency, ?string $locale = null): int
    {
        $currency = self::normalizeCurrency($currency);
        $exponent = self::exponent($currency);
        $amount = trim((string) $amount);

        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::DECIMAL);
        $decimalSeparator = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $groupingSeparator = $formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        for ($digit = 0; $digit <= 9; $digit++) {
            $localizedDigit = $formatter->format($digit);

            if ($localizedDigit !== false && $localizedDigit !== (string) $digit) {
                $amount = str_replace($localizedDigit, (string) $digit, $amount);
            }
        }

        if ($groupingSeparator !== '') {
            $amount = str_replace($groupingSeparator, '', $amount);
        }

        if ($decimalSeparator !== '.' && $decimalSeparator !== '') {
            $amount = str_replace($decimalSeparator, '.', $amount);
        }

        if (! preg_match('/^(0|[1-9]\d*)(?:\.(\d+))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('Amount must be a non-negative decimal number.');
        }

        $fraction = $matches[2] ?? '';

        if (strlen($fraction) > $exponent) {
            throw new InvalidArgumentException("{$currency} supports at most {$exponent} decimal places.");
        }

        $minorDigits = ltrim($matches[1].str_pad($fraction, $exponent, '0'), '0') ?: '0';
        $minorUnits = filter_var($minorDigits, FILTER_VALIDATE_INT);

        if ($minorUnits === false) {
            throw new InvalidArgumentException('Amount exceeds the supported monetary range.');
        }

        return $minorUnits;
    }

    /** Convert stored minor units to the amount representation Stripe expects. */
    public static function toStripeAmount(int $minorUnits, string $currency): int
    {
        $currency = self::normalizeCurrency($currency);

        if ($minorUnits < 0) {
            throw new InvalidArgumentException('Stripe charge amounts cannot be negative.');
        }

        // Stripe retains two-decimal API compatibility for these zero-decimal currencies.
        if (in_array($currency, ['isk', 'ugx'], true)) {
            if ($minorUnits > intdiv(PHP_INT_MAX, 100)) {
                throw new InvalidArgumentException('Amount exceeds the Stripe API integer range.');
            }

            return $minorUnits * 100;
        }

        return $minorUnits;
    }

    public static function format(int $minorUnits, string $currency, ?string $locale = null): string
    {
        $currency = self::normalizeCurrency($currency);
        $exponent = self::exponent($currency);
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $exponent);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $exponent);

        $amount = $minorUnits / (10 ** $exponent);
        $formatted = $formatter->formatCurrency($amount, strtoupper($currency));

        if ($formatted === false) {
            throw new InvalidArgumentException("Unable to format currency {$currency}.");
        }

        return strtoupper($currency)."\u{00A0}".$formatted;
    }

    public static function formatInput(int $minorUnits, string $currency, ?string $locale = null): string
    {
        $exponent = self::exponent($currency);
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $exponent);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $exponent);
        $amount = $minorUnits / (10 ** $exponent);
        $formatted = $formatter->format($amount);

        if ($formatted === false) {
            throw new InvalidArgumentException('Unable to format the monetary amount.');
        }

        return $formatted;
    }

    public static function currencyLabel(string $currency, ?string $locale = null): string
    {
        $currency = self::normalizeCurrency($currency);
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, strtoupper($currency));
        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        if ($symbol === '') {
            return strtoupper($currency);
        }

        return strtoupper($currency).' ('.$symbol.')';
    }
}
