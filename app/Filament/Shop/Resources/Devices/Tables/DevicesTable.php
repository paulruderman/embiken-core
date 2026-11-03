<?php

namespace App\Filament\Shop\Resources\Devices\Tables;

use App\Actions\Devices\DeleteDeviceAction;
use App\Actions\Devices\PairDeviceAction;
use App\Enums\DeviceSurface;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.device_ui.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('surface')
                    ->label(__('app.device_ui.surface'))
                    ->badge()
                    ->formatStateUsing(fn (DeviceSurface $state): string => $state->getLabel())
                    ->color(fn (Device $record): string => $record->surface->getColor()),
                TextColumn::make('paired_at')
                    ->label(__('app.device_ui.pairing_status'))
                    ->badge()
                    ->state(fn (Device $record): string => $record->paired_at === null
                        ? __('app.device_ui.not_paired')
                        : __('app.device_ui.paired'))
                    ->color(fn (Device $record): string => $record->paired_at === null ? 'gray' : 'success'),
                TextColumn::make('pairedTerminalDevice.name')
                    ->label(__('app.device_ui.paired_terminal'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('display.name')
                    ->label(__('app.device_ui.display'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('pair')
                    ->label(__('app.device_ui.pair'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('code')
                            ->label(__('app.device_ui.pairing_code'))
                            ->helperText(__('app.device_ui.pairing_code_help'))
                            ->required()
                            ->maxLength(32)
                            ->autocomplete('off'),
                    ])
                    ->visible(fn (Device $record): bool => $record->paired_at === null)
                    ->action(function (Device $record, array $data): void {
                        PairDeviceAction::run($record, $data['code']);

                        Notification::make()
                            ->title(__('app.device_ui.paired'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->label(__('app.device_ui.delete'))
                    ->modalDescription(__('app.device_ui.delete_confirmation'))
                    ->action(fn (Device $record) => DeleteDeviceAction::run($record)),
            ])
            ->toolbarActions([]);
    }
}
