<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers;

use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Filament\Forms\Components\BikeModelVariantSelect;
use Embiken\BikeFleet\Filament\Resources\BikeModelVariants\BikeModelVariantResource;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Actions\AllocateLineAction;
use Embiken\BikeRentals\Actions\ReleaseLineAction;
use Embiken\BikeRentals\Enums\BikeReadinessIssue;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Models\BikeReservation;
use Embiken\BikeRentals\Models\Reservation;
use Embiken\BikeRentals\Services\Availability;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
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
            ->modifyQueryUsing(fn ($query) => $query->with([
                'product.bikeModel',
                'bike.situationReservation.customer',
            ]))
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
                    ->color(fn (BikeReservation $record): ?string => $record->bike?->bike_situation_state->getColor())
                    ->placeholder('—'),
                TextColumn::make('occupying_customer')
                    ->label(__('app.fleet.fields.occupying_reservation'))
                    ->getStateUsing(function (BikeReservation $record): ?string {
                        $bike = $record->bike;

                        if ($bike === null || $bike->bike_situation_state === BikeSituation::Home) {
                            return null;
                        }

                        $occupyingId = $bike->bike_situation_reservation_id;

                        if ($occupyingId === null) {
                            return __('app.location_ui.readiness_missing_reservation');
                        }

                        if ($occupyingId === $record->reservation_id) {
                            return null;
                        }

                        $occupying = $bike->getRelationValue('situationReservation');

                        if (! $occupying instanceof Reservation) {
                            return '#'.$occupyingId;
                        }

                        return $occupying->public_id.' · '.($occupying->customer->name ?: $occupying->public_id);
                    }),
                TextColumn::make('readiness')
                    ->label(__('app.location_ui.readiness'))
                    ->badge()
                    ->getStateUsing(fn (BikeReservation $record): ?string => self::readinessLabel($record))
                    ->color(function (BikeReservation $record): string {
                        $issues = app(Availability::class)->readinessIssuesForLine($record);

                        if ($issues === []) {
                            return 'green';
                        }

                        return BikeReadinessIssue::from($issues[0])->getColor();
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (BikeReservation $record): string => $record->status->getColor()),
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

    private static function readinessLabel(BikeReservation $record): ?string
    {
        if ($record->status !== BikeReservationStatus::Assigned) {
            return null;
        }

        $record->loadMissing(['reservation.location', 'bike']);
        $bike = $record->bike;
        $activePreparation = $bike !== null
            && $bike->bike_situation_reservation_id === $record->reservation_id
            && in_array($bike->bike_situation_state, [BikeSituation::Prepping, BikeSituation::Staged], true);
        $windowMinutes = $record->reservation->location->reservation_readiness_window_minutes;
        $startsAt = $record->reservation->starts_at;
        $now = now();
        $withinWindow = $windowMinutes > 0
            && $startsAt->greaterThanOrEqualTo($now)
            && $startsAt->lessThanOrEqualTo($now->copy()->addMinutes($windowMinutes));

        if (! $activePreparation && ! $withinWindow) {
            return null;
        }

        $issues = app(Availability::class)->readinessIssuesForLine($record);

        if ($issues === []) {
            return $record->bike_id === null
                ? null
                : (string) __('app.location_ui.readiness_ready');
        }

        return implode(' · ', array_map(
            fn (string $issue): string => BikeReadinessIssue::from($issue)->getLabel(),
            $issues,
        ));
    }
}
