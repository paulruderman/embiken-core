<?php

namespace App\Filament\Shop\Resources\Reservations\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('notes'),
                Textarea::make('damage_notes'),
            ]);
    }
}
