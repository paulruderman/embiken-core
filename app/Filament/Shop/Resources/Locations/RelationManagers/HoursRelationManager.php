<?php

namespace App\Filament\Shop\Resources\Locations\RelationManagers;

use App\Enums\Weekday;
use App\Filament\Shop\Support\ShopSetupReadiness;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HoursRelationManager extends RelationManager
{
    protected static string $relationship = 'hours';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('weekday')
                    ->options(Weekday::class)
                    ->required(),
                TextInput::make('opens_at')
                    ->required()
                    ->placeholder('09:00'),
                TextInput::make('closes_at')
                    ->required()
                    ->placeholder('17:00'),
                Toggle::make('closes_next_day')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('weekday')
            ->columns([
                TextColumn::make('weekday')
                    ->badge(),
                TextColumn::make('opens_at'),
                TextColumn::make('closes_at'),
                IconColumn::make('closes_next_day')
                    ->boolean(),
            ])
            ->emptyStateHeading('Opening hours are not set up')
            ->emptyStateDescription('Add the intervals when customers can start and finish rentals. Book cannot accept reservations until opening hours are configured.')
            ->emptyStateActions([
                CreateAction::make('createHourFromEmptyState')
                    ->label('Add opening hours')
                    ->successNotification(fn (Notification $notification): Notification => $this->addContinueSetupAction($notification)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->successNotification(fn (Notification $notification): Notification => $this->addContinueSetupAction($notification)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private function addContinueSetupAction(Notification $notification): Notification
    {
        $readiness = app(ShopSetupReadiness::class);

        $setupTarget = request()->query('setup_target');

        if (! in_array($setupTarget, [ShopSetupReadiness::LocationHours, ShopSetupReadiness::BookOffer], true)
            || ! $readiness->hasStep(ShopSetupReadiness::LocationHours)
            || ! $readiness->step(ShopSetupReadiness::LocationHours)['complete']) {
            return $notification;
        }

        if ($setupTarget === ShopSetupReadiness::BookOffer) {
            return $readiness->addContinueSetupAction($notification, setupTarget: $setupTarget);
        }

        return $readiness->addContinueSetupAction($notification, $readiness->step(ShopSetupReadiness::LocationHours)['module_id']);
    }
}
