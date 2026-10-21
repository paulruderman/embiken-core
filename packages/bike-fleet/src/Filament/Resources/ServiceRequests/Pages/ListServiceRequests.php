<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages;

use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createServiceRequest')
                ->label('Create Service Request')
                ->action(function (): void {
                    if (! Bike::query()->exists()) {
                        Notification::make()
                            ->title('Add a bike first')
                            ->body('Add a bike to your fleet before creating a service request.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->redirect(ServiceRequestResource::getUrl('create'));
                }),
        ];
    }
}
