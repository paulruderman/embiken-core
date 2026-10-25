<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Widgets;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodayReservationLinesWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $timezone = LocalizedDateTimeColumn::shopTimezone();
        $dayStart = now($timezone)->startOfDay()->utc();
        $dayEnd = now($timezone)->startOfDay()->addDay()->utc();

        return $table
            ->heading(__('app.rentals_ui.today_schedule'))
            ->query(
                BikeReservation::query()
                    ->whereIn('status', [BikeReservationStatus::Assigned, BikeReservationStatus::Out])
                    ->whereHas('reservation', static function (Builder $query) use ($dayStart, $dayEnd): void {
                        $query
                            ->whereIn('stage', [ReservationStage::Confirmed, ReservationStage::Out])
                            ->where(static function (Builder $query) use ($dayStart, $dayEnd): void {
                                $query
                                    ->where(static fn (Builder $query): Builder => $query
                                        ->where('starts_at', '>=', $dayStart)
                                        ->where('starts_at', '<', $dayEnd))
                                    ->orWhere(static fn (Builder $query): Builder => $query
                                        ->where('ends_at', '>=', $dayStart)
                                        ->where('ends_at', '<', $dayEnd));
                            });
                    })
                    ->with([
                        'reservation.customer',
                        'product.bikeModel',
                        'bike.situationReservation.customer',
                    ])
                    ->orderBy(Reservation::query()
                        ->select('starts_at')
                        ->whereColumn('reservations.id', 'bike_reservation.reservation_id')),
            )
            ->columns([
                TextColumn::make('reservation_id')
                    ->label(__('app.rentals_ui.fields.reservation'))
                    ->url(fn (BikeReservation $record): string => ReservationResource::getUrl('view', [
                        'record' => $record->reservation_id,
                    ]))
                    ->sortable(),
                TextColumn::make('reservation.customer.name')
                    ->label(__('app.rentals_ui.fields.customer'))
                    ->searchable(),
                TextColumn::make('product.bikeModel.name')
                    ->label(__('app.fleet.fields.model')),
                TextColumn::make('product.size')
                    ->label(__('app.fleet.fields.size')),
                TextColumn::make('bike.bid')
                    ->label(__('app.fleet.fields.bid'))
                    ->placeholder(__('app.rentals_ui.table.unassigned')),
                LocalizedDateTimeColumn::make('reservation.starts_at')
                    ->label(__('app.rentals_ui.fields.starts_at')),
                LocalizedDateTimeColumn::make('reservation.ends_at')
                    ->label(__('app.rentals_ui.fields.ends_at')),
                TextColumn::make('bike.bike_situation_state')
                    ->label(__('app.fleet.fields.situation'))
                    ->badge()
                    ->color(fn (BikeReservation $record): ?string => $record->bike?->bike_situation_state->getColor())
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('app.rentals_ui.fields.line_status'))
                    ->badge()
                    ->color(fn (BikeReservation $record): string => $record->status->getColor()),
            ])
            ->emptyStateHeading(__('app.rentals_ui.today_schedule_empty'))
            ->paginated([10, 25, 50]);
    }
}
