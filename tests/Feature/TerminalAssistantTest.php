<?php

use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Tenant;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Customer;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\TerminalAssistant\TerminalAssistantTools;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
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
    $systemPrompt = $requestBodies[0]['messages'][0]['content'];

    expect($requestBodies[0]['tools'][0]['function']['parameters']['properties'])
        ->toHaveKeys(['start_date', 'end_date']);

    expect($encodedBodies)
        ->not->toContain($customerName)
        ->not->toContain($customerEmail)
        ->not->toContain('212 555 0100')
        ->not->toContain($myRentalToken)
        ->toContain('[email removed]')
        ->toContain('[phone removed]')
        ->toContain($reservation->public_id);

    expect($systemPrompt)
        ->toContain('The current date in the shop timezone '.$shop['location']->timezone.' is '.now($shop['location']->timezone)->toDateString())
        ->toContain('Never classify a shop as busy or quiet without a staff-provided threshold.')
        ->toContain('The configured first day of the shop week is '.$shop['location']->week_start_day)
        ->toContain('For “rest of the week”, use period rest_of_week')
        ->toContain('ask for clarification if wording could reasonably mean more than one period.');
});

test('terminal assistant preserves empty tool arguments as an object on the follow-up request', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-empty-tool-arguments.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];
    config()->set('services.terminal_assistant.driver', 'ollama');
    config()->set('services.terminal_assistant.ollama_url', 'http://127.0.0.1:11434');
    config()->set('services.terminal_assistant.ollama_model', 'gemma4:e2b');

    Http::fake([
        'http://127.0.0.1:11434/api/chat' => Http::sequence()
            ->push([
                'message' => [
                    'role' => 'assistant',
                    'content' => '',
                    'tool_calls' => [[
                        'function' => [
                            'name' => 'list_todays_reservations',
                            'arguments' => [],
                        ],
                    ]],
                ],
            ])
            ->push([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'There are no reservations today.',
                ],
            ]),
    ]);

    $this->withToken($shop['token'])
        ->actingAs($shop['staff'], 'staff')
        ->postJson('http://assistant-empty-tool-arguments.localhost/terminal/assistant', [
            'messages' => [['role' => 'user', 'content' => 'Many reservations today?']],
        ])
        ->assertOk()
        ->assertJsonPath('message', 'There are no reservations today.');

    $requests = Http::recorded()->pluck(0)->all();
    $followUpMessages = $requests[1]->data()['messages'];

    expect($followUpMessages[2]['tool_calls'][0]['function']['arguments'])
        ->toBeInstanceOf(stdClass::class);
});

test('terminal assistant summarizes a requested date range and current fleet without customer details', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-shop-day.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];

    $summary = $shop['tenant']->run(function () use ($shop): array {
        $today = now($shop['location']->timezone)->startOfDay();
        $variant = BikeModelVariant::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'Private Customer Name',
            'email' => 'private.customer@example.test',
            'phone' => '212 555 0199',
        ]);
        $reservations = collect([9, 9, 16])->map(fn (int $startHour) => Reservation::factory()
            ->confirmed()
            ->terminal()
            ->recycle($shop['location'])
            ->create([
                'customer_id' => $customer->id,
                'starts_at' => $today->copy()->addHours($startHour)->utc(),
                'ends_at' => $today->copy()->addHours($startHour + 2)->utc(),
            ]));
        $tomorrowReservation = Reservation::factory()
            ->confirmed()
            ->terminal()
            ->recycle($shop['location'])
            ->create([
                'customer_id' => $customer->id,
                'starts_at' => $today->copy()->addDay()->addHours(10)->utc(),
                'ends_at' => $today->copy()->addDay()->addHours(12)->utc(),
            ]);
        $cancelledReservation = Reservation::factory()
            ->terminal()
            ->recycle($shop['location'])
            ->create([
                'customer_id' => $customer->id,
                'stage' => ReservationStage::Cancelled,
                'starts_at' => $today->copy()->addHours(11)->utc(),
                'ends_at' => $today->copy()->addHours(13)->utc(),
            ]);

        foreach ([2, 1, 0] as $index => $lineCount) {
            for ($line = 0; $line < $lineCount; $line++) {
                BikeReservation::factory()->create([
                    'reservation_id' => $reservations[$index]->id,
                    'product_id' => $variant->id,
                ]);
            }
        }

        BikeReservation::factory()->create([
            'reservation_id' => $tomorrowReservation->id,
            'product_id' => $variant->id,
        ]);

        Bike::factory()->for($shop['location'])->count(2)->create([
            'bike_model_variant_id' => $variant->id,
        ]);
        $outOfServiceBike = Bike::factory()->outOfService()->for($shop['location'])->create([
            'bike_model_variant_id' => $variant->id,
        ]);
        $blockingServiceBike = Bike::factory()->for($shop['location'])->create([
            'bike_model_variant_id' => $variant->id,
        ]);
        Bike::factory()->for($shop['location'])->create([
            'bike_model_variant_id' => $variant->id,
            'bike_situation_state' => BikeSituation::RentedOut,
        ]);
        ServiceRequest::factory()->for($outOfServiceBike)->create();
        ServiceRequest::factory()->for($blockingServiceBike)->create();

        return app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
            'period' => 'custom',
            'start_date' => $today->toDateString(),
            'end_date' => $today->copy()->addDay()->toDateString(),
        ]);
    });

    expect($summary['reservations']['intersecting_period_count'])->toBe(4)
        ->and($summary['reservations']['cancelled_intersecting_period_count'])->toBe(1)
        ->and($summary['reservations']['bike_line_count'])->toBe(4)
        ->and($summary['reservations']['starts_by_date'])->toBe([
            [
                'date' => now($shop['location']->timezone)->toDateString(),
                'reservation_start_count' => 3,
                'bike_line_start_count' => 3,
                'starts_by_hour' => [
                    ['hour_local' => '09:00', 'reservation_start_count' => 2, 'bike_line_start_count' => 3],
                    ['hour_local' => '16:00', 'reservation_start_count' => 1, 'bike_line_start_count' => 0],
                ],
            ],
            [
                'date' => now($shop['location']->timezone)->addDay()->toDateString(),
                'reservation_start_count' => 1,
                'bike_line_start_count' => 1,
                'starts_by_hour' => [
                    ['hour_local' => '10:00', 'reservation_start_count' => 1, 'bike_line_start_count' => 1],
                ],
            ],
        ])
        ->and($summary['fleet']['bike_count'])->toBe(5)
        ->and($summary['fleet']['in_service_bike_count'])->toBe(4)
        ->and($summary['fleet']['out_of_service_bike_count'])->toBe(1)
        ->and($summary['fleet']['blocking_service_bike_count'])->toBe(2)
        ->and($summary['fleet']['unavailable_bike_count'])->toBe(2)
        ->and($summary['fleet']['situation_counts']['rented_out'])->toBe(1);

    expect(json_encode($summary, JSON_THROW_ON_ERROR))
        ->not->toContain('Private Customer Name')
        ->not->toContain('private.customer@example.test')
        ->not->toContain('212 555 0199');
});

test('terminal assistant limits reservation summaries to valid ranges of seven dates', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-date-range.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        $startDate = now($shop['location']->timezone)->toDateString();
        $validSevenDateSummary = app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
            'period' => 'custom',
            'start_date' => '2026-10-31',
            'end_date' => '2026-11-06',
        ]);

        expect($validSevenDateSummary['end_date'])
            ->toBe('2026-11-06');

        expect(fn () => app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => now($shop['location']->timezone)->addDays(7)->toDateString(),
        ]))->toThrow(ValidationException::class);

        expect(fn () => app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
            'period' => 'custom',
            'start_date' => '2026-02-30',
        ]))->toThrow(ValidationException::class);
    });
});

test('terminal assistant resolves relative reservation periods using the location week start', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-relative-periods.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];

    $shop['tenant']->run(function () use ($shop): void {
        $today = now($shop['location']->timezone)->startOfDay();
        $weekStartDays = [
            'saturday' => 6,
            'sunday' => 0,
            'monday' => 1,
        ];

        foreach ($weekStartDays as $weekStartDay => $dayOfWeek) {
            $shop['location']->update(['week_start_day' => $weekStartDay]);
            $currentWeekStart = $today->copy()->subDays(($today->dayOfWeek - $dayOfWeek + 7) % 7);

            $restOfWeek = app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
                'period' => 'rest_of_week',
            ]);
            $nextWeek = app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
                'period' => 'next_week',
            ]);

            expect($restOfWeek['start_date'])->toBe($today->toDateString())
                ->and($restOfWeek['end_date'])->toBe($currentWeekStart->copy()->addDays(6)->toDateString())
                ->and($nextWeek['start_date'])->toBe($currentWeekStart->copy()->addDays(7)->toDateString())
                ->and($nextWeek['end_date'])->toBe($currentWeekStart->copy()->addDays(13)->toDateString());
        }

        $tomorrow = app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
            'period' => 'tomorrow',
        ]);
        $nextSevenDays = app(TerminalAssistantTools::class)->execute('summarize_reservations_period', [
            'period' => 'next_seven_days',
        ]);

        expect($tomorrow['start_date'])->toBe($today->copy()->addDay()->toDateString())
            ->and($tomorrow['end_date'])->toBe($today->copy()->addDay()->toDateString())
            ->and($nextSevenDays['start_date'])->toBe($today->toDateString())
            ->and($nextSevenDays['end_date'])->toBe($today->copy()->addDays(6)->toDateString());
    });
});

test('terminal assistant explains when Ollama rejects a tool-call request', function (): void {
    $shop = provisionTerminalAssistantShop('assistant-tool-rejected.localhost');
    $this->terminalAssistantTenant = $shop['tenant'];
    config()->set('services.terminal_assistant.driver', 'ollama');
    config()->set('services.terminal_assistant.ollama_url', 'http://127.0.0.1:11434');
    config()->set('services.terminal_assistant.ollama_model', 'gemma4:e2b');

    Http::fake([
        'http://127.0.0.1:11434/api/chat' => Http::response([
            'error' => "Value looks like object, but can't find closing '}' symbol",
        ], 400),
    ]);

    $this->withToken($shop['token'])
        ->actingAs($shop['staff'], 'staff')
        ->postJson('http://assistant-tool-rejected.localhost/terminal/assistant', [
            'messages' => [['role' => 'user', 'content' => 'Many reservations today?']],
        ])
        ->assertStatus(503)
        ->assertJsonPath('message', __('app.terminal_ui.assistant_tool_call_rejected'));
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
