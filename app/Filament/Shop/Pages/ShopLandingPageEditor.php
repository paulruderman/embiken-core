<?php

namespace App\Filament\Shop\Pages;

use App\Actions\Shop\SaveShopLandingPageAction;
use App\Enums\StaffRole;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\ShopCalloutBlock;
use App\Models\ShopLandingPage;
use App\Models\Staff;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use LogicException;

class ShopLandingPageEditor extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop settings';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.shop.pages.shop-landing-page-editor';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $staff = Filament::auth()->user();

        return $staff instanceof Staff && $staff->role === StaffRole::Manager;
    }

    public function mount(): void
    {
        $page = ShopLandingPage::current();

        $this->landingPageForm()->fill($page?->attributesToArray() ?? [
            'layout' => 'hero',
            'layout_data' => [
                'eyebrow' => '',
                'headline' => '',
                'description' => '',
                'button_label' => 'Book a bike',
            ],
            'theme' => 'light',
            'custom_content' => null,
            'booking_notices' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Home page')
                    ->description('Choose a layout, then fill in the fields for its slots.')
                    ->columns(2)
                    ->schema([
                        Select::make('layout')
                            ->options(['hero' => 'Hero', 'split' => 'Split', 'story' => 'Story'])
                            ->default('hero')
                            ->required()
                            ->live(),
                        Select::make('theme')
                            ->label('Page style')
                            ->options(['light' => 'Light', 'warm' => 'Warm', 'dark' => 'Dark'])
                            ->default('light')
                            ->required(),
                        TextInput::make('layout_data.eyebrow')
                            ->label('Small heading')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => in_array($get('layout'), ['split', 'story'], true)),
                        TextInput::make('layout_data.headline')
                            ->label('Main heading')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        Textarea::make('layout_data.description')
                            ->label('Introduction')
                            ->rows(4)
                            ->required()
                            ->maxLength(1200)
                            ->columnSpanFull(),
                        TextInput::make('layout_data.button_label')
                            ->label('Booking button')
                            ->default('Book a bike')
                            ->required()
                            ->maxLength(80),
                        self::imageUpload()
                            ->visible(fn (Get $get): bool => in_array($get('layout'), ['hero', 'split', 'story'], true)),
                        Select::make('layout_data.image_position')
                            ->label('Image position')
                            ->options(['left' => 'Left', 'right' => 'Right'])
                            ->default('right')
                            ->required()
                            ->visible(fn (Get $get): bool => $get('layout') === 'split'),
                        Textarea::make('layout_data.story_quote')
                            ->label('Quote')
                            ->rows(3)
                            ->maxLength(500)
                            ->visible(fn (Get $get): bool => $get('layout') === 'story'),
                        TextInput::make('layout_data.story_byline')
                            ->label('Quote attribution')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => $get('layout') === 'story'),
                    ]),
                Section::make('Extra page sections')
                    ->description('Add flexible sections below the selected layout with the rich editor blocks.')
                    ->schema([
                        RichEditor::make('custom_content')
                            ->label('Page sections')
                            ->customBlocks([ShopCalloutBlock::class])
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Messages on the booking screen')
                    ->description('Show up to five short notices at the top of /book.')
                    ->schema([
                        Repeater::make('booking_notices')
                            ->label('Booking messages')
                            ->schema([
                                Textarea::make('message')
                                    ->required()
                                    ->rows(2)
                                    ->maxLength(240)
                                    ->columnSpanFull(),
                                Select::make('tone')
                                    ->options(['info' => 'Information', 'success' => 'Good news', 'warning' => 'Heads up'])
                                    ->default('info')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(5)
                            ->reorderable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $page = $this->landingPageForm()->getState();

        app(SaveShopLandingPageAction::class)($page);

        Notification::make()
            ->title('Home page saved')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    private function landingPageForm(): Schema
    {
        return $this->getSchema('form') ?? throw new LogicException('The shop landing page schema is unavailable.');
    }

    private static function imageUpload(): FileUpload
    {
        return FileUpload::make('layout_data.image_path')
            ->label('Page image')
            ->image()
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->maxSize(5120)
            ->disk('public')
            ->directory('landing-pages')
            ->visibility('public')
            ->getUploadedFileUsing(static function (FileUpload $component, string $file, string|array|null $storedFileNames): array {
                $disk = $component->getDisk();

                return [
                    'name' => (is_array($storedFileNames) ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                    'size' => $disk->size($file),
                    'type' => $disk->mimeType($file),
                    'url' => tenant_asset($file),
                ];
            });
    }

    public function getTitle(): string|Htmlable
    {
        return 'Shop home page';
    }
}
