<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRentalPackages extends ListRecords
{
    use HasPendingSetupTitle;

    protected static string $resource = RentalPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
