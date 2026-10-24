<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\RelationManagers;

use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Support\Money;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeRentals\Filament\Support\MoneyInput;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MoneyInput::make('rate_minor_units', fn (): string => (string) $this->getOwnerRecord()->getAttribute('currency'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('size')
            ->columns([
                TextColumn::make('bikeModel.name')
                    ->label('Model'),
                TextColumn::make('size'),
                TextColumn::make('color_name')
                    ->label('Color')
                    ->placeholder('—'),
                TextColumn::make('rate_minor_units')
                    ->label('Rate')
                    ->formatStateUsing(function (?int $state): string {
                        try {
                            return Money::format(
                                $state ?? 0,
                                (string) $this->getOwnerRecord()->getAttribute('currency'),
                            );
                        } catch (InvalidArgumentException) {
                            return 'Invalid currency';
                        }
                    }),
            ])
            ->emptyStateHeading('No variants in this package yet')
            ->emptyStateDescription(fn (): string => BikeModelVariant::query()->exists()
                ? 'Attach variants and set each rate to define which bikes this package offers.'
                : 'Create a bike model variant first. Then return here to add it to this package and set its rate.')
            ->emptyStateActions(BikeModelVariant::query()->exists()
                ? []
                : [
                    Action::make('createBikeModelVariant')
                        ->label('Create a bike model variant')
                        ->url(app(ShopSetupReadiness::class)->continuationUrl(ShopSetupReadiness::BookOffer)),
                ])
            ->headerActions([
                Action::make('createBikeModelVariant')
                    ->label('Add bike model variants first')
                    ->url(app(ShopSetupReadiness::class)->continuationUrl(ShopSetupReadiness::BookOffer))
                    ->visible(fn (): bool => ! BikeModelVariant::query()->exists()),
                AttachAction::make()
                    ->visible(fn (): bool => BikeModelVariant::query()->exists())
                    ->preloadRecordSelect()
                    ->successNotification(function (Notification $notification): Notification {
                        $readiness = app(ShopSetupReadiness::class);

                        if (request()->query('setup_target') !== ShopSetupReadiness::BookOffer
                            || ! $readiness->hasStep(ShopSetupReadiness::BookOffer)
                            || ! $readiness->step(ShopSetupReadiness::BookOffer)['complete']) {
                            return $notification;
                        }

                        return $readiness->addContinueSetupAction($notification, $readiness->step(ShopSetupReadiness::BookOffer)['module_id']);
                    })
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        MoneyInput::make('rate_minor_units', fn (): string => (string) $this->getOwnerRecord()->getAttribute('currency'))
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
