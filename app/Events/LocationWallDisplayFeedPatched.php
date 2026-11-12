<?php

declare(strict_types=1);

namespace App\Events;

use App\Support\LocationChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LocationWallDisplayFeedPatched implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /**
     * @param  array{slug: string, name: string, component: string, data: array<string, mixed>}  $feed
     */
    public function __construct(
        public array $feed,
        public int $locationId,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(LocationChannel::name($this->locationId))];
    }

    public function broadcastAs(): string
    {
        return 'WallDisplayFeedUpdated';
    }

    /** @return array{feed: array{slug: string, name: string, component: string, data: array<string, mixed>}} */
    public function broadcastWith(): array
    {
        return ['feed' => $this->feed];
    }
}
