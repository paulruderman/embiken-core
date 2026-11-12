<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use App\Filament\Shop\Concerns\HasBookSetupCallout;
use App\Filament\Shop\Concerns\HasPendingSetupTitle;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Embiken\BikeRentals\Filament\Resources\Reservations\Widgets\TodayReservationLinesWidget;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReservations extends ListRecords
{
    use HasBookSetupCallout;
    use HasPendingSetupTitle;

    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        $action = $this->continueBookSetupAction();

        return $action === null ? [] : [$action];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make(__('app.rentals_ui.filters.all'))];

        foreach (ReservationStage::cases() as $stage) {
            $tabs[$stage->value] = Tab::make($stage->getLabel())
                ->badge(static fn (): int => Reservation::query()->where('stage', $stage)->count())
                ->deferBadge()
                ->badgeColor($stage->getColor())
                ->badgeIcon($stage->getIcon())
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->where('stage', $stage));
        }

        return $tabs;
    }

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [TodayReservationLinesWidget::class];
    }
}
