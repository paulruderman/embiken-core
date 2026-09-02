<?php

use App\Actions\Platform\DeleteTenantAction;
use App\Actions\Platform\DisableUserAction;
use App\Actions\Platform\InviteUserAction;
use App\Actions\Platform\StartExpressAccountLinkAction;
use App\Actions\Platform\SuspendTenantAction;
use App\Actions\Platform\UnsuspendTenantAction;
use App\Filament\Platform\Resources\Users\Pages\ListUsers;
use App\Filament\Platform\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('platform');
});

test('a platform user can open the user list', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ListUsers::class)
        ->assertOk();
});

test('a disabled user cannot access the platform panel', function () {
    $user = User::factory()->disabled()->create();

    expect($user->canAccessPanel(Filament::getPanel('platform')))->toBeFalse();
});

test('platform users cannot be deleted', function () {
    $user = User::factory()->create();

    expect(UserResource::canDelete($user))->toBeFalse();
});

test('inviting a user notifies them to set a password', function () {
    Notification::fake();

    $user = app(InviteUserAction::class)('Ada', 'ada@example.com');

    expect($user->email)->toBe('ada@example.com');

    Notification::assertSentTo($user, SetPasswordNotification::class);
});

test('disabling a user sets disabled_at without deleting the row', function () {
    $user = User::factory()->create();

    app(DisableUserAction::class)($user);

    expect($user->fresh()->disabled_at)->not->toBeNull()
        ->and(User::query()->find($user->id))->not->toBeNull();
});

test('suspend and unsuspend toggle the tenant padlock', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::factory()->create());

    app(SuspendTenantAction::class)($tenant);

    expect($tenant->fresh()->isSuspended())->toBeTrue();

    app(UnsuspendTenantAction::class)($tenant);

    expect($tenant->fresh()->isSuspended())->toBeFalse();
});

test('tenant delete is refused unless suspended and does not use a drop-database job', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::factory()->create());

    expect(fn () => app(DeleteTenantAction::class)($tenant))->toThrow(RuntimeException::class);

    app(SuspendTenantAction::class)($tenant);
    app(DeleteTenantAction::class)($tenant->fresh());

    expect(Tenant::query()->find($tenant->id))->toBeNull();
});

test('express account link stores a pending connected account id', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::factory()->create());

    $tenant = app(StartExpressAccountLinkAction::class)($tenant);

    expect($tenant->stripe_connect_account_id)->toStartWith('acct_pending_')
        ->and($tenant->charges_enabled)->toBeFalse();
});
