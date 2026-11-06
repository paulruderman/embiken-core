<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class CustomerDisplayTicketPatched implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /** @param array<string, mixed>|null $ticket */
    public function __construct(
        public string $tenantId,
        public int $deviceId,
        public ?array $ticket,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}.cfd.{$this->deviceId}")];
    }

    public function broadcastAs(): string
    {
        return 'CustomerDisplayTicketUpdated';
    }

    /** @return array{ticket: array<string, mixed>|null} */
    public function broadcastWith(): array
    {
        return ['ticket' => $this->ticket];
    }
}
