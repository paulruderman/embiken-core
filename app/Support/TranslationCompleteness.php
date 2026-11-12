<?php

namespace App\Support;

use App\Filament\Shop\Pages\ShopLandingPageEditor;
use App\Models\ShopLandingPage;
use Embiken\BikeFleet\BikeFleetModule;
use Embiken\BikeFleet\Filament\Resources\BikeCategories\BikeCategoryResource;
use Embiken\BikeFleet\Filament\Resources\BikeModels\BikeModelResource;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeModel;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Embiken\BikeRentals\Models\RentalPackage;
use Illuminate\Database\Eloquent\Model;

class TranslationCompleteness
{
    /** @var list<array{locale: string, type: string, attribute: string, url: string}>|null */
    private ?array $gaps = null;

    public function hasMissingTranslations(): bool
    {
        return $this->gaps() !== [];
    }

    public function missingTranslationCount(): int
    {
        return count($this->gaps());
    }

    public function firstMissingTranslationUrl(): ?string
    {
        return $this->gaps()[0]['url'] ?? null;
    }

    /** @return list<array{locale: string, type: string, attribute: string, url: string}> */
    private function gaps(): array
    {
        if ($this->gaps !== null) {
            return $this->gaps;
        }

        $gaps = [];
        $locales = Locales::tenantContentLocales();
        $defaultLocale = Locales::tenantDefaultContentLocale();
        $landingPage = ShopLandingPage::current();

        if ($landingPage !== null) {
            $this->addLandingPageGaps($gaps, $landingPage, $locales, $defaultLocale);
        }

        $modules = app(ModuleRegistry::class);

        if ($modules->isEnabled(BikeFleetModule::ID)) {
            $this->addModelGaps(
                $gaps,
                BikeCategory::query()->get(),
                ['name', 'description'],
                'Bike category',
                static fn (Model $record): string => BikeCategoryResource::getUrl('edit', ['record' => $record]),
                $locales,
                $defaultLocale,
            );
            $this->addModelGaps(
                $gaps,
                BikeModel::query()->get(),
                ['name', 'description'],
                'Bike model',
                static fn (Model $record): string => BikeModelResource::getUrl('edit', ['record' => $record]),
                $locales,
                $defaultLocale,
            );
            $this->addModelGaps(
                $gaps,
                BikeModelVariant::query()->get(),
                ['color_name'],
                'Bike variant',
                static fn (Model $record): string => BikeModelVariantResource::getUrl('edit', ['record' => $record]),
                $locales,
                $defaultLocale,
            );
        }

        if ($modules->isEnabled('embiken/bike-rentals')) {
            $this->addModelGaps(
                $gaps,
                RentalPackage::query()->get(),
                ['name', 'description', 'tagline', 'blurb'],
                'Rental package',
                static fn (Model $record): string => RentalPackageResource::getUrl('edit', ['record' => $record]),
                $locales,
                $defaultLocale,
            );
        }

        return $this->gaps = $gaps;
    }

    /**
     * @param  list<array{locale: string, type: string, attribute: string, url: string}>  $gaps
     * @param  list<string>  $locales
     */
    private function addLandingPageGaps(array &$gaps, ShopLandingPage $page, array $locales, string $defaultLocale): void
    {
        $layoutData = $page->layout_data;
        $defaultContent = $layoutData[$defaultLocale] ?? [];
        $layoutFields = ['eyebrow', 'headline', 'description', 'button_label'];

        if ($page->layout === 'story') {
            $layoutFields = [...$layoutFields, 'story_quote', 'story_byline'];
        }

        foreach ($layoutFields as $field) {
            $this->addArrayTranslationGaps($gaps, $defaultContent[$field] ?? null, static fn (string $locale): mixed => $layoutData[$locale][$field] ?? null, 'Shop home page', $field, ShopLandingPageEditor::getUrl(), $locales, $defaultLocale);
        }

        $defaultContentValue = $page->custom_content[$defaultLocale] ?? null;

        if ($this->hasRichContent($defaultContentValue)) {
            foreach ($locales as $locale) {
                if ($locale === $defaultLocale || ! $this->hasRichContent($page->custom_content[$locale] ?? null)) {
                    if ($locale !== $defaultLocale) {
                        $this->addGap($gaps, $locale, 'Shop home page', 'custom content', ShopLandingPageEditor::getUrl());
                    }
                }
            }
        }

        foreach ($page->booking_notices ?? [] as $notice) {
            $message = $notice['message'];
            $defaultMessage = is_array($message) ? ($message[$defaultLocale] ?? null) : $message;

            foreach ($locales as $locale) {
                if ($locale === $defaultLocale) {
                    continue;
                }

                $localizedMessage = is_array($message) ? ($message[$locale] ?? null) : null;

                if (filled($defaultMessage) && ! filled($localizedMessage)) {
                    $this->addGap($gaps, $locale, 'Booking message', 'message', ShopLandingPageEditor::getUrl());
                }
            }
        }
    }

    /**
     * @param  list<array{locale: string, type: string, attribute: string, url: string}>  $gaps
     * @param  iterable<Model>  $records
     * @param  list<string>  $attributes
     * @param  class-string<Model>|string  $type
     * @param  callable(Model): string  $url
     * @param  list<string>  $locales
     */
    private function addModelGaps(array &$gaps, iterable $records, array $attributes, string $type, callable $url, array $locales, string $defaultLocale): void
    {
        foreach ($records as $record) {
            if (! method_exists($record, 'getTranslation')) {
                continue;
            }

            foreach ($attributes as $attribute) {
                $defaultValue = $record->getTranslation($attribute, $defaultLocale, false);

                if (! filled($defaultValue)) {
                    continue;
                }

                foreach ($locales as $locale) {
                    if ($locale === $defaultLocale) {
                        continue;
                    }

                    if (method_exists($record, 'getTranslation') && ! filled($record->getTranslation($attribute, $locale, false))) {
                        $this->addGap($gaps, $locale, $type, $attribute, $url($record));
                    }
                }
            }
        }
    }

    /**
     * @param  list<array{locale: string, type: string, attribute: string, url: string}>  $gaps
     * @param  list<string>  $locales
     */
    private function addArrayTranslationGaps(array &$gaps, mixed $defaultValue, callable $localizedValue, string $type, string $attribute, string $url, array $locales, string $defaultLocale): void
    {
        if (! filled($defaultValue)) {
            return;
        }

        foreach ($locales as $locale) {
            if ($locale !== $defaultLocale && ! filled($localizedValue($locale))) {
                $this->addGap($gaps, $locale, $type, $attribute, $url);
            }
        }
    }

    /** @param list<array{locale: string, type: string, attribute: string, url: string}> $gaps */
    private function addGap(array &$gaps, string $locale, string $type, string $attribute, string $url): void
    {
        $gaps[] = compact('locale', 'type', 'attribute', 'url');
    }

    private function hasRichContent(mixed $content): bool
    {
        if (! is_array($content)) {
            return is_string($content) && filled(strip_tags($content));
        }

        foreach ($content as $key => $value) {
            if (in_array($key, ['type', 'id', 'tone'], true)) {
                continue;
            }

            if (is_array($value) ? $this->hasRichContent($value) : (is_string($value) && filled(strip_tags($value)))) {
                return true;
            }
        }

        return false;
    }
}
