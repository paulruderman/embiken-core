<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\DeviceSurface;
use App\Models\Device;
use Embiken\BikeRentals\Http\Resources\CustomerDisplayTicketResource;
use Embiken\BikeRentals\Models\Reservation;

class CustomerDisplayTicketData
{
    /** @return array<string, mixed>|null */
    public function forCustomerDisplay(Device $device): ?array
    {
        if ($device->surface !== DeviceSurface::CustomerDisplay || $device->paired_at === null) {
            return null;
        }

        $terminal = $device->pairedTerminalDevice;
        $reservation = $terminal?->focusedReservation;

        if ($terminal?->paired_at === null || $reservation === null || $reservation->location_id !== $device->location_id) {
            return null;
        }

        return $this->forReservation($reservation);
    }

    /** @return array<int, array<string, mixed>|null> */
    public function forTerminal(Device $terminal): array
    {
        if ($terminal->surface !== DeviceSurface::Terminal) {
            return [];
        }

        $reservation = $terminal->focusedReservation;
        $ticket = $reservation === null ? null : $this->forReservation($reservation);
        $tickets = [];

        foreach ($terminal->customerDisplays()
            ->where('surface', DeviceSurface::CustomerDisplay->value)
            ->whereNotNull('paired_at')
            ->get() as $device) {
            $tickets[$device->id] = $ticket;
        }

        return $tickets;
    }

    /** @return array<string, mixed>|null */
    public function forReservation(?Reservation $reservation): ?array
    {
        return $reservation === null
            ? null
            : CustomerDisplayTicketResource::make($reservation)->resolve();
    }
}
