<?php

namespace App\Filament\Tables\Columns;

use App\Models\Location;
use App\Support\Locales;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Tables\Columns\TextColumn;

class LocalizedDateTimeColumn extends TextColumn
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->formatStateUsing(fn (DateTimeInterface|string|null $state): ?string => $this->formatCompactDateTime($state))
            ->description(fn (DateTimeInterface|string|null $state): ?string => $this->formatRelativeDateTime($state))
            ->tooltip(fn (DateTimeInterface|string|null $state): ?string => $this->formatIsoDateTime($state))
            ->timezone(fn (): string => self::shopTimezone())
            ->sortable();
    }

    private function formatCompactDateTime(DateTimeInterface|string|null $state): ?string
    {
        if ($state === null) {
            return null;
        }

        $dateTime = $this->localizedDateTime($state);
        $formatter = new \IntlDateFormatter(
            Locales::currentIntlLocale(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $dateTime->getTimezone()->getName(),
            \IntlDateFormatter::GREGORIAN,
            __('app.datetime_ui.compact_format'),
        );

        $formatted = $formatter->format($dateTime);

        return is_string($formatted) ? $formatted : null;
    }

    private function formatRelativeDateTime(DateTimeInterface|string|null $state): ?string
    {
        if ($state === null) {
            return null;
        }

        $dateTime = $this->localizedDateTime($state)->locale(
            Locales::translationLocale(Locales::currentIntlLocale()),
        );

        return $dateTime instanceof CarbonImmutable ? $dateTime->diffForHumans() : null;
    }

    private function formatIsoDateTime(DateTimeInterface|string|null $state): ?string
    {
        if ($state === null) {
            return null;
        }

        return $this->localizedDateTime($state)->format('Y-m-d\TH:i:sP [e]');
    }

    private function localizedDateTime(DateTimeInterface|string $state): CarbonImmutable
    {
        $dateTime = $state instanceof DateTimeInterface
            ? CarbonImmutable::instance($state)
            : CarbonImmutable::parse($state);

        return $dateTime->setTimezone(self::shopTimezone());
    }

    public static function shopTimezone(): string
    {
        return once(fn (): string => tenancy()->initialized
            ? (Location::query()->value('timezone') ?? config('app.timezone'))
            : config('app.timezone'));
    }
}
