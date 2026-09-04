<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
