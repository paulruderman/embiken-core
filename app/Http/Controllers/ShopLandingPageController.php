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
        $layoutData = $page->layout_data ?? [
            'headline' => $shopName,
            'description' => '',
            'button_label' => 'Book a bike',
        ];
        $imagePath = $layoutData['image_path'] ?? null;
        $contentHtml = $page?->custom_content
            ? RichContentRenderer::make($page->custom_content)
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
            'layout' => $page->layout ?? 'hero',
            'layoutData' => [
                ...$layoutData,
                'image_url' => is_string($imagePath) ? tenant_asset($imagePath) : null,
            ],
            'theme' => $page->theme ?? 'light',
            'contentHtml' => $sanitizer->sanitize($contentHtml),
            'isConfigured' => $page !== null,
        ]);
    }

    public function booking(): InertiaResponse
    {
        return inertia('Book/Index', [
            'bookingNotices' => ShopLandingPage::current()->booking_notices ?? [],
        ]);
    }
}
