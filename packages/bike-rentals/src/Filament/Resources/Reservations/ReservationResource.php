<?php

namespace Embiken\BikeRentals\Filament\Resources\Reservations;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\EditReservation;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\ListReservations;
use Embiken\BikeRentals\Filament\Resources\Reservations\Pages\ViewReservation;
use Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers\BikeReservationsRelationManager;
use Embiken\BikeRentals\Filament\Resources\Reservations\RelationManagers\TransactionsRelationManager;
use Embiken\BikeRentals\Filament\Resources\Reservations\Schemas\ReservationForm;
use Embiken\BikeRentals\Filament\Resources\Reservations\Schemas\ReservationInfolist;
use Embiken\BikeRentals\Filament\Resources\Reservations\Tables\ReservationsTable;
use Embiken\BikeRentals\Models\Reservation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReservationResource extends Resource
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/bike-rentals';
    }

    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReservationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BikeReservationsRelationManager::class,
            TransactionsRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'view' => ViewReservation::route('/{record}'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}
