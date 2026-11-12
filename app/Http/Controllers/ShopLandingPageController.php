<?php

namespace App\Http\Controllers;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\ShopCalloutBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\ShopFeatureBlock;
use App\Models\Location;
use App\Models\ShopLandingPage;
use App\Models\ShopSetting;
use App\Support\BrandColor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ShopLandingPageController extends Controller
{
    public function home(): InertiaResponse
    {
        $page = ShopLandingPage::current();
        $settings = ShopSetting::query()->first();
        $shopName = $settings?->brand_name ?: Location::query()->value('name') ?: (string) tenant()->name;
        $locale = app()->getLocale();
        $storedLayoutData = $page->layout_data ?? [];
        $englishLayoutData = $storedLayoutData['en'] ?? [];
        $localizedLayoutData = $storedLayoutData[$locale] ?? [];
        $localizedLayoutData = array_filter($localizedLayoutData, static fn (mixed $value): bool => $value !== null && $value !== '');
        $layoutData = array_replace($englishLayoutData, $localizedLayoutData) ?: [
            'headline' => $shopName,
            'description' => '',
            'button_label' => 'Book a bike',
        ];
        $sharedLayoutData = $storedLayoutData['shared'] ?? [];
        $imagePath = $sharedLayoutData['image_path'] ?? null;
        $customContent = $page?->custom_content;
        $customContent = is_array($customContent) && array_key_exists('en', $customContent)
            ? ($customContent[$locale] ?? null) ?: $customContent['en']
            : $customContent;
        $contentHtml = $customContent
            ? RichContentRenderer::make($customContent)
                ->customBlocks([ShopCalloutBlock::class, ShopFeatureBlock::class])
                ->toHtml()
            : '';
        $sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig)->allowSafeElements()->allowRelativeLinks()->allowRelativeMedias(),
        );

        return inertia('Shop/Home', [
            'shopName' => $shopName,
            'logoUrl' => $settings?->logo_path === null ? null : tenant_asset($settings->logo_path),
            'primaryColor' => BrandColor::swatch($settings?->primary_color, ShopSetting::DEFAULT_PRIMARY_COLOR),
            'locale' => $locale,
            'layout' => $page->layout ?? 'hero',
            'layoutData' => [
                ...$layoutData,
                'image_position' => $sharedLayoutData['image_position'] ?? 'right',
                'image_url' => is_string($imagePath) ? tenant_asset($imagePath) : null,
            ],
            'theme' => $page->theme ?? 'light',
            'contentHtml' => $sanitizer->sanitize($contentHtml),
            'isConfigured' => $page !== null,
        ]);
    }

    public function booking(): InertiaResponse
    {
        $locale = app()->getLocale();
        $bookingNotices = ShopLandingPage::current()->booking_notices ?? [];
        $bookingNotices = array_map(static function (array $notice) use ($locale): array {
            $message = $notice['message'];

            $localizedMessage = is_array($message) ? ($message[$locale] ?? null) : $message;

            return [
                'message' => $localizedMessage ?: (is_array($message) ? ($message['en'] ?? '') : $message),
                'tone' => $notice['tone'],
            ];
        }, $bookingNotices);

        return inertia('Book/Index', [
            'locale' => $locale,
            'bookingNotices' => $bookingNotices,
        ]);
    }
}
