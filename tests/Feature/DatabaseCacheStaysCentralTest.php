<?php

test('the database cache store uses the central connection', function () {
    expect(config('cache.stores.database.connection'))
        ->toBe(config('tenancy.database.central_connection'))
        ->toBe(config('database.default'));
});

test('the database queue uses the central connection', function () {
    expect(config('queue.connections.database.connection'))
        ->toBe(config('tenancy.database.central_connection'));
});
