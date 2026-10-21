<?php

use App\Actions\Platform\ImpersonatePlatformManagerAction;
use Illuminate\Support\Facades\Route;

$centralDomains = config('tenancy.central_domains');

Route::domain($centralDomains[0] ?? 'localhost')->group(function (): void {
    Route::redirect('/', '/platform')->name('platform.home');
});

foreach (array_slice($centralDomains, 1) as $domain) {
    Route::domain($domain)->group(function (): void {
        Route::redirect('/', '/platform');
    });
}

foreach ($centralDomains as $domain) {
    Route::domain($domain)
        ->middleware('auth:web')
        ->get('/platform/tenants/{tenant}/impersonate', ImpersonatePlatformManagerAction::class)
        ->name('platform.tenants.impersonate.'.str_replace('.', '_', $domain));
}
