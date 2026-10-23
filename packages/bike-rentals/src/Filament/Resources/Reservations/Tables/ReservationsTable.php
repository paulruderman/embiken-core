<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Tables;

use App\Models\Location;
use Carbon\CarbonImmutable;
use Embiken\BikeRentals\Enums\ReservationStage;
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
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('stage')
                    ->badge(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime(),
                TextColumn::make('owed')
                    ->label('Owed'),
                TextColumn::make('paid')
                    ->label('Paid'),
            ])
            ->filters([
                Filter::make('stage')
                    ->form([
                        Select::make('value')
                            ->label('Stage')
                            ->options(ReservationStage::class),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $stage): Builder => $query->where('stage', $stage),
                    )),
                Filter::make('today')
                    ->label('Today')
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
                    ->label('Upcoming')
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('starts_at', '>=', self::shopDayStart()->copy()->addDay()),
                    )),
                Filter::make('past')
                    ->label('Past')
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
