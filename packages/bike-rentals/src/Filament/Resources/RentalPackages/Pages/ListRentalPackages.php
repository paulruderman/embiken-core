<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\Pages;

use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use Embiken\BikeRentals\Filament\Resources\RentalPackages\RentalPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListRentalPackages extends ListRecords
{
    use HasPendingSetupTitle;
    use Translatable;

    protected static string $resource = RentalPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
