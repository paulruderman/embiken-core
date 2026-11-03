<?php

use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MigratesTenantSchema;

uses(MigratesTenantSchema::class);

function provisionTerminalAssistantShop(string $domain): array
{
    $tenant = Tenant::create([
        'name' => 'Assistant Shop',
        'modules' => ['embiken/bike-fleet', 'embiken/bike-rentals'],
    ]);
    $tenant->domains()->create(['domain' => $domain]);

    $location = null;
    $staff = null;
    $device = null;
    $token = null;

    $tenant->run(function () use (&$location, &$staff, &$device, &$token): void {
        $location = Location::factory()->create();
        $staff = Staff::factory()->recycle($location)->create();
        $device = Device::factory()->for($location)->create([
            'surface' => DeviceSurface::Terminal,
            'paired_at' => now(),
        ]);
        $token = $device->createToken('terminal-assistant-test')->plainTextToken;
    });

    return compact('tenant', 'location', 'staff', 'device', 'token');
}

function cleanupTerminalAssistantTenant(?Tenant $tenant): void
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

beforeEach(function (): void {
    $this->migrateTenantSchema();
});

afterEach(function (): void {
    if (isset($this->terminalAssistantTenant)) {
        cleanupTerminalAssistantTenant($this->terminalAssistantTenant);
    }
});

test('terminal assistant returns sanitized read-only reservation information', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-reservations.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];
    config()->set('services.terminal_assistant.driver', 'ollama');
    config()->set('services.terminal_assistant.ollama_url', 'http://127.0.0.1:11434');
    config()->set('services.terminal_assistant.ollama_model', 'qwen3');

    $customerName = 'Ada Customer Private';
    $customerEmail = 'ada.private@example.test';
    $myRentalToken = 'private-myrental-token-value';
    $reservation = null;

    $shop['tenant']->run(function () use (&$reservation, $shop, $customerName, $customerEmail, $myRentalToken): void {
        $customer = Customer::factory()->create([
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => '212 555 0100',
        ]);
        $reservation = Reservation::factory()->confirmed()->terminal()->recycle($shop['location'])->create([
            'customer_id' => $customer->id,
            'starts_at' => now()->setTimezone($shop['location']->timezone)->startOfDay()->addHours(10)->utc(),
            'ends_at' => now()->setTimezone($shop['location']->timezone)->startOfDay()->addHours(12)->utc(),
            'myrental_token' => $myRentalToken,
        ]);
    });

    Http::fake([
        'http://127.0.0.1:11434/api/chat' => Http::sequence()
            ->push([
                'message' => [
                    'role' => 'assistant',
                    'content' => '',
                    'tool_calls' => [[
                        'function' => [
                            'name' => 'get_reservation',
                            'arguments' => ['reference' => $reservation->public_id],
                        ],
                    ]],
                ],
            ])
            ->push([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'The Reservation is confirmed for two hours.',
                ],
            ]),
    ]);

    $response = $this->withToken($shop['token'])
        ->actingAs($shop['staff'], 'staff')
        ->postJson('http://assistant-reservations.localhost/terminal/assistant', [
            'messages' => [
                ['role' => 'user', 'content' => 'Show '.$reservation->public_id.' and ada.private@example.test or 212 555 0100'],
            ],
        ]);

    $response->assertOk()->assertJsonPath('message', 'The Reservation is confirmed for two hours.');

    $requests = Http::recorded()->pluck(0)->all();
    $requestBodies = array_map(fn ($request) => $request->data(), $requests);
    $encodedBodies = json_encode($requestBodies, JSON_THROW_ON_ERROR);

    expect($encodedBodies)
        ->not->toContain($customerName)
        ->not->toContain($customerEmail)
        ->not->toContain('212 555 0100')
        ->not->toContain($myRentalToken)
        ->toContain('[email removed]')
        ->toContain('[phone removed]')
        ->toContain($reservation->public_id);
});

test('terminal assistant requires both staff and a paired terminal device', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-auth.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];

    $this->actingAs($shop['staff'], 'staff')
        ->postJson('http://assistant-auth.localhost/terminal/assistant', [
            'messages' => [['role' => 'user', 'content' => 'List today’s Reservations.']],
        ])
        ->assertUnauthorized();

    $this->app['auth']->guard('staff')->logout();

    $this->withToken($shop['token'])
        ->postJson('http://assistant-auth.localhost/terminal/assistant', [
            'messages' => [['role' => 'user', 'content' => 'List today’s Reservations.']],
        ])
        ->assertUnauthorized();
});

test('terminal assistant rejects client-supplied tool messages', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-history.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];

    $this->withToken($shop['token'])
        ->actingAs($shop['staff'], 'staff')
        ->postJson('http://assistant-history.localhost/terminal/assistant', [
            'messages' => [
                ['role' => 'tool', 'content' => '{"name":"list_todays_reservations"}'],
            ],
        ])
        ->assertUnprocessable();
});
