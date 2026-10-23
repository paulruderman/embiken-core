<?php

use App\Enums\StaffRole;
use App\Filament\Shop\Pages\ShopLandingPageEditor;
use App\Models\ShopLandingPage;
use App\Models\Staff;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

beforeEach(function () {
    $this->migrateTenantSchema();
    Filament::setCurrentPanel('shop');
});

function deleteShopLandingTenantDatabase(?Tenant $tenant): void
{
    tenancy()->end();

    if ($tenant === null) {
        return;
    }

    $path = database_path($tenant->database()->getName());

    if (is_file($path)) {
        unlink($path);
    }
}

/** @return array{tenant: Tenant, staff: Staff} */
function makeShopLandingTenant(string $domain, StaffRole $role = StaffRole::Manager): array
{
    Notification::fake();

    $tenant = Tenant::create(['name' => 'Harbor Bikes']);
    $tenant->domains()->create(['domain' => $domain]);
    $staff = $tenant->run(fn (): Staff => Staff::factory()->create(['role' => $role]));

    return ['tenant' => $tenant, 'staff' => $staff];
}

afterEach(function () {
    deleteShopLandingTenantDatabase($this->landingTenant ?? null);
});

test('the tenant root renders the configured shop home page', function () {
    $shop = makeShopLandingTenant('landing-home.localhost');
    $this->landingTenant = $shop['tenant'];

    $shop['tenant']->run(function (): void {
        ShopLandingPage::factory()->create([
            'layout' => 'story',
            'layout_data' => [
                'eyebrow' => 'Locally owned',
                'headline' => 'Ride the coast',
                'description' => 'Bikes for a day by the water.',
                'button_label' => 'Choose a bike',
                'story_quote' => 'The best way to see town.',
                'story_byline' => 'A happy rider',
            ],
            'theme' => 'warm',
        ]);
    });

    $this->withoutVite()
        ->get('http://landing-home.localhost')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Home')
            ->where('layout', 'story')
            ->where('theme', 'warm')
            ->where('layoutData.headline', 'Ride the coast')
            ->where('isConfigured', true));
});

test('the tenant home renders rich editor custom blocks as safe html', function () {
    $shop = makeShopLandingTenant('landing-block.localhost');
    $this->landingTenant = $shop['tenant'];

    $shop['tenant']->run(function (): void {
        ShopLandingPage::factory()->create([
            'custom_content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => 'shop_callout',
                        'config' => [
                            'heading' => 'Ride update',
                            'message' => 'Book early. <script>alert("x")</script>',
                            'tone' => 'info',
                        ],
                    ],
                ]],
            ],
        ]);
    });

    $this->withoutVite()
        ->get('http://landing-block.localhost')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Home')
            ->where('contentHtml', fn (mixed $html): bool => is_string($html)
                && str_contains($html, 'Ride update')
                && ! str_contains($html, '<script>')));
});

test('the booking page renders tenant booking notices', function () {
    $shop = makeShopLandingTenant('landing-book.localhost');
    $this->landingTenant = $shop['tenant'];

    $shop['tenant']->run(function (): void {
        ShopLandingPage::factory()->create([
            'booking_notices' => [
                ['message' => 'Please arrive ten minutes early.', 'tone' => 'warning'],
            ],
        ]);
    });

    $this->withoutVite()
        ->get('http://landing-book.localhost/book')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Book/Index')
            ->where('bookingNotices.0.message', 'Please arrive ten minutes early.')
            ->where('bookingNotices.0.tone', 'warning'));
});

test('a manager can save shop page content over the tenant JSON route', function () {
    $shop = makeShopLandingTenant('landing-save.localhost');
    $this->landingTenant = $shop['tenant'];

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://landing-save.localhost/shop/landing-page', [
            'layout' => 'hero',
            'layout_data' => [
                'headline' => 'Rent by the river',
                'description' => 'Bikes for a quiet day out.',
                'button_label' => 'Book now',
            ],
            'theme' => 'light',
            'custom_content' => null,
            'booking_notices' => [],
        ])
        ->assertOk()
        ->assertJsonPath('layout_data.headline', 'Rent by the river');

    $shop['tenant']->run(function (): void {
        expect(ShopLandingPage::current()?->layout_data['headline'])->toBe('Rent by the river');
    });
});

test('a counter receives 403 when saving shop page content', function () {
    $shop = makeShopLandingTenant('landing-counter.localhost', StaffRole::Counter);
    $this->landingTenant = $shop['tenant'];

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://landing-counter.localhost/shop/landing-page', [
            'layout' => 'hero',
            'layout_data' => [
                'headline' => 'Ride the coast',
                'description' => 'Bikes by the water.',
                'button_label' => 'Book now',
            ],
            'theme' => 'light',
        ])
        ->assertForbidden();
});

test('a page cannot save slots that do not belong to its selected layout', function () {
    $shop = makeShopLandingTenant('landing-slots.localhost');
    $this->landingTenant = $shop['tenant'];

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://landing-slots.localhost/shop/landing-page', [
            'layout' => 'hero',
            'layout_data' => [
                'headline' => 'Rent by the river',
                'description' => 'Bikes for a quiet day out.',
                'button_label' => 'Book now',
                'story_quote' => 'This belongs to Story.',
            ],
            'theme' => 'light',
        ])
        ->assertUnprocessable();

    $shop['tenant']->run(function (): void {
        expect(ShopLandingPage::current())->toBeNull();
    });
});

test('a manager can choose a layout and save booking messages', function () {
    ShopLandingPage::factory()->create([
        'layout' => 'story',
        'layout_data' => [
            'eyebrow' => 'Our story',
            'headline' => 'A shop by the sea',
            'description' => 'Locally owned.',
            'button_label' => 'Book a bike',
            'story_quote' => 'A great ride.',
            'story_byline' => 'Pat',
        ],
    ]);
    $staff = Staff::factory()->create(['role' => StaffRole::Manager]);

    $this->actingAs($staff, 'staff');

    Livewire::test(ShopLandingPageEditor::class)
        ->set('data.layout', 'split')
        ->set('data.layout_data.eyebrow', 'Guided rides')
        ->set('data.layout_data.headline', 'See more by bike')
        ->set('data.layout_data.description', 'Choose your route and ride.')
        ->set('data.layout_data.button_label', 'Book now')
        ->set('data.layout_data.image_position', 'left')
        ->set('data.theme', 'dark')
        ->set('data.booking_notices', [
            ['message' => 'Closed on Monday.', 'tone' => 'info'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $page = ShopLandingPage::current();

    expect($page?->layout)->toBe('split')
        ->and($page?->layout_data['headline'])->toBe('See more by bike')
        ->and($page?->layout_data['image_position'])->toBe('left')
        ->and($page?->theme)->toBe('dark')
        ->and($page?->booking_notices)->toBe([
            ['message' => 'Closed on Monday.', 'tone' => 'info'],
        ]);
});

test('counter staff cannot access the shop home page editor', function () {
    $counter = Staff::factory()->counter()->create();

    $this->actingAs($counter, 'staff');

    expect(ShopLandingPageEditor::canAccess())->toBeFalse();
});
