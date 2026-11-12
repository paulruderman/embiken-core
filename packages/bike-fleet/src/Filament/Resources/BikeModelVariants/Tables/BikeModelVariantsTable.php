<?php

namespace Embiken\BikeFleet\Filament\Resources\BikeModelVariants\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\BrandColor;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BikeModelVariantsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                ImageColumn::make('photo')
                    ->label(__('app.fleet.fields.photo'))
                    ->getStateUsing(static function (BikeModelVariant $record): ?string {
                        $path = $record->displayPhotoPath();

                        return $path === null ? null : tenant_asset($path);
                    })
                    ->imageSize(56)
                    ->extraImgAttributes(['style' => 'object-fit: contain;'])
                    ->alt(__('app.fleet.fields.photo')),
                TextColumn::make('bikeModel.name')
                    ->label(__('app.fleet.fields.model'))
                    ->searchable(),
                TextColumn::make('size')
                    ->label(__('app.fleet.fields.size'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('color_name')
                    ->label(__('app.fleet.fields.color_name'))
                    ->toggleable(),
                ColorColumn::make('color_value')
                    ->label(__('app.fleet.fields.color'))
                    ->getStateUsing(static fn (BikeModelVariant $record): ?string => $record->color_value === null
                        ? null
                        : BrandColor::swatch($record->color_value, '#000000')),
            ])
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with('bikeModel.bikeCategory'))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::BikeModelVariants,
            __('app.fleet.empty_states.bike_model_variants'),
        );
    }
}
