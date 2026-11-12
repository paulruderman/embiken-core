<?php

declare(strict_types=1);

namespace App\Events;

use App\Support\LocationChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LocationDisplayPatched implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /** @param array<string, mixed> $displayState */
    public function __construct(
        public array $displayState,
        public int $locationId,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(LocationChannel::name($this->locationId))];
    }

    public function broadcastAs(): string
    {
        return 'DisplayUpdated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->displayState;
    }
}
