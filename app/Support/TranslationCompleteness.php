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
        $landingPage = ShopLandingPage::current();

        if ($landingPage !== null) {
            $this->addLandingPageGaps($gaps, $landingPage);
        }

        $modules = app(ModuleRegistry::class);

        if ($modules->isEnabled(BikeFleetModule::ID)) {
            $this->addModelGaps(
                $gaps,
                BikeCategory::query()->get(),
                ['name', 'description'],
                'Bike category',
                static fn (Model $record): string => BikeCategoryResource::getUrl('edit', ['record' => $record]),
            );
            $this->addModelGaps(
                $gaps,
                BikeModel::query()->get(),
                ['name', 'description'],
                'Bike model',
                static fn (Model $record): string => BikeModelResource::getUrl('edit', ['record' => $record]),
            );
            $this->addModelGaps(
                $gaps,
                BikeModelVariant::query()->get(),
                ['color_name'],
                'Bike variant',
                static fn (Model $record): string => BikeModelVariantResource::getUrl('edit', ['record' => $record]),
            );
        }

        if ($modules->isEnabled('embiken/bike-rentals')) {
            $this->addModelGaps(
                $gaps,
                RentalPackage::query()->get(),
                ['name', 'description', 'tagline', 'blurb'],
                'Rental package',
                static fn (Model $record): string => RentalPackageResource::getUrl('edit', ['record' => $record]),
            );
        }

        return $this->gaps = $gaps;
    }

    /** @param list<array{locale: string, type: string, attribute: string, url: string}> $gaps */
    private function addLandingPageGaps(array &$gaps, ShopLandingPage $page): void
    {
        $layoutData = $page->layout_data;
        $english = $layoutData['en'] ?? [];
        $layoutFields = ['eyebrow', 'headline', 'description', 'button_label'];

        if ($page->layout === 'story') {
            $layoutFields = [...$layoutFields, 'story_quote', 'story_byline'];
        }

        foreach ($layoutFields as $field) {
            $this->addArrayTranslationGaps($gaps, $english[$field] ?? null, static fn (string $locale): mixed => $layoutData[$locale][$field] ?? null, 'Shop home page', $field, ShopLandingPageEditor::getUrl());
        }

        $englishContent = $page->custom_content['en'] ?? null;

        if ($this->hasRichContent($englishContent)) {
            foreach (Locales::contentLocales() as $locale) {
                if ($locale === 'en' || ! $this->hasRichContent($page->custom_content[$locale] ?? null)) {
                    if ($locale !== 'en') {
                        $this->addGap($gaps, $locale, 'Shop home page', 'custom content', ShopLandingPageEditor::getUrl());
                    }
                }
            }
        }

        foreach ($page->booking_notices ?? [] as $notice) {
            $message = $notice['message'];
            $englishMessage = is_array($message) ? ($message['en'] ?? null) : $message;

            foreach (Locales::contentLocales() as $locale) {
                if ($locale === 'en') {
                    continue;
                }

                $localizedMessage = is_array($message) ? ($message[$locale] ?? null) : null;

                if (filled($englishMessage) && ! filled($localizedMessage)) {
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
     */
    private function addModelGaps(array &$gaps, iterable $records, array $attributes, string $type, callable $url): void
    {
        foreach ($records as $record) {
            if (! method_exists($record, 'getTranslation')) {
                continue;
            }

            foreach ($attributes as $attribute) {
                $englishValue = $record->getTranslation($attribute, 'en', false);

                if (! filled($englishValue)) {
                    continue;
                }

                foreach (Locales::contentLocales() as $locale) {
                    if ($locale === 'en') {
                        continue;
                    }

                    if (method_exists($record, 'getTranslation') && ! filled($record->getTranslation($attribute, $locale, false))) {
                        $this->addGap($gaps, $locale, $type, $attribute, $url($record));
                    }
                }
            }
        }
    }

    /** @param list<array{locale: string, type: string, attribute: string, url: string}> $gaps */
    private function addArrayTranslationGaps(array &$gaps, mixed $englishValue, callable $localizedValue, string $type, string $attribute, string $url): void
    {
        if (! filled($englishValue)) {
            return;
        }

        foreach (Locales::contentLocales() as $locale) {
            if ($locale !== 'en' && ! filled($localizedValue($locale))) {
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
