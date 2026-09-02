<?php

namespace App\Filament\Shop\Resources\ServiceRequests\Schemas;

use App\Enums\ServiceStage;
use App\Models\Bike;
use App\Models\Staff;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ServiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->default(fn (): ?int => Auth::guard('staff')->id()),
                Select::make('assigned_to')
                    ->options(fn (): array => Staff::query()->orderBy('name')->pluck('name', 'id')->all()),
            ]);
    }
}
