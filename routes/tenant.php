<?php

declare(strict_types=1);

use App\Actions\Devices\SetCustomerDisplayFocusAction;
use App\Actions\Platform\ConsumeImpersonationAction;
use App\Actions\Shop\SaveShopLandingPageAction;
use App\Actions\Shop\UpdateShopBrandingAction;
use App\Actions\Staff\SignInAction;
use App\Http\Controllers\MyRentalController;
use App\Http\Controllers\ShopLandingPageController;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Models\Device;
use App\Support\CustomerDisplayTicketData;
use App\Support\WallDisplayPageData;
use Embiken\BikeRentals\Actions\RequestMyRentalLinkAction;
use Embiken\BikeRentals\Actions\UpdateCustomerDisplayTicketAction;
use Embiken\Maintenance\Actions\CompleteMaintenanceScheduleAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [ShopLandingPageController::class, 'home'])->name('home');
    Route::get('/book', [ShopLandingPageController::class, 'booking'])->name('book');

    Route::get('/device/pair', function (Request $request) {
        $surface = $request->query('surface');
        abort_unless(in_array($surface, ['terminal', 'customer_display', 'wall_display'], true), 404);

        return Inertia::render('Device/Pair', [
            'surface' => $surface,
            'copy' => __('app.device_pairing_page'),
        ]);
    })->name('devices.pairing.page');

    Route::middleware('device:customer_display')->group(function (): void {
        Route::get('/cfd', function (Request $request, CustomerDisplayTicketData $ticketData) {
            $device = $request->attributes->get('device');
            abort_unless($device instanceof Device, 401);

            return Inertia::render('Cfd/Index', [
                'tenant_id' => (string) tenant('id'),
                'device_id' => $device->id,
                'ticket' => $ticketData->forCustomerDisplay($device),
                'copy' => __('app.customer_display_ui'),
            ]);
        })->name('cfd');

        Route::post('/cfd/ticket', UpdateCustomerDisplayTicketAction::class)
            ->name('cfd.ticket.update');
    });
    Route::middleware('device:wall_display')->get('/wall-display', function (Request $request, WallDisplayPageData $pageData) {
        $device = $request->attributes->get('device');
        abort_unless($device instanceof Device, 401);

        return Inertia::render('WallDisplay/Index', $pageData->forDevice($device));
    })
        ->name('wall-display');

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

    Route::match(['get', 'post'], '/terminal/login', SignInAction::class)->name('staff.login');

    Route::post('/shop/branding', UpdateShopBrandingAction::class)
        ->middleware('auth:staff')
        ->name('shop.branding.update');
    Route::post('/shop/landing-page', SaveShopLandingPageAction::class)
        ->middleware('auth:staff')
        ->name('shop.landing-page.save');

    Route::middleware([
        'auth:staff',
        EnsureModuleEnabled::class.':embiken/bike-rentals',
        'device:terminal',
    ])->group(function (): void {
        Route::post('/terminal/customer-display/focus', SetCustomerDisplayFocusAction::class)
            ->name('terminal.customer-display.focus');
        Route::post('/terminal/customer-display/ticket', UpdateCustomerDisplayTicketAction::class)
            ->name('terminal.customer-display.ticket.update');

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
