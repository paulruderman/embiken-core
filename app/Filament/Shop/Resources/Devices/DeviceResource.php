<?php

namespace App\Filament\Shop\Resources\Devices;

use App\Filament\Shop\Resources\Devices\Pages\EditDevice;
use App\Filament\Shop\Resources\Devices\Pages\ListDevices;
use App\Filament\Shop\Resources\Devices\Schemas\DeviceForm;
use App\Filament\Shop\Resources\Devices\Tables\DevicesTable;
use App\Models\Device;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Shop settings';

    public static function getNavigationLabel(): string
    {
        return __('app.device_ui.navigation');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('location_id', Location::query()->value('id'));
    }

    public static function form(Schema $schema): Schema
    {
        return DeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'edit' => EditDevice::route('/{record}/edit'),
        ];
    }
}
