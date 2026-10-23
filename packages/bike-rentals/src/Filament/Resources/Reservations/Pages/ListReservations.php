<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use App\Filament\Shop\Concerns\HasBookSetupCallout;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    use HasBookSetupCallout;

    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        $action = $this->continueBookSetupAction();

        return $action === null ? [] : [$action];
    }
}
