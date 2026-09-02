<?php

use App\Models\Tenant;
use App\Models\User;

test('a platform user can be disabled without being deleted', function () {
    $user = User::factory()->disabled()->create();

    expect($user->disabled_at)->not->toBeNull()
        ->and(User::query()->find($user->id))->not->toBeNull();
});

test('a tenant stores suspend and express charge flags on the central connection', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::factory()->suspended()->create([
        'stripe_connect_account_id' => 'acct_test',
        'charges_enabled' => true,
        'transfers_active' => true,
    ]));

    expect($tenant->isSuspended())->toBeTrue()
        ->and($tenant->charges_enabled)->toBeTrue()
        ->and($tenant->transfers_active)->toBeTrue();
});
