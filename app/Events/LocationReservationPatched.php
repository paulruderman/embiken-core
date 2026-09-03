<?php

namespace App\Events;

use App\Support\LocationChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LocationReservationPatched implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /**
     * @param  array<string, mixed>  $reservation
     */
    public function __construct(
        public string $action,
        public array $reservation,
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
            'created' => 'ReservationCreated',
            'deleted' => 'ReservationDeleted',
            default => 'ReservationUpdated',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        if ($this->action === 'deleted') {
            return ['id' => $this->reservation['id']];
        }

        return $this->reservation;
    }
}
