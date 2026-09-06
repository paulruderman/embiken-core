<?php

namespace App\Events;

use App\Support\LocationChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LocationWeatherPatched implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /**
     * @param  array<string, mixed>  $weather
     */
    public function __construct(
        public array $weather,
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
        return 'WeatherUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->weather;
    }
}
