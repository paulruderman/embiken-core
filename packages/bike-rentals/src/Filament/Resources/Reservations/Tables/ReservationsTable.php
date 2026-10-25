<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Tables;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use App\Models\Location;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('app.rentals_ui.fields.reservation'))
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('app.rentals_ui.fields.customer'))
                    ->searchable(),
                TextColumn::make('stage')
                    ->label(__('app.rentals_ui.fields.stage'))
                    ->badge(),
                LocalizedDateTimeColumn::make('starts_at')
                    ->label(__('app.rentals_ui.fields.starts_at'))
                    ->sortable(),
                LocalizedDateTimeColumn::make('ends_at')
                    ->label(__('app.rentals_ui.fields.ends_at'))
                    ->sortable(),
                TextColumn::make('owed')
                    ->label(__('app.rentals_ui.fields.owed'))
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
                TextColumn::make('paid')
                    ->label(__('app.rentals_ui.fields.paid'))
                    ->formatStateUsing(fn (?int $state, Reservation $record): ?string => $state === null
                        ? null
                        : Money::format($state, $record->currency)),
            ])
            ->filters([
                Filter::make('stage')
                    ->form([
                        Select::make('value')
                            ->label(__('app.rentals_ui.fields.stage'))
                            ->options(ReservationStage::class),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $stage): Builder => $query->where('stage', $stage),
                    )),
                Filter::make('today')
                    ->label(__('app.rentals_ui.filters.today'))
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        function (Builder $query): void {
                            $dayStart = self::shopDayStart();

                            $query
                                ->where('starts_at', '>=', $dayStart)
                                ->where('starts_at', '<', $dayStart->copy()->addDay());
                        },
                    )),
                Filter::make('upcoming')
                    ->label(__('app.rentals_ui.filters.upcoming'))
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('starts_at', '>=', self::shopDayStart()->copy()->addDay()),
                    )),
                Filter::make('past')
                    ->label(__('app.rentals_ui.filters.past'))
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('starts_at', '<', self::shopDayStart()),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function shopDayStart(): CarbonImmutable
    {
        $timezone = Location::query()->value('timezone') ?? config('app.timezone');

        return now($timezone)->startOfDay()->utc();
    }
}
