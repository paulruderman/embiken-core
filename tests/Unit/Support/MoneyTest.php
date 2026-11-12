<?php

use App\Support\Money;
use InvalidArgumentException;

test('currency precision determines the number of minor units', function () {
    expect(Money::toMinorUnits('12.50', 'usd'))->toBe(1250)
        ->and(Money::toMinorUnits('1250', 'jpy'))->toBe(1250)
        ->and(Money::toMinorUnits('12.34', 'kwd'))->toBe(1234);
});

test('localized decimal input is normalized without accepting excess precision', function () {
    expect(Money::toMinorUnits('1.234,56', 'eur', 'de_DE'))->toBe(123456)
        ->and(Money::toMinorUnits('12,50', 'eur', 'de_DE'))->toBe(1250)
        ->and(Money::toMinorUnits('١٢٫٥٠', 'usd', 'ar_EG'))->toBe(1250)
        ->and(fn () => Money::toMinorUnits('1.001', 'kwd'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::toMinorUnits('1.001', 'usd'))->toThrow(InvalidArgumentException::class);
});

test('currency formatting follows the requested locale and minor unit precision', function () {
    expect(Money::format(1250, 'usd', 'en_US'))->toBe("USD\u{00A0}$12.50")
        ->and(Money::format(1250, 'jpy', 'ja_JP'))->toContain('JPY')
        ->and(Money::format(1250, 'jpy', 'ja_JP'))->toContain('1,250');
});

test('currency labels show both the ISO code and the locale symbol', function () {
    expect(Money::currencyLabel('usd', 'en_US'))->toBe('USD ($)')
        ->and(Money::currencyLabel('eur', 'de_DE'))->toBe('EUR (€)');
});

test('currency normalization trims input and uses Stripe lowercase format', function () {
    expect(Money::normalizeCurrency(' USD '))->toBe('usd');
});

test('money inputs can be formatted for currency-aware forms', function () {
    expect(Money::formatInput(1234, 'usd', 'en_US'))->toBe('12.34')
        ->and(Money::formatInput(1234, 'kwd', 'en_US'))->toBe('12.34')
        ->and(Money::formatInput(1250, 'jpy', 'en_US'))->toBe('1,250');
});

test('Stripe amount conversion preserves its special zero decimal compatibility rules', function () {
    expect(Money::toStripeAmount(500, 'jpy'))->toBe(500)
        ->and(Money::toStripeAmount(500, 'isk'))->toBe(50000)
        ->and(Money::toStripeAmount(500, 'ugx'))->toBe(50000);
});
