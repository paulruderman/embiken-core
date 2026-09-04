<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReservation extends ViewRecord
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
