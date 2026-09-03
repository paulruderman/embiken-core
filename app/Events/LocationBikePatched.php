<?php

namespace App\Events;

use App\Support\LocationChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LocationBikePatched implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /**
     * @param  array<string, mixed>  $bike
     */
    public function __construct(
        public string $action,
        public array $bike,
        public int $locationId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(LocationChannel::name($this->locationId))];
    }

    public function broadcastAs(): string
    {
        return match ($this->action) {
            'created' => 'BikeCreated',
            'deleted' => 'BikeDeleted',
            default => 'BikeUpdated',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        if ($this->action === 'deleted') {
            return ['id' => $this->bike['id']];
        }

        return $this->bike;
    }
}
