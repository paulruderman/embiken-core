<?php

namespace App\Filament\Shop\Resources\Reservations\Pages;

use App\Actions\Reservations\CancelAction;
use App\Actions\Reservations\ExtendAction;
use App\Actions\Reservations\RefundAction;
use App\Actions\Reservations\SetReservationOwedAction;
use App\Actions\Reservations\SetReservationStageAction;
use App\Enums\BikeReservationStatus;
use App\Enums\ReservationStage;
use App\Exceptions\OutBikesNeedConfirmation;
use App\Filament\Shop\Resources\Reservations\ReservationResource;
use App\Models\Reservation;
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
                        ->options([
                            ReservationStage::Provisional->value => 'Provisional',
                            ReservationStage::Confirmed->value => 'Confirmed',
                            ReservationStage::Cancelled->value => 'Cancelled',
                        ])
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
                    TextInput::make('owed')
                        ->numeric()
                        ->required()
                        ->default(fn (): int => $reservation->owed),
                ])
                ->action(fn (array $data) => app(SetReservationOwedAction::class)($reservation, (int) $data['owed'])),
            Action::make('extend')
                ->schema([
                    DateTimePicker::make('ends_at')
                        ->required()
                        ->default(fn (): mixed => $reservation->ends_at),
                    TextInput::make('owed')
                        ->numeric()
                        ->helperText('Leave blank to keep owed.'),
                ])
                ->action(function (array $data) use ($reservation): void {
                    app(ExtendAction::class)(
                        $reservation,
                        Carbon::parse($data['ends_at']),
                        isset($data['owed']) && $data['owed'] !== '' && $data['owed'] !== null
                            ? (int) $data['owed']
                            : null,
                    );
                }),
            Action::make('refund')
                ->schema([
                    TextInput::make('amount_cents')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    TextInput::make('note'),
                ])
                ->action(fn (array $data) => app(RefundAction::class)(
                    $reservation,
                    (int) $data['amount_cents'],
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
