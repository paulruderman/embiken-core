<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the platform users command creates and refreshes the two fixed users', function () {
    User::factory()->create([
        'name' => 'Old Platform Name',
        'email' => 'paul@embiken.com',
        'password' => 'old-password',
        'disabled_at' => now(),
    ]);

    $this->artisan('platform:ensure-users')->assertSuccessful();
    $this->artisan('platform:ensure-users')->assertSuccessful();

    $paul = User::query()->where('email', 'paul@embiken.com')->firstOrFail();
    $platform = User::query()->where('email', 'platform@embiken.com')->firstOrFail();

    expect(User::query()->count())->toBe(2)
        ->and($paul->name)->toBe('Paul')
        ->and($paul->disabled_at)->toBeNull()
        ->and(Hash::check('11111111', $paul->password))->toBeTrue()
        ->and($platform->name)->toBe('Platform')
        ->and(Hash::check('11111111', $platform->password))->toBeTrue();
});
