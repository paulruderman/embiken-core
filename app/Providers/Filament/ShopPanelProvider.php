<?php

namespace App\Providers\Filament;

use App\Models\ShopSetting;
use App\Support\BrandColor;
use App\Support\ModuleRegistry;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class ShopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->id('shop')
            ->path('manage')
            ->brandName(fn (): string => $this->shopSetting()?->brand_name ?: (tenant()->name ?? (string) config('app.name')))
            ->brandLogo(function (): ?string {
                $logoPath = $this->shopSetting()?->logo_path;

                return $logoPath === null ? null : tenant_asset($logoPath);
            })
            ->favicon(function (): string {
                $faviconPath = $this->shopSetting()?->favicon_path;

                return $faviconPath === null ? global_asset('favicon.svg') : tenant_asset($faviconPath);
            })
            ->authGuard('staff')
            ->authPasswordBroker('staff')
            ->login()
            ->passwordReset()
            ->viteTheme('resources/css/filament/shop/theme.css')
            ->colors(fn (): array => $this->panelColors())
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.shop.brand-color-variables', [
                    'palettes' => $this->brandPalettes(),
                ]),
            )
            ->discoverResources(in: app_path('Filament/Shop/Resources'), for: 'App\Filament\Shop\Resources')
            ->discoverPages(in: app_path('Filament/Shop/Pages'), for: 'App\Filament\Shop\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Shop/Widgets'), for: 'App\Filament\Shop\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        return app(ModuleRegistry::class)->applyFilamentDiscoveries($panel);
    }

    private function shopSetting(): ?ShopSetting
    {
        return tenant() === null ? null : ShopSetting::current();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function panelColors(): array
    {
        return $this->brandPalettes();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function brandPalettes(): array
    {
        $settings = $this->shopSetting();

        return [
            'primary' => $this->paletteForColor($settings?->primary_color, ShopSetting::DEFAULT_PRIMARY_COLOR),
            'gray' => $this->paletteForColor($settings?->brand_gray_color, ShopSetting::DEFAULT_GRAY_COLOR),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function paletteForColor(?string $color, string $default): array
    {
        return BrandColor::palette($color, $default);
    }
}
