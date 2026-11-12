<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use App\Filament\Shop\Concerns\HasBookSetupCallout;
use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    use HasBookSetupCallout;
    use HasPendingSetupTitle;

    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        $action = $this->continueBookSetupAction();

        return $action === null ? [] : [$action];
    }
}
