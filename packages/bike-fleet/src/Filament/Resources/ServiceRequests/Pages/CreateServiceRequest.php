<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages;

use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    public function mount(): void
    {
        if (! Bike::query()->exists()) {
            Notification::make()
                ->title('Add a bike first')
                ->body('Add a bike to your fleet before creating a service request.')
                ->warning()
                ->send();

            $this->redirect(ServiceRequestResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): ServiceRequest {
            $bike = app(FleetMutationLock::class)
                ->lockBikeForMutation(Bike::query()->findOrFail((int) $data['bike_id']));

            return $bike->serviceRequests()->create($data);
        }, attempts: 5);
    }
}
