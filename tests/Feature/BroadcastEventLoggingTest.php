<?php

use App\Listeners\LogBroadcastEvent;
use App\Models\Tenant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

afterEach(function () {
    tenancy()->end();
});

test('broadcast events are written to the dedicated log with sensitive payload values redacted', function () {
    $tenant = new Tenant;
    $tenant->forceFill(['id' => 'shop-42']);
    tenancy()->initialize($tenant);

    $event = new class implements ShouldBroadcast
    {
        public function broadcastOn(): array
        {
            return [new Channel('tenant.test.location.1')];
        }

        public function broadcastAs(): string
        {
            return 'BikeUpdated';
        }

        public function broadcastWith(): array
        {
            return [
                'id' => 10,
                'customer' => [
                    'email' => 'renter@example.com',
                    'phone' => '555-0100',
                ],
                'myrental_token' => 'private-token',
                'stage' => 'confirmed',
            ];
        }
    };
    $logger = Mockery::mock(LoggerInterface::class);

    Log::shouldReceive('build')->once()->with(Mockery::on(
        fn (array $config): bool => $config['driver'] === 'daily'
            && $config['path'] === dirname(config('logging.channels.broadcasts.path')).'/shop-42.log'
            && $config['max_files'] === 14,
    ))->andReturn($logger);
    $logger->shouldReceive('info')->once()->with('Broadcast event dispatched', Mockery::on(
        fn (array $context): bool => $context['event'] === class_basename($event)
            && $context['broadcast_name'] === 'BikeUpdated'
            && $context['channels'] === ['tenant.test.location.1']
            && $context['tenant_id'] === 'shop-42'
            && $context['payload'] === [
                'id' => 10,
                'customer' => [
                    'email' => '[REDACTED]',
                    'phone' => '[REDACTED]',
                ],
                'myrental_token' => '[REDACTED]',
                'stage' => 'confirmed',
            ],
    ));

    app(LogBroadcastEvent::class)->handle($event::class, [$event]);
});

test('broadcast log channel rotates daily and keeps the configured retention window', function () {
    expect(config('logging.channels.broadcasts'))
        ->driver->toBe('daily')
        ->path->toBe(storage_path('logs/broadcasts/central.log'))
        ->max_files->toBe(14);
});
