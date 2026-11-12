<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Filament\Resources\Bikes\BikeResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeCategory;
use Embiken\BikeFleet\Models\BikeSpecification;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BikesTable
{
    public static function configure(Table $table, bool $showOccupyingReservation): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('bid')
                    ->label(__('app.fleet.fields.bid'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->label(__('app.fleet.fields.serial_number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('variant.bikeModel.name')
                    ->label(__('app.fleet.fields.model')),
                TextColumn::make('variant.size')
                    ->label(__('app.fleet.fields.size')),
                TextColumn::make('variant.bikeModel.bikeCategory.name')
                    ->label(__('app.fleet.fields.category'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('in_service')
                    ->label(__('app.fleet.fields.in_service'))
                    ->boolean(),
                IconColumn::make('self_bookable')
                    ->label(__('app.fleet.fields.self_bookable'))
                    ->boolean(),
                TextColumn::make('bike_situation_state')
                    ->label(__('app.fleet.fields.situation'))
                    ->badge(),
                TextColumn::make('specifications_summary')
                    ->label(__('app.fleet.fields.specifications'))
                    ->getStateUsing(fn (Bike $record): ?string => $record->specifications
                        ->map(fn (BikeSpecification $specification): string => "{$specification->name}: {$specification->value}")
                        ->implode(' · ') ?: null)
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                ...($showOccupyingReservation ? [
                    TextColumn::make('occupying_reservation')
                        ->label(__('app.fleet.fields.occupying_reservation'))
                        ->getStateUsing(function (Bike $record): ?string {
                            if ($record->bike_situation_state === BikeSituation::Home) {
                                return null;
                            }

                            $reservationId = $record->bike_situation_reservation_id;

                            if ($reservationId === null) {
                                return __('app.fleet.table.no_reservation_linked');
                            }

                            $reservation = $record->getRelationValue('situationReservation');

                            if (! $reservation instanceof Model) {
                                return '#'.$reservationId;
                            }

                            $customer = $reservation->getRelationValue('customer');
                            $customerName = $customer instanceof Model ? $customer->getAttribute('name') : null;

                            return '#'.$reservation->getKey().' · '.($customerName ?? __('app.fleet.table.customer_unavailable'));
                        })
                        ->placeholder('—'),
                ] : []),
            ])
            ->filters([
                SelectFilter::make('model')
                    ->label(__('app.fleet.fields.model'))
                    ->relationship('variant.bikeModel', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('category')
                    ->form([
                        Select::make('value')
                            ->label(__('app.fleet.fields.category'))
                            ->options(fn (): array => BikeCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, int|string $categoryId): Builder => $query->whereHas(
                            'variant.bikeModel',
                            fn (Builder $query): Builder => $query->where('bike_category_id', $categoryId),
                        ),
                    )),
                Filter::make('out_of_service')
                    ->label(__('app.fleet.filters.out_of_service'))
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('in_service', false),
                    )),
                Filter::make('not_bookable')
                    ->label(__('app.fleet.filters.not_bookable_online'))
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('self_bookable', false),
                    )),
                SelectFilter::make('bike_situation_state')
                    ->label(__('app.fleet.fields.situation'))
                    ->options(BikeSituation::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Bike $record): string => BikeResource::getUrl('edit', array_filter([
                        'record' => $record,
                        'setup_target' => request()->query('setup_target'),
                    ], fn (mixed $value): bool => $value !== null))),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        $relationships = ['specifications'];

        if ($showOccupyingReservation) {
            $relationships[] = 'situationReservation.customer';
        }

        $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with($relationships));

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::Bikes,
            __('app.fleet.empty_states.bikes'),
        );
    }
}
