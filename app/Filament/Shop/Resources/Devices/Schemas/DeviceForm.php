<?php

namespace App\Filament\Shop\Resources\Devices\Schemas;

use App\Enums\DeviceSurface;
use App\Models\Device;
use App\Models\Display;
use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class DeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.device_ui.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('surface')
                    ->label(__('app.device_ui.surface'))
                    ->options(DeviceSurface::class)
                    ->required()
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('paired_terminal_device_id', null);
                        $set('display_id', null);
                    }),
                Select::make('paired_terminal_device_id')
                    ->label(__('app.device_ui.paired_terminal'))
                    ->helperText(__('app.device_ui.paired_terminal_help'))
                    ->options(fn (): array => Device::query()
                        ->where('location_id', Location::query()->value('id'))
                        ->where('surface', DeviceSurface::Terminal->value)
                        ->whereNotNull('paired_at')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->rules([Rule::exists('devices', 'id')
                        ->where('location_id', Location::query()->value('id'))
                        ->where('surface', DeviceSurface::Terminal->value)
                        ->whereNotNull('paired_at')])
                    ->required(fn (Get $get): bool => $get('surface') === DeviceSurface::CustomerDisplay->value)
                    ->visible(fn (Get $get): bool => $get('surface') === DeviceSurface::CustomerDisplay->value)
                    ->dehydratedWhenHidden(false),
                Select::make('display_id')
                    ->label(__('app.device_ui.display'))
                    ->helperText(__('app.device_ui.display_help'))
                    ->options(fn (): array => Display::query()
                        ->where('location_id', Location::query()->value('id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->rules([Rule::exists('displays', 'id')->where('location_id', Location::query()->value('id'))])
                    ->nullable()
                    ->visible(fn (Get $get): bool => $get('surface') === DeviceSurface::WallDisplay->value)
                    ->dehydratedWhenHidden(false),
            ]);
    }
}
