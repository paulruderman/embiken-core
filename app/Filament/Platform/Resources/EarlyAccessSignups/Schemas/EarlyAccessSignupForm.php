<?php

namespace App\Filament\Platform\Resources\EarlyAccessSignups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EarlyAccessSignupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->label(__('app.early_access_admin.email'))->email()->required()->disabled(),
            TextInput::make('name')->label(__('app.early_access_admin.name'))->disabled(),
            TextInput::make('shop_name')->label(__('app.early_access_admin.shop_name'))->disabled(),
            Textarea::make('message')->label(__('app.early_access_admin.message'))->rows(4)->disabled()->columnSpanFull(),
            Select::make('status')
                ->label(__('app.early_access_admin.status'))
                ->options([
                    'new' => __('app.early_access_admin.new'),
                    'invited' => __('app.early_access_admin.invited'),
                    'closed' => __('app.early_access_admin.closed'),
                ])
                ->required(),
            Textarea::make('internal_notes')->label(__('app.early_access_admin.internal_notes'))->rows(5)->columnSpanFull(),
        ]);
    }
}
