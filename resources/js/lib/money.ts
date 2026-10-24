const zeroDecimalCurrencies = new Set([
    'bif',
    'clp',
    'djf',
    'gnf',
    'isk',
    'jpy',
    'kmf',
    'krw',
    'mga',
    'pyg',
    'rwf',
    'ugx',
    'vnd',
    'vuv',
    'xaf',
    'xof',
    'xpf',
]);

export function currencyExponent(currency: string): number {
    return zeroDecimalCurrencies.has(currency.toLowerCase()) ? 0 : 2;
}

export function formatMoney(
    minorUnits: number,
    currency: string,
    locale?: string,
): string {
    const exponent = currencyExponent(currency);

    const formatted = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency.toUpperCase(),
        minimumFractionDigits: exponent,
        maximumFractionDigits: exponent,
    }).format(minorUnits / 10 ** exponent);

    return `${currency.toUpperCase()}\u00a0${formatted}`;
}

export function currencyLabel(currency: string, locale?: string): string {
    const code = currency.toUpperCase();
    const symbol = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: code,
        currencyDisplay: 'symbol',
    })
        .formatToParts(0)
        .find((part) => part.type === 'currency')?.value;

    return symbol ? `${code} (${symbol})` : code;
}

export function parseMoneyInput(
    value: string,
    currency: string,
    locale?: string,
): number {
    const exponent = currencyExponent(currency);
    const parts = new Intl.NumberFormat(locale, {
        useGrouping: true,
        maximumFractionDigits: Math.max(exponent, 1),
    }).formatToParts(12345.6);
    const decimalSeparator =
        parts.find((part) => part.type === 'decimal')?.value ?? '.';
    const groupSeparator =
        parts.find((part) => part.type === 'group')?.value ?? ',';
    const digitFormatter = new Intl.NumberFormat(locale, {
        useGrouping: false,
    });
    let normalized = value.trim();

    for (let digit = 0; digit <= 9; digit += 1) {
        const localizedDigit = digitFormatter.format(digit);
        if (localizedDigit !== String(digit)) {
            normalized = normalized.replaceAll(localizedDigit, String(digit));
        }
    }

    if (groupSeparator.trim() === '') {
        normalized = normalized.replace(/[\s\u00a0\u202f\u2009]/g, '');
    } else {
        normalized = normalized.replaceAll(groupSeparator, '');
    }

    if (decimalSeparator !== '.') {
        normalized = normalized.replace(decimalSeparator, '.');
    }

    const match = /^(0|[1-9]\d*)(?:\.(\d+))?$/.exec(normalized);
    if (!match) {
        throw new Error(
            'Enter a non-negative amount using your language’s number format.',
        );
    }

    const fraction = match[2] ?? '';
    if (fraction.length > exponent) {
        throw new Error(
            `This currency allows up to ${exponent} decimal places.`,
        );
    }

    const minorUnits =
        Number(match[1]) * 10 ** exponent +
        Number(fraction.padEnd(exponent, '0'));
    if (!Number.isSafeInteger(minorUnits)) {
        throw new Error('Enter a smaller amount.');
    }

    return minorUnits;
}
