<?php

declare(strict_types=1);

use App\Actions\Platform\ConsumeImpersonationAction;
use App\Actions\Shop\UpdateShopBrandingAction;
use App\Actions\Staff\SignInAction;
use App\Http\Middleware\EnsureModuleEnabled;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::redirect('/', '/book')->name('home');
    Route::inertia('/book', 'Book/Index')->name('book');

    Route::get('/impersonate/{staff}', ConsumeImpersonationAction::class)
        ->middleware('signed')
        ->name('staff.impersonate');

    Route::get('/terminal/login', SignInAction::class)->name('staff.login');
    Route::post('/terminal/login', SignInAction::class)->name('staff.login.store');

    Route::post('/shop/branding', UpdateShopBrandingAction::class)
        ->middleware('auth:staff')
        ->name('shop.branding.update');

    Route::middleware([
        'auth:staff',
        EnsureModuleEnabled::class.':embiken/bike-rentals',
    ])->group(function (): void {
        foreach (glob(base_path('packages/bike-rentals/src/Actions/*.php')) ?: [] as $file) {
            $class = 'Embiken\\BikeRentals\\Actions\\'.basename($file, '.php');

            if (is_callable([$class, 'routes'])) {
                $class::routes(app('router'));
            }
        }
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
