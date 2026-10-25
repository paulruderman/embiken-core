<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\RelationManagers;

use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use Embiken\BikeFleet\Models\Part;
use Embiken\BikeFleet\Models\ServiceEntryPart;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->label(__('app.fleet.fields.staff'))
                    ->relationship('staff', 'name')
                    ->default(function (): ?int {
                        $id = Auth::guard('staff')->id();

                        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int) $id : null);
                    }),
                Textarea::make('notes')->label(__('app.fleet.fields.notes')),
                TextInput::make('labor_minutes')
                    ->label(__('app.fleet.fields.labor_minutes'))
                    ->numeric()
                    ->minValue(0),
                DateTimePicker::make('work_started_at')
                    ->label(__('app.fleet.fields.work_started_at'))
                    ->native(false)
                    ->seconds(false)
                    ->timezone(LocalizedDateTimeColumn::shopTimezone())
                    ->displayFormat(__('app.datetime_ui.input_format')),
                DateTimePicker::make('work_completed_at')
                    ->label(__('app.fleet.fields.work_completed_at'))
                    ->native(false)
                    ->seconds(false)
                    ->timezone(LocalizedDateTimeColumn::shopTimezone())
                    ->displayFormat(__('app.datetime_ui.input_format')),
                Repeater::make('partUsages')
                    ->label('Parts used')
                    ->relationship()
                    ->schema([
                        Select::make('part_id')
                            ->label('Part')
                            ->relationship(name: 'part', titleAttribute: 'name')
                            ->searchable(['name', 'sku'])
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->nullable()
                                    ->maxLength(100),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $name = trim((string) ($data['name'] ?? ''));
                                $existingPart = Part::query()
                                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                                    ->first();

                                if ($existingPart instanceof Part) {
                                    return (int) $existingPart->getKey();
                                }

                                $sku = $data['sku'] ?? null;
                                $part = Part::query()->create([
                                    'name' => $name,
                                    'sku' => is_string($sku) && filled($sku) ? trim($sku) : null,
                                ]);

                                return (int) $part->getKey();
                            })
                            ->helperText('Create a new shop part if it is not listed.'),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->default(1)
                            ->required(),
                        TextInput::make('unit')
                            ->default('each')
                            ->required()
                            ->maxLength(32),
                    ])
                    ->columns(3)
                    ->addActionLabel('Add part'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notes')
            ->columns([
                TextColumn::make('staff.name')->label(__('app.fleet.fields.staff')),
                TextColumn::make('notes')
                    ->label(__('app.fleet.fields.notes'))
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state !== null && mb_strlen($state) > 40 ? $state : null),
                TextColumn::make('labor_minutes')->label(__('app.fleet.fields.labor_minutes')),
                TextColumn::make('partUsages.part_name')
                    ->label('Parts used')
                    ->formatStateUsing(function (string $state, ServiceEntryPart $relatedRecord): string {
                        $quantity = rtrim(rtrim($relatedRecord->quantity, '0'), '.');

                        return trim("{$quantity} {$relatedRecord->unit} {$state}");
                    })
                    ->listWithLineBreaks(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
