<?php

declare(strict_types=1);

use App\Actions\Platform\ConsumeImpersonationAction;
use App\Actions\Staff\SignInAction;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Facades\Actions;
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

    Route::middleware('auth:staff')->group(function () {
        Actions::registerRoutes([
            app_path('Actions/Reservations'),
            app_path('Actions/Terminal'),
        ]);
    });
});
