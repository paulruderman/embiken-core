<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateServiceRequest extends CreateRecord
{
    use RequiresShopSetup;

    protected static string $resource = ServiceRequestResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::ServiceRequests;
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
