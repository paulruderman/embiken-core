<?php

namespace App\Filament\Platform\Resources\EarlyAccessSignups;

use App\Filament\Platform\Resources\EarlyAccessSignups\Pages\EditEarlyAccessSignup;
use App\Filament\Platform\Resources\EarlyAccessSignups\Pages\ListEarlyAccessSignups;
use App\Filament\Platform\Resources\EarlyAccessSignups\Schemas\EarlyAccessSignupForm;
use App\Filament\Platform\Resources\EarlyAccessSignups\Tables\EarlyAccessSignupsTable;
use App\Models\EarlyAccessSignup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EarlyAccessSignupResource extends Resource
{
    protected static ?string $model = EarlyAccessSignup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('app.early_access_admin.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('app.early_access_admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.early_access_admin.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return EarlyAccessSignupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EarlyAccessSignupsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEarlyAccessSignups::route('/'),
            'edit' => EditEarlyAccessSignup::route('/{record}/edit'),
        ];
    }
}
