<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Pages;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LogicException;

class EditBike extends EditRecord
{
    protected static string $resource = BikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->using(function (Model $record): bool {
                return DB::transaction(function () use ($record): bool {
                    $bike = app(FleetMutationLock::class)
                        ->lockBikeForMutation(Bike::query()->whereKey($record->getKey())->firstOrFail());

                    return (bool) $bike->delete();
                }, attempts: 5);
            }),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        $notification = parent::getSavedNotification();
        $readiness = app(ShopSetupReadiness::class);

        if ($notification === null
            || request()->query('setup_target') !== ShopSetupReadiness::BookableFleet
            || ! $readiness->hasStep(ShopSetupReadiness::BookableFleet)
            || ! $readiness->step(ShopSetupReadiness::BookableFleet)['complete']) {
            return $notification;
        }

        return $readiness->addContinueSetupAction($notification, $readiness->step(ShopSetupReadiness::BookableFleet)['module_id']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Bike {
            $bikeId = (int) $record->getKey();
            $originalVariantId = (int) Bike::query()->whereKey($bikeId)->value('bike_model_variant_id');
            $targetVariantId = (int) $data['bike_model_variant_id'];
            $locks = app(FleetMutationLock::class);
            $locks->lockVariants([$originalVariantId, $targetVariantId]);
            $bike = $locks->lockBikes([$bikeId])->firstOrFail();

            if ($bike->bike_model_variant_id !== $originalVariantId) {
                throw new LogicException('The bike variant changed while its edit lock was being acquired.');
            }

            if (Schema::hasTable('bike_reservation')) {
                $reservationLines = DB::table('bike_reservation')->where('bike_id', $bikeId);

                if ($targetVariantId !== $originalVariantId && $reservationLines->exists()) {
                    throw ValidationException::withMessages([
                        'bike_model_variant_id' => 'A bike with reservation history cannot change variants.',
                    ]);
                }

                if (! (bool) ($data['in_service'] ?? $bike->in_service)
                    && $reservationLines->where('status', '!=', 'in')
                        ->whereNotIn('reservation_id', DB::table('reservations')->where('stage', 'cancelled')->select('id'))
                        ->exists()) {
                    throw ValidationException::withMessages([
                        'in_service' => 'Release or return this bike before parking it.',
                    ]);
                }
            }

            $bike->fill($data);
            $bike->save();

            return $bike;
        }, attempts: 5);
    }
}
