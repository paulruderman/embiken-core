<?php

namespace Embiken\BikeRentals\Filament\Resources\RentalPackages\RelationManagers;

use App\Filament\Shop\Support\ShopSetupReadiness;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('rate_cents')
                    ->numeric()
                    ->required()
                    ->minValue(0),
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
                TextColumn::make('rate_cents')
                    ->label('Rate (cents)'),
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
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('rate_cents')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
