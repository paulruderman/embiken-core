<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\BikeCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BikesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('bid')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('variant.bikeModel.name')
                    ->label('Model'),
                TextColumn::make('variant.size')
                    ->label('Size'),
                TextColumn::make('variant.bikeModel.bikeCategory.name')
                    ->label('Category')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('in_service')
                    ->boolean(),
                IconColumn::make('self_bookable')
                    ->boolean(),
                TextColumn::make('bike_situation_state')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('model')
                    ->label('Model')
                    ->relationship('variant.bikeModel', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('category')
                    ->form([
                        Select::make('value')
                            ->label('Category')
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
                    ->label('Out of service')
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('in_service', false),
                    )),
                Filter::make('not_bookable')
                    ->label('Not bookable online')
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['isActive'] ?? false,
                        fn (Builder $query): Builder => $query->where('self_bookable', false),
                    )),
                SelectFilter::make('bike_situation_state')
                    ->label('Situation')
                    ->options(BikeSituation::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::Bikes,
            'No bikes in your fleet yet',
        );
    }
}
