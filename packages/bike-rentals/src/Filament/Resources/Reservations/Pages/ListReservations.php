<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    public function getSubheading(): ?string
    {
        $step = app(ShopSetupReadiness::class)->nextIncompleteSetupStep();

        return $step === null
            ? null
            : 'Online Book setup is incomplete. Next: '.$step['action_label'].'.';
    }

    protected function getHeaderActions(): array
    {
        $step = app(ShopSetupReadiness::class)->nextIncompleteSetupStep();

        if ($step === null || $step['action_url'] === null) {
            return [];
        }

        return [
            Action::make('continueSetup')
                ->label('Continue setup')
                ->color('warning')
                ->icon('heroicon-m-arrow-right')
                ->url($step['action_url']),
        ];
    }
}
