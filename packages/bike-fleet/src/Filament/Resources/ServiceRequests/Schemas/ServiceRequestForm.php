<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Schemas;

use App\Models\Staff;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\ServiceRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ServiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(ServiceRequest::SETUP_DESCRIPTION)
                    ->size('sm')
                    ->columnSpanFull(),
                Select::make('bike_id')
                    ->label('Bike')
                    ->options(fn (): array => Bike::query()->orderBy('bid')->pluck('bid', 'id')->all())
                    ->required()
                    ->searchable(),
                Textarea::make('description')
                    ->required(),
                Select::make('stage')
                    ->options(ServiceStage::class)
                    ->default(ServiceStage::Open)
                    ->required(),
                Toggle::make('blocks_usage')
                    ->default(true),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Select::make('created_by')
                    ->options(fn (): array => Staff::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->default(function (): ?int {
                        $id = Auth::guard('staff')->id();

                        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int) $id : null);
                    }),
                Select::make('assigned_to')
                    ->options(fn (): array => Staff::query()
                        ->where('is_platform_manager', false)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
            ]);
    }
}
