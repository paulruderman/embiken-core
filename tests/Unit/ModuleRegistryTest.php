<?php

use App\Support\ModuleRegistry;

test('normalize module ids adds rental fleet dependency', function () {
    $normalized = app(ModuleRegistry::class)->normalizeModuleIds(['embiken/bike-rentals']);

    expect($normalized)->toContain('embiken/bike-fleet', 'embiken/bike-rentals');
});

test('maintenance includes its fleet prerequisite and is enabled by default', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->normalizeModuleIds(['embiken/maintenance']))
        ->toBe(['embiken/bike-fleet', 'embiken/maintenance'])
        ->and(ModuleRegistry::defaultTenantModuleIds())
        ->toContain('embiken/maintenance');
});

test('module selection explains and enforces direct and transitive prerequisites', function () {
    $registry = app(ModuleRegistry::class);
    $selectedIds = $registry->normalizeModuleIds(['embiken/bike-rental-self-booking']);
    $descriptions = $registry->moduleOptionDescriptions($selectedIds);

    expect($selectedIds)->toBe([
        'embiken/bike-fleet',
        'embiken/bike-rentals',
        'embiken/bike-rental-self-booking',
    ])
        ->and($registry->hasSelectedDependents('embiken/bike-fleet', $selectedIds))->toBeTrue()
        ->and($registry->hasSelectedDependents('embiken/bike-rentals', $selectedIds))->toBeTrue()
        ->and($descriptions['embiken/bike-rental-self-booking'])->toContain('Requires Rentals, Fleet')
        ->and($descriptions['embiken/bike-fleet'])->toContain('Required by Online Book, Rentals');
});

test('installed module packages include maintenance', function () {
    $ids = collect(app(ModuleRegistry::class)->installedModules())->map->id()->all();

    expect($ids)->toContain('embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/maintenance', 'embiken/weather');
});
