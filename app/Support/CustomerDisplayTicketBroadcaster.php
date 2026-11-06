<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\DeviceSurface;
use App\Events\CustomerDisplayTicketPatched;
use App\Models\Device;
use Embiken\BikeRentals\Http\Resources\CustomerDisplayTicketResource;
use Embiken\BikeRentals\Models\Reservation;

class CustomerDisplayTicketBroadcaster
{
    public function broadcastForTerminal(Device $terminal, ?Reservation $reservation): void
    {
        $ticket = $reservation === null
            ? null
            : CustomerDisplayTicketResource::make($reservation)->resolve();

        foreach ($terminal->customerDisplays()
            ->where('surface', DeviceSurface::CustomerDisplay->value)
            ->whereNotNull('paired_at')
            ->get() as $customerDisplay) {
            event(new CustomerDisplayTicketPatched(
                (string) tenant('id'),
                $customerDisplay->id,
                $ticket,
            ));
        }
    }

    public function broadcastForReservation(Reservation $reservation): void
    {
        $terminals = Device::query()
            ->where('surface', DeviceSurface::Terminal->value)
            ->whereNotNull('paired_at')
            ->where('focused_reservation_id', $reservation->id)
            ->where('location_id', $reservation->location_id)
            ->get();

        foreach ($terminals as $terminal) {
            $this->broadcastForTerminal($terminal, $reservation);
        }
    }

    public function clearReservation(Reservation $reservation): void
    {
        $terminals = Device::query()
            ->where('surface', DeviceSurface::Terminal->value)
            ->whereNotNull('paired_at')
            ->where('focused_reservation_id', $reservation->id)
            ->where('location_id', $reservation->location_id)
            ->get();

        foreach ($terminals as $terminal) {
            $terminal->forceFill(['focused_reservation_id' => null])->save();
            $this->broadcastForTerminal($terminal, null);
        }
    }
}
