<?php

namespace App\Filament\Platform\Resources\Domains\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),
                TextInput::make('domain')
                    ->label('Hostname')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
            ]);
    }
}
