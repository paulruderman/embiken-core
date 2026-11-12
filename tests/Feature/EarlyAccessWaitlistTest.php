<?php

use App\Actions\Platform\CreateTenantAction;
use App\Actions\Platform\SendEarlyAccessInvitationAction;
use App\Filament\Platform\Resources\EarlyAccessSignups\Pages\ListEarlyAccessSignups;
use App\Models\EarlyAccessInvitation;
use App\Models\EarlyAccessSignup;
use App\Models\MarketingPage;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EarlyAccessInvitationNotification;
use App\Notifications\EarlyAccessReceiptNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\mock;

beforeEach(function () {
    Filament::setCurrentPanel('platform');
});

it('renders the public early-access interest form', function () {
    $this->get('http://localhost/waitlist')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Marketing/Waitlist')
            ->where('ui.title', __('app.early_access_waitlist.title')));
});

it('stores an email-only interest signup and sends a receipt without creating an account or tenant', function () {
    Notification::fake();

    $this->postJson('http://localhost/waitlist', ['email' => 'Rider@Example.com'])
        ->assertCreated()
        ->assertJsonPath('data.received', true);

    $signup = EarlyAccessSignup::query()->sole();

    expect($signup->email)->toBe('rider@example.com')
        ->and($signup->name)->toBeNull()
        ->and($signup->shop_name)->toBeNull()
        ->and($signup->message)->toBeNull()
        ->and(User::query()->count())->toBe(0)
        ->and(Tenant::query()->count())->toBe(0);

    Notification::assertSentOnDemand(EarlyAccessReceiptNotification::class);
});

it('updates an existing interest signup and sends a receipt for the update', function () {
    Notification::fake();
    $signup = EarlyAccessSignup::factory()->create(['email' => 'rider@example.com']);

    $this->postJson('http://localhost/waitlist', [
        'email' => 'RIDER@example.com',
        'name' => 'Riley',
        'shop_name' => 'Riley Bikes',
        'message' => 'Interested in trying the counter flow.',
    ])->assertCreated()
        ->assertJsonPath('data.received', true);

    expect(EarlyAccessSignup::query()->count())->toBe(1)
        ->and($signup->fresh()->name)->toBe('Riley')
        ->and($signup->fresh()->shop_name)->toBe('Riley Bikes')
        ->and($signup->fresh()->message)->toBe('Interested in trying the counter flow.');

    Notification::assertSentOnDemand(EarlyAccessReceiptNotification::class);
});

it('rejects public shop signup without a valid invitation', function () {
    Notification::fake();

    $this->get('http://localhost/signup/starter')->assertNotFound();
    $this->post('http://localhost/signup', [
        'plan' => 'starter',
        'name' => 'Shop',
        'shop_slug' => 'waitlist-blocked-shop',
        'timezone' => 'America/New_York',
        'currency' => 'usd',
        'manager_name' => 'Riley',
        'manager_email' => 'manager@example.com',
    ])->assertSessionHasErrors(['invite']);

    expect(Tenant::query()->count())->toBe(0);
});

it('sends a hashed one-use invitation from the platform list', function () {
    Notification::fake();
    $user = User::factory()->create();
    $signup = EarlyAccessSignup::factory()->create();

    $this->actingAs($user);
    Livewire::test(ListEarlyAccessSignups::class)
        ->callTableAction('invite', $signup)
        ->assertHasNoTableActionErrors();

    $invitation = $signup->invitations()->sole();

    expect(strlen($invitation->token_hash))->toBe(64)
        ->and($invitation->isUsable())->toBeTrue()
        ->and($signup->fresh()->status)->toBe('invited');

    Notification::assertSentOnDemand(EarlyAccessInvitationNotification::class);
});

it('keeps earlier invitations valid when another invitation is sent', function () {
    Notification::fake();
    $signup = EarlyAccessSignup::factory()->create();
    $user = User::factory()->create();
    $action = app(SendEarlyAccessInvitationAction::class);

    $first = $action($signup, $user);
    $second = $action($signup->fresh(), $user);

    expect($first->fresh()->isUsable())->toBeTrue()
        ->and($second->fresh()->isUsable())->toBeTrue()
        ->and($signup->invitations()->count())->toBe(2);
});

it('lets an authenticated platform user send an invitation through the JSON action route', function () {
    Notification::fake();
    $user = User::factory()->create();
    $signup = EarlyAccessSignup::factory()->create();

    $this->actingAs($user)
        ->postJson("http://localhost/platform/early-access/{$signup->id}/invite")
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'expires_at']])
        ->assertJsonMissingPath('data.token_hash');

    expect($signup->invitations()->count())->toBe(1);
});

it('renders plan selection only for a usable invitation', function () {
    MarketingPage::factory()->create(['slug' => 'pricing']);
    $signup = EarlyAccessSignup::factory()->create();
    $rawToken = str_repeat('a', 64);
    $invitation = EarlyAccessInvitation::factory()->create([
        'early_access_signup_id' => $signup->id,
        'token_hash' => hash('sha256', $rawToken),
    ]);

    $this->get('http://localhost/invite/'.$rawToken)
        ->assertRedirect('http://localhost/pricing')
        ->assertSessionHas('early_access_invitation_token', $rawToken);

    $this->get('http://localhost/pricing')
        ->assertInertia(fn ($page) => $page
            ->component('Marketing/Pricing')
            ->where('invitationAvailable', true));

    $invitation->forceFill(['expires_at' => now()->subSecond()])->save();

    $this->get('http://localhost/invite/'.$rawToken)->assertNotFound();
});

it('uses an invitation once to provision a shop even when the Manager email differs', function () {
    Notification::fake();
    $signup = EarlyAccessSignup::factory()->create(['email' => 'interested@example.com']);
    $rawToken = str_repeat('b', 64);
    $invitation = EarlyAccessInvitation::factory()->create([
        'early_access_signup_id' => $signup->id,
        'token_hash' => hash('sha256', $rawToken),
    ]);
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::factory()->create());

    mock(CreateTenantAction::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturnUsing(function () use ($tenant): Tenant {
            $tenant->domains()->create(['domain' => 'riley-bikes.localhost']);

            return $tenant;
        });

    $this->get('http://localhost/invite/'.$rawToken)
        ->assertRedirect('http://localhost/pricing');

    $this->withSession(['early_access_invitation_token' => $rawToken])
        ->post('http://localhost/signup', [
            'plan' => 'starter',
            'name' => 'Riley Bikes',
            'shop_slug' => 'riley-bikes',
            'timezone' => 'America/New_York',
            'currency' => 'usd',
            'manager_name' => 'Riley',
            'manager_email' => 'manager@rileybikes.example',
        ])->assertRedirect('http://localhost/checkout/placeholder');

    expect($invitation->fresh()->used_at)->not->toBeNull();

    $this->get('http://localhost/invite/'.$rawToken)->assertNotFound();
});
