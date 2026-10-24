<?php

namespace Embiken\BikeFleet\Filament\Resources\Bikes\Schemas;

use App\Models\Location;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Models\Bike;
use Embiken\BikeFleet\Models\BikeModelVariant;
use Embiken\BikeFleet\Models\BikeSpecification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class BikeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make('Add one physical bike to your fleet. Choose the model variant that identifies this bike’s model and size.')
                    ->size('sm')
                    ->columnSpanFull(),
                Hidden::make('location_id')
                    ->default(fn (): ?int => Location::query()->value('id')),
                Select::make('bike_model_variant_id')
                    ->label('Variant')
                    ->options(fn (): array => BikeModelVariant::query()
                        ->with('bikeModel')
                        ->get()
                        ->mapWithKeys(fn (BikeModelVariant $variant): array => [
                            $variant->id => collect([
                                $variant->bikeModel->manufacturer,
                                $variant->bikeModel->name,
                                $variant->size,
                                $variant->color_name,
                            ])->filter()->implode(' '),
                        ])
                        ->all())
                    ->helperText(Bike::SETUP_DESCRIPTION)
                    ->required()
                    ->searchable(),
                TextInput::make('bid')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('serial_number')
                    ->label('Manufacturer serial number')
                    ->maxLength(255),
                Toggle::make('in_service')
                    ->default(true),
                Toggle::make('self_bookable')
                    ->default(true),
                Placeholder::make('bike_situation_state')
                    ->label('Situation')
                    ->content(fn (?Bike $record): string => ($record->bike_situation_state ?? BikeSituation::Home)->getLabel()),
                FileUpload::make('photo')
                    ->image()
                    ->directory('bikes'),
                Repeater::make('specifications')
                    ->relationship()
                    ->label('Specifications')
                    ->schema([
                        Select::make('name')
                            ->label('Specification')
                            ->options(fn (): array => BikeSpecification::query()
                                ->select('name')
                                ->distinct()
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => self::existingOrNewSpecificationName($data['name']))
                            ->required(),
                        TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add specification')
                    ->columnSpanFull(),
                Textarea::make('damage_notes'),
            ]);
    }

    private static function existingOrNewSpecificationName(string $name): string
    {
        $name = trim($name);

        return BikeSpecification::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('name') ?? $name;
    }
}
