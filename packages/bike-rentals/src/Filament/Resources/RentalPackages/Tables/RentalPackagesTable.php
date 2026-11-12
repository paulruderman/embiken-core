<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\Tables;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RentalPackagesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.rentals_ui.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tagline')
                    ->label(__('app.rentals_ui.fields.tagline'))
                    ->toggleable(),
                TextColumn::make('meter')
                    ->label(__('app.rentals_ui.fields.meter'))
                    ->badge(),
                TextColumn::make('confirm_threshold')
                    ->label(__('app.rentals_ui.fields.confirm_threshold'))
                    ->badge(),
                IconColumn::make('book_visible')
                    ->label(__('app.rentals_ui.fields.book_visible'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('app.rentals_ui.fields.sort_order'))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::RentalPackages,
            __('app.rentals_ui.empty_states.rental_packages'),
        );
    }
}
