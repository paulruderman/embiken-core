<?php

namespace App\Filament\Shop\Resources\Reservations\Pages;

use App\Filament\Shop\Resources\Reservations\ReservationResource;
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
