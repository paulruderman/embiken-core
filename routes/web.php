<?php

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

Route::inertia('/prototype/terminal', 'Terminal/prototype/Index')->name('prototype.terminal');
