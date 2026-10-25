<?php

declare(strict_types=1);

use App\Actions\Platform\ConsumeImpersonationAction;
use App\Actions\Shop\SaveShopLandingPageAction;
use App\Actions\Shop\UpdateShopBrandingAction;
use App\Actions\Staff\SignInAction;
use App\Http\Controllers\MyRentalController;
use App\Http\Controllers\ShopLandingPageController;
use App\Http\Middleware\EnsureModuleEnabled;
use Embiken\BikeRentals\Actions\RequestMyRentalLinkAction;
use Embiken\Maintenance\Actions\CompleteMaintenanceScheduleAction;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [ShopLandingPageController::class, 'home'])->name('home');
    Route::get('/book', [ShopLandingPageController::class, 'booking'])->name('book');

    Route::middleware(EnsureModuleEnabled::class.':embiken/bike-rentals')->group(function (): void {
        Route::get('/myrental', [MyRentalController::class, 'requestLink'])->name('myrental.request');
        Route::get('/myrental/{reservation}/{token}', [MyRentalController::class, 'show'])
            ->middleware('signed')
            ->name('myrental.show');
        RequestMyRentalLinkAction::routes(app('router'));
    });

    Route::get('/impersonate/{staff}', ConsumeImpersonationAction::class)
        ->middleware('signed')
        ->name('staff.impersonate');

    Route::get('/terminal/login', SignInAction::class)->name('staff.login');
    Route::post('/terminal/login', SignInAction::class)->name('staff.login.store');

    Route::post('/shop/branding', UpdateShopBrandingAction::class)
        ->middleware('auth:staff')
        ->name('shop.branding.update');
    Route::post('/shop/landing-page', SaveShopLandingPageAction::class)
        ->middleware('auth:staff')
        ->name('shop.landing-page.save');

    Route::middleware([
        'auth:staff',
        EnsureModuleEnabled::class.':embiken/bike-rentals',
    ])->group(function (): void {
        foreach (glob(base_path('packages/bike-rentals/src/Actions/*.php')) ?: [] as $file) {
            $class = 'Embiken\\BikeRentals\\Actions\\'.basename($file, '.php');

            if ($class !== RequestMyRentalLinkAction::class && is_callable([$class, 'routes'])) {
                $class::routes(app('router'));
            }
        }
    });

    Route::middleware([
        'auth:staff',
        EnsureModuleEnabled::class.':embiken/bike-fleet',
    ])->group(function (): void {
        foreach (glob(base_path('packages/bike-fleet/src/Actions/*.php')) ?: [] as $file) {
            $class = 'Embiken\\BikeFleet\\Actions\\'.basename($file, '.php');

            if (is_callable([$class, 'routes'])) {
                $class::routes(app('router'));
            }
        }
    });

    Route::middleware([
        'auth:staff',
        EnsureModuleEnabled::class.':embiken/maintenance',
    ])->group(function (): void {
        CompleteMaintenanceScheduleAction::routes(app('router'));
    });

    Route::middleware([
        'auth:staff',
        EnsureModuleEnabled::class.':embiken/weather',
    ])->group(function (): void {
        foreach (glob(base_path('packages/weather/src/Actions/*.php')) ?: [] as $file) {
            $class = 'Embiken\\Weather\\Actions\\'.basename($file, '.php');

            if (is_callable([$class, 'routes'])) {
                $class::routes(app('router'));
            }
        }
    });
});
