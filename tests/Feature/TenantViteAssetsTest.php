<?php

use Illuminate\Foundation\Vite;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Vite as TenancyVite;

test('tenant hosts do not prefix the asset helper with the tenancy asset route', function () {
    expect(config('tenancy.filesystem.asset_helper_tenancy'))->toBeFalse();
});

test('the application URL host is recognized as a central domain', function () {
    expect(config('tenancy.central_domains'))
        ->toContain(parse_url((string) config('app.url'), PHP_URL_HOST));
});

test('vite uses global asset urls when tenancy is booted', function () {
    app(Tenancy::class);

    expect(app(Vite::class))->toBeInstanceOf(TenancyVite::class);
});
