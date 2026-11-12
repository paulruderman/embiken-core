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

test('module selection identifies prerequisites and enforces direct and transitive dependencies', function () {
    $registry = app(ModuleRegistry::class);
    $selectedIds = $registry->normalizeModuleIds(['embiken/bike-rental-self-booking']);
    $prerequisites = $registry->modulePrerequisites();

    expect($selectedIds)->toBe([
        'embiken/bike-fleet',
        'embiken/bike-rentals',
        'embiken/bike-rental-self-booking',
    ])
        ->and($registry->hasSelectedDependents('embiken/bike-fleet', $selectedIds))->toBeTrue()
        ->and($registry->hasSelectedDependents('embiken/bike-rentals', $selectedIds))->toBeTrue()
        ->and($prerequisites['embiken/bike-rentals'])->toBe(['Fleet'])
        ->and($prerequisites['embiken/bike-rental-self-booking'])->toBe(['Rentals', 'Fleet']);
});

test('installed module packages include maintenance', function () {
    $ids = collect(app(ModuleRegistry::class)->installedModules())->map->id()->all();

    expect($ids)->toContain('embiken/bike-fleet', 'embiken/bike-rentals', 'embiken/maintenance', 'embiken/weather');
});
