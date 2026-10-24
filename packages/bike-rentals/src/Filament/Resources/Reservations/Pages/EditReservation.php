<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations\Pages;

use Embiken\BikeRentals\Actions\CancelAction;
use Embiken\BikeRentals\Actions\ExtendAction;
use Embiken\BikeRentals\Actions\RefundAction;
use Embiken\BikeRentals\Actions\SetReservationOwedAction;
use Embiken\BikeRentals\Actions\SetReservationStageAction;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Exceptions\OutBikesNeedConfirmation;
use Embiken\BikeRentals\Filament\Resources\Reservations\ReservationResource;
use Embiken\BikeRentals\Filament\Support\MoneyInput;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->getRecord();

        return [
            ViewAction::make(),
            Action::make('setStage')
                ->schema([
                    Select::make('stage')
                        ->options(collect([
                            ReservationStage::Provisional,
                            ReservationStage::Confirmed,
                            ReservationStage::Cancelled,
                        ])->mapWithKeys(fn (ReservationStage $stage): array => [
                            $stage->value => $stage->getLabel(),
                        ])->all())
                        ->required(),
                    Toggle::make('confirm_out_bikes_in_shop')
                        ->visible(fn (): bool => $reservation->bikeReservations->contains(
                            fn ($line): bool => $line->status === BikeReservationStatus::Out,
                        )),
                ])
                ->action(function (array $data) use ($reservation): void {
                    try {
                        app(SetReservationStageAction::class)(
                            $reservation,
                            ReservationStage::from($data['stage']),
                            (bool) ($data['confirm_out_bikes_in_shop'] ?? false),
                        );
                    } catch (OutBikesNeedConfirmation $exception) {
                        Notification::make()
                            ->danger()
                            ->title($exception->getMessage())
                            ->send();
                    }
                }),
            Action::make('setOwed')
                ->schema([
                    MoneyInput::make('owed', fn (): string => $reservation->currency)
                        ->required()
                        ->default(fn (): int => $reservation->owed),
                ])
                ->action(fn (array $data) => app(SetReservationOwedAction::class)($reservation, (int) $data['owed'])),
            Action::make('extend')
                ->schema([
                    DateTimePicker::make('ends_at')
                        ->required()
                        ->default(fn (): mixed => $reservation->ends_at),
                    MoneyInput::make('owed', fn (): string => $reservation->currency)
                        ->helperText('Leave blank to keep owed.'),
                ])
                ->action(function (array $data) use ($reservation): void {
                    app(ExtendAction::class)(
                        $reservation,
                        Carbon::parse($data['ends_at']),
                        isset($data['owed']) && $data['owed'] !== ''
                            ? (int) $data['owed']
                            : null,
                    );
                }),
            Action::make('refund')
                ->schema([
                    MoneyInput::make('amount_minor_units', fn (): string => $reservation->currency, minimumMinorUnits: 1)
                        ->required(),
                    TextInput::make('note'),
                ])
                ->action(fn (array $data) => app(RefundAction::class)(
                    $reservation,
                    (int) $data['amount_minor_units'],
                    $data['note'] ?? null,
                )),
            Action::make('cancel')
                ->color('danger')
                ->schema([
                    Toggle::make('confirm_out_bikes_in_shop')
                        ->label('Out bikes are in the shop'),
                ])
                ->action(function (array $data) use ($reservation): void {
                    try {
                        app(CancelAction::class)($reservation, (bool) ($data['confirm_out_bikes_in_shop'] ?? false));
                    } catch (OutBikesNeedConfirmation $exception) {
                        Notification::make()
                            ->danger()
                            ->title($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
