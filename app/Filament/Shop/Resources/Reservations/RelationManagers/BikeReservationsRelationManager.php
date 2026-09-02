<?php

namespace App\Filament\Shop\Resources\Reservations\RelationManagers;

use App\Actions\Reservations\AllocateLineAction;
use App\Actions\Reservations\ReleaseLineAction;
use App\Enums\BikeSituation;
use App\Models\Bike;
use App\Models\BikeModelVariant;
use App\Models\BikeReservation;
use App\Models\Reservation;
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
                Select::make('product_id')
                    ->label('Variant')
                    ->options(fn (): array => BikeModelVariant::query()
                        ->with('bikeModel')
                        ->get()
                        ->mapWithKeys(fn (BikeModelVariant $variant): array => [
                            $variant->id => $variant->bikeModel->name.' '.$variant->size,
                        ])
                        ->all())
                    ->required()
                    ->searchable(),
                Select::make('bike_id')
                    ->label('Bike')
                    ->options(fn (): array => Bike::query()->orderBy('bid')->pluck('bid', 'id')->all())
                    ->searchable(),
                TextInput::make('rider_name'),
                TextInput::make('rider_height_cm')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with(['product.bikeModel', 'bike.occupyingReservation.customer']))
            ->columns([
                TextColumn::make('product.bikeModel.name')
                    ->label('Model'),
                TextColumn::make('product.size')
                    ->label('Size'),
                TextColumn::make('bike.bid')
                    ->label('Bid')
                    ->placeholder('unassigned'),
                TextColumn::make('bike.bike_situation_state')
                    ->label('Situation')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('occupying_customer')
                    ->label('Occupying reservation')
                    ->getStateUsing(function (BikeReservation $record): string {
                        $occupying = $record->bike?->occupyingReservation;

                        if ($occupying === null) {
                            return $record->bike?->bike_situation_state === BikeSituation::Home ? '—' : '—';
                        }

                        return '#'.$occupying->id.' '.$occupying->customer?->name;
                    }),
                IconColumn::make('wrong_ticket')
                    ->label('Wrong ticket')
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
            ->headerActions([
                CreateAction::make()
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
