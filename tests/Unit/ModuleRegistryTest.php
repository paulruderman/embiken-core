<?php

use App\Support\ModuleRegistry;

test('normalize module ids adds rental fleet dependency', function () {
    $normalized = app(ModuleRegistry::class)->normalizeModuleIds(['embiken/bike-rentals']);

    expect($normalized)->toContain('embiken/bike-fleet', 'embiken/bike-rentals');
});

test('installed modules include fleet and rentals', function () {
    $ids = collect(app(ModuleRegistry::class)->installedModules())->map->id()->all();

    expect($ids)->toContain('embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/weather');
});
