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
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;

class VariantsRelationManager extends RelationManager
{
    use Translatable;

    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MoneyInput::make('rate_minor_units', fn (): string => (string) $this->getOwnerRecord()->getAttribute('currency'))
                    ->label(__('app.rentals_ui.fields.rate'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('size')
            ->columns([
                TextColumn::make('bikeModel.name')
                    ->label(__('app.fleet.fields.model'))
                    ->state(fn (BikeModelVariant $record): ?string => $record->bikeModel?->getTranslation('name', $this->activeLocale)),
                TextColumn::make('size')->label(__('app.fleet.fields.size')),
                TextColumn::make('color_name')
                    ->label(__('app.fleet.fields.color_name'))
                    ->state(fn (BikeModelVariant $record): ?string => $record->getTranslation('color_name', $this->activeLocale))
                    ->placeholder('—'),
                TextColumn::make('rate_minor_units')
                    ->label(__('app.rentals_ui.fields.rate'))
                    ->formatStateUsing(function (?int $state): string {
                        try {
                            return Money::format(
                                $state ?? 0,
                                (string) $this->getOwnerRecord()->getAttribute('currency'),
                            );
                        } catch (InvalidArgumentException) {
                            return __('app.rentals_ui.errors.invalid_currency');
                        }
                    }),
            ])
            ->emptyStateHeading(__('app.rentals_ui.empty_states.package_variants'))
            ->emptyStateDescription(fn (): string => BikeModelVariant::query()->exists()
                ? __('app.rentals_ui.empty_states.add_package_variants')
                : __('app.rentals_ui.empty_states.create_variant_first'))
            ->emptyStateActions(BikeModelVariant::query()->exists()
                ? []
                : [
                    Action::make('createBikeModelVariant')
                        ->label(__('app.setup_steps.bike_model_variants.action'))
                        ->url(app(ShopSetupReadiness::class)->continuationUrl(ShopSetupReadiness::BookOffer)),
                ])
            ->headerActions([
                LocaleSwitcher::make(),
                Action::make('createBikeModelVariant')
                    ->label(__('app.rentals_ui.actions.add_variants_first'))
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
