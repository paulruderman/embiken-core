<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModels\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Models\BikeModel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BikeModelsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                ImageColumn::make('photo')
                    ->label(__('app.fleet.fields.photo'))
                    ->getStateUsing(static function (BikeModel $record): ?string {
                        $path = $record->displayPhotoPath();

                        return $path === null ? null : tenant_asset($path);
                    })
                    ->imageSize(56)
                    ->extraImgAttributes(['style' => 'object-fit: contain;'])
                    ->alt(static fn (BikeModel $record): string => $record->name),
                TextColumn::make('name')
                    ->label(__('app.fleet.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manufacturer')
                    ->label(__('app.fleet.fields.manufacturer'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('bikeCategory.name')
                    ->label(__('app.fleet.fields.category')),
                TextColumn::make('padding_minutes')
                    ->label(__('app.fleet.fields.padding_minutes')),
            ])
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with('bikeCategory'))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::BikeModels,
            __('app.fleet.empty_states.bike_models'),
        );
    }
}
