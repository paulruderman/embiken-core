<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages;

use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): ServiceRequest {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $bikeIds = [(int) $serviceRequest->bike_id, (int) $data['bike_id']];
            $bikes = app(FleetMutationLock::class)->lockBikesForMutation($bikeIds)->keyBy('id');

            $serviceRequest->fill($data);
            $serviceRequest->setRelation('bike', $bikes->get((int) $data['bike_id']));
            $serviceRequest->save();

            return $serviceRequest;
        }, attempts: 5);
    }
}
