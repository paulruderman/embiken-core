<?php

namespace App\Filament\Shop\Resources\ServiceRequests;

use App\Filament\Shop\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Shop\Resources\ServiceRequests\Pages\EditServiceRequest;
use App\Filament\Shop\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Shop\Resources\ServiceRequests\RelationManagers\EntriesRelationManager;
use App\Filament\Shop\Resources\ServiceRequests\Schemas\ServiceRequestForm;
use App\Filament\Shop\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use App\Models\ServiceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return ServiceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRequests::route('/'),
            'create' => CreateServiceRequest::route('/create'),
            'edit' => EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
