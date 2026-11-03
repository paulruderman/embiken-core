<?php

namespace App\Filament\Shop\Resources\Devices\Tables;

use App\Actions\Devices\DeleteDeviceAction;
use App\Actions\Devices\PairDeviceAction;
use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Display;
use App\Support\DevicePairingCode;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
            ->headerActions([
                Action::make('pairDevice')
                    ->label(__('app.device_ui.pair'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('code')
                            ->label(__('app.device_ui.pairing_code'))
                            ->helperText(__('app.device_ui.pairing_code_help'))
                            ->required()
                            ->maxLength(8)
                            ->autocomplete('off')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $device = filled($state) ? DevicePairingCode::findPending($state) : null;
                                $set('device_surface', $device?->surface->value);
                            }),
                        Hidden::make('device_surface')->dehydrated(false),
                        Select::make('paired_terminal_device_id')
                            ->label(__('app.device_ui.paired_terminal'))
                            ->helperText(__('app.device_ui.paired_terminal_help'))
                            ->options(fn (): array => Device::query()
                                ->where('surface', DeviceSurface::Terminal->value)
                                ->whereNotNull('paired_at')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required(fn (Get $get): bool => $get('device_surface') === DeviceSurface::CustomerDisplay->value)
                            ->visible(fn (Get $get): bool => $get('device_surface') === DeviceSurface::CustomerDisplay->value),
                        Select::make('display_id')
                            ->label(__('app.device_ui.display'))
                            ->helperText(__('app.device_ui.display_help'))
                            ->options(fn (): array => Display::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->visible(fn (Get $get): bool => $get('device_surface') === DeviceSurface::WallDisplay->value),
                    ])
                    ->action(function (array $data): void {
                        $device = PairDeviceAction::run(
                            $data['code'],
                            $data['paired_terminal_device_id'] ?? null,
                            $data['display_id'] ?? null,
                        );

                        Notification::make()
                            ->title(__('app.device_ui.paired'))
                            ->body($device->name)
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label(__('app.device_ui.delete'))
                    ->modalDescription(__('app.device_ui.delete_confirmation'))
                    ->action(fn (Device $record) => DeleteDeviceAction::run($record)),
            ])
            ->toolbarActions([]);
    }
}
