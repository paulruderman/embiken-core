<?php

use Illuminate\Foundation\Vite;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Vite as TenancyVite;

test('tenant hosts do not prefix the asset helper with the tenancy asset route', function () {
    expect(config('tenancy.filesystem.asset_helper_tenancy'))->toBeFalse();
});

test('vite uses global asset urls when tenancy is booted', function () {
    app(Tenancy::class);

    expect(app(Vite::class))->toBeInstanceOf(TenancyVite::class);
});
