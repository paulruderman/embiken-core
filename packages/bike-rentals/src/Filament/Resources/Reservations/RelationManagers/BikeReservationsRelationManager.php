<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers;

use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Filament\Forms\Components\BikeModelVariantSelect;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Actions\ReleaseLineAction;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BikeReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'bikeReservations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                BikeModelVariantSelect::make('product_id')
                    ->label(__('app.rentals_ui.fields.variant'))
                    ->required(),
                Select::make('bike_id')
                    ->label(__('app.rentals_ui.fields.bike'))
                    ->options(fn (): array => Bike::query()->orderBy('bid')->pluck('bid', 'id')->all())
                    ->searchable(),
                TextInput::make('rider_name')->label(__('app.rentals_ui.fields.rider_name')),
                TextInput::make('rider_height_cm')
                    ->label(__('app.rentals_ui.fields.rider_height'))
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with(['product.bikeModel', 'bike']))
            ->columns([
                TextColumn::make('product.bikeModel.name')
                    ->label(__('app.fleet.fields.model')),
                TextColumn::make('product.size')
                    ->label(__('app.fleet.fields.size')),
                TextColumn::make('product.color_name')
                    ->label(__('app.fleet.fields.color_name'))
                    ->placeholder('—'),
                TextColumn::make('bike.bid')
                    ->label(__('app.fleet.fields.bid'))
                    ->placeholder(__('app.rentals_ui.table.unassigned')),
                TextColumn::make('bike.bike_situation_state')
                    ->label(__('app.fleet.fields.situation'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('occupying_customer')
                    ->label(__('app.fleet.fields.occupying_reservation'))
                    ->getStateUsing(function (BikeReservation $record): string {
                        $occupying = $record->bike?->bike_situation_reservation_id
                            ? Reservation::query()->with('customer')->find($record->bike->bike_situation_reservation_id)
                            : null;

                        if ($occupying === null) {
                            return $record->bike?->bike_situation_state === BikeSituation::Home ? '—' : '—';
                        }

                        return '#'.$occupying->id.' '.$occupying->customer?->name;
                    }),
                IconColumn::make('wrong_ticket')
                    ->label(__('app.rentals_ui.fields.wrong_reservation'))
                    ->boolean()
                    ->getStateUsing(function (BikeReservation $record): bool {
                        $occupyingId = $record->bike?->bike_situation_reservation_id;

                        return $occupyingId !== null && $occupyingId !== $record->reservation_id;
                    }),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('rider_name')
                    ->placeholder('—'),
            ])
            ->emptyStateHeading(__('app.rentals_ui.empty_states.reservation_lines'))
            ->emptyStateDescription(fn (): string => BikeModelVariant::query()->exists()
                ? __('app.rentals_ui.empty_states.add_variant_to_reservation')
                : __('app.rentals_ui.empty_states.create_variant_for_reservation'))
            ->emptyStateActions([
                Action::make('createBikeModelVariantFromEmptyState')
                    ->label(__('app.setup_steps.bike_model_variants.action'))
                    ->url(BikeModelVariantResource::getUrl('create'))
                    ->visible(fn (): bool => ! BikeModelVariant::query()->exists()),
            ])
            ->headerActions([
                Action::make('createBikeModelVariant')
                    ->label(__('app.rentals_ui.actions.add_variants_first'))
                    ->url(BikeModelVariantResource::getUrl('create'))
                    ->visible(fn (): bool => ! BikeModelVariant::query()->exists()),
                CreateAction::make()
                    ->visible(fn (): bool => BikeModelVariant::query()->exists())
                    ->using(function (array $data, RelationManager $livewire): BikeReservation {
                        /** @var Reservation $reservation */
                        $reservation = $livewire->getOwnerRecord();

                        return app(AllocateLineAction::class)(
                            $reservation,
                            BikeModelVariant::query()->findOrFail($data['product_id']),
                            isset($data['bike_id']) ? Bike::query()->find($data['bike_id']) : null,
                            $data['rider_name'] ?? null,
                            isset($data['rider_height_cm']) ? (int) $data['rider_height_cm'] : null,
                        );
                    }),
            ])
            ->recordActions([
                Action::make('release')
                    ->requiresConfirmation()
                    ->action(fn (BikeReservation $record) => app(ReleaseLineAction::class)($record)),
            ]);
    }
}
