<?php

declare(strict_types=1);

use App\Actions\Platform\ConsumeImpersonationAction;
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
});
