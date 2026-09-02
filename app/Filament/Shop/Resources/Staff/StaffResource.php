<?php

namespace App\Filament\Shop\Resources\Staff;

use App\Filament\Shop\Resources\Staff\Pages\CreateStaff;
use App\Filament\Shop\Resources\Staff\Pages\EditStaff;
use App\Filament\Shop\Resources\Staff\Pages\ListStaff;
use App\Filament\Shop\Resources\Staff\Schemas\StaffForm;
use App\Filament\Shop\Resources\Staff\Tables\StaffTable;
use App\Models\Staff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Staff && ! $record->is_platform_manager;
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Staff && ! $record->is_platform_manager;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
