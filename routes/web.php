<?php

use App\Actions\Platform\ImpersonatePlatformManagerAction;
use App\Actions\Platform\PublicSignupAction;
use App\Http\Controllers\MarketingPageController;
use Illuminate\Support\Facades\Route;

$centralDomains = config('tenancy.central_domains');

Route::domain($centralDomains[0] ?? 'localhost')->group(function (): void {
    Route::get('/', [MarketingPageController::class, 'home'])->name('marketing.home');
    Route::get('/pricing', [MarketingPageController::class, 'pricing'])->name('marketing.pricing');
    Route::get('/features', [MarketingPageController::class, 'show'])->defaults('slug', 'features')->name('marketing.features');
    Route::get('/faq', [MarketingPageController::class, 'show'])->defaults('slug', 'faq')->name('marketing.faq');
    Route::get('/contact', [MarketingPageController::class, 'show'])->defaults('slug', 'contact')->name('marketing.contact');
    Route::get('/terms', [MarketingPageController::class, 'show'])->defaults('slug', 'terms')->name('marketing.terms');
    Route::get('/privacy', [MarketingPageController::class, 'show'])->defaults('slug', 'privacy')->name('marketing.privacy');
    Route::get('/blog', [MarketingPageController::class, 'blogIndex'])->name('marketing.blog');
    Route::get('/blog/{slug}', [MarketingPageController::class, 'blogArticle'])->name('marketing.blog.show');
    Route::get('/sitemap.xml', [MarketingPageController::class, 'sitemap'])->name('marketing.sitemap');
    Route::get('/{slug}', [MarketingPageController::class, 'show'])->where('slug', '^(?!platform$|signup$|checkout$|blog$)[^/]+$')->name('marketing.page');
    Route::get('/signup/{billingPlan}', [MarketingPageController::class, 'signup'])->name('marketing.signup');
    Route::post('/signup', PublicSignupAction::class)->middleware('throttle:5,1')->name('marketing.signup.store');
    Route::get('/checkout/placeholder', [MarketingPageController::class, 'checkoutPlaceholder'])->name('marketing.checkout.placeholder');
});

foreach (array_slice($centralDomains, 1) as $domain) {
    Route::domain($domain)->group(function () use ($domain): void {
        Route::get('/', [MarketingPageController::class, 'home'])->name('marketing.home.'.str_replace('.', '_', $domain));
        Route::get('/pricing', [MarketingPageController::class, 'pricing'])->name('marketing.pricing.'.str_replace('.', '_', $domain));
        Route::get('/features', [MarketingPageController::class, 'show'])->defaults('slug', 'features')->name('marketing.features.'.str_replace('.', '_', $domain));
        Route::get('/faq', [MarketingPageController::class, 'show'])->defaults('slug', 'faq')->name('marketing.faq.'.str_replace('.', '_', $domain));
        Route::get('/contact', [MarketingPageController::class, 'show'])->defaults('slug', 'contact')->name('marketing.contact.'.str_replace('.', '_', $domain));
        Route::get('/terms', [MarketingPageController::class, 'show'])->defaults('slug', 'terms')->name('marketing.terms.'.str_replace('.', '_', $domain));
        Route::get('/privacy', [MarketingPageController::class, 'show'])->defaults('slug', 'privacy')->name('marketing.privacy.'.str_replace('.', '_', $domain));
        Route::get('/blog', [MarketingPageController::class, 'blogIndex'])->name('marketing.blog.'.str_replace('.', '_', $domain));
        Route::get('/blog/{slug}', [MarketingPageController::class, 'blogArticle'])->name('marketing.blog.show.'.str_replace('.', '_', $domain));
        Route::get('/sitemap.xml', [MarketingPageController::class, 'sitemap'])->name('marketing.sitemap.'.str_replace('.', '_', $domain));
        Route::get('/{slug}', [MarketingPageController::class, 'show'])->where('slug', '^(?!platform$|signup$|checkout$|blog$)[^/]+$')->name('marketing.page.'.str_replace('.', '_', $domain));
        Route::get('/signup/{billingPlan}', [MarketingPageController::class, 'signup'])->name('marketing.signup.'.str_replace('.', '_', $domain));
        Route::post('/signup', PublicSignupAction::class)->middleware('throttle:5,1')->name('marketing.signup.store.'.str_replace('.', '_', $domain));
        Route::get('/checkout/placeholder', [MarketingPageController::class, 'checkoutPlaceholder'])->name('marketing.checkout.placeholder.'.str_replace('.', '_', $domain));
    });
}

foreach ($centralDomains as $domain) {
    Route::domain($domain)
        ->middleware('auth:web')
        ->get('/platform/tenants/{tenant}/impersonate', ImpersonatePlatformManagerAction::class)
        ->name('platform.tenants.impersonate.'.str_replace('.', '_', $domain));
}
