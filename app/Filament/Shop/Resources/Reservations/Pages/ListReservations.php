<?php

namespace App\Filament\Shop\Resources\Reservations\Pages;

use App\Filament\Shop\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
