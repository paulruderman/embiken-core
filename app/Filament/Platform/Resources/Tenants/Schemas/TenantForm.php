<?php

namespace App\Filament\Platform\Resources\Tenants\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('domain')
                    ->label('Hostname')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('timezone')
                    ->required()
                    ->default('America/New_York')
                    ->visibleOn('create'),
                TextInput::make('currency')
                    ->required()
                    ->default('usd')
                    ->length(3)
                    ->visibleOn('create'),
                TextInput::make('manager_name')
                    ->required()
                    ->visibleOn('create'),
                TextInput::make('manager_email')
                    ->email()
                    ->required()
                    ->visibleOn('create'),
                Placeholder::make('express_status')
                    ->label('Express')
                    ->visibleOn('edit')
                    ->content(function (?Tenant $record): string {
                        if ($record === null) {
                            return '—';
                        }

                        $account = $record->stripe_connect_account_id ?? 'none';
                        $charges = $record->charges_enabled ? 'charges enabled' : 'cannot charge';

                        return "{$account} ({$charges})";
                    }),
            ]);
    }
}
