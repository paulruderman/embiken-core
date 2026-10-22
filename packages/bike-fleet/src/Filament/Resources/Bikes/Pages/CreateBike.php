<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use App\Filament\Shop\Concerns\RequiresShopSetup;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateBike extends CreateRecord
{
    use RequiresShopSetup;

    protected static string $resource = BikeResource::class;

    protected static function shopSetupRequirement(): string
    {
        return ShopSetupReadiness::Bikes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['bike_situation_state'] = BikeSituation::Home;
        $data['bike_situation_reservation_id'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Bike {
            app(FleetMutationLock::class)->lockVariants([(int) $data['bike_model_variant_id']]);

            return Bike::query()->create($data);
        }, attempts: 5);
    }
}
