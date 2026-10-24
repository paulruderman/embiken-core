<?php

namespace App\Filament\Shop\Pages;

use App\Actions\Shop\SaveShopLandingPageAction;
use App\Enums\StaffRole;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\ShopCalloutBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\ShopFeatureBlock;
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
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
                'shared' => ['image_path' => null, 'image_position' => 'right'],
                'en' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Book a bike'],
                'es' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Reservar una bicicleta'],
                'fr' => ['eyebrow' => '', 'headline' => '', 'description' => '', 'button_label' => 'Réserver un vélo'],
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
                Section::make(__('app.shop_home.home_page'))
                    ->description(__('app.shop_home.choose_layout'))
                    ->columns(2)
                    ->schema([
                        Select::make('layout')
                            ->options(__('app.shop_home.layouts'))
                            ->default('hero')
                            ->required()
                            ->live(),
                        Select::make('theme')
                            ->label(__('app.shop_home.page_style'))
                            ->options(__('app.shop_home.themes'))
                            ->default('light')
                            ->required(),
                        Tabs::make('Translations')
                            ->tabs([
                                Tab::make('English (US)')->schema(self::translatedLayoutFields('en', true)),
                                Tab::make('Español (Latinoamérica)')->schema(self::translatedLayoutFields('es')),
                                Tab::make('Français (Canada)')->schema(self::translatedLayoutFields('fr')),
                            ])
                            ->columnSpanFull(),
                        self::imageUpload(),
                        Select::make('layout_data.shared.image_position')
                            ->label(__('app.shop_home.image_position'))
                            ->options(__('app.shop_home.positions'))
                            ->default('right')
                            ->required()
                            ->visible(fn (Get $get): bool => $get('layout') === 'split'),
                    ]),
                Section::make(__('app.shop_home.extra_sections'))
                    ->description(__('app.shop_home.extra_sections_description'))
                    ->schema([
                        Tabs::make('Translations')
                            ->tabs([
                                Tab::make('English (US)')->schema([self::richContentField('en')]),
                                Tab::make('Español (Latinoamérica)')->schema([self::richContentField('es')]),
                                Tab::make('Français (Canada)')->schema([self::richContentField('fr')]),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make(__('app.shop_home.booking_screen'))
                    ->description(__('app.shop_home.booking_screen_description'))
                    ->schema([
                        Repeater::make('booking_notices')
                            ->label(__('app.shop_home.booking_messages'))
                            ->schema([
                                Tabs::make('Translations')
                                    ->tabs([
                                        Tab::make('English (US)')->schema([self::noticeField('en', true)]),
                                        Tab::make('Español (Latinoamérica)')->schema([self::noticeField('es')]),
                                        Tab::make('Français (Canada)')->schema([self::noticeField('fr')]),
                                    ])
                                    ->columnSpanFull(),
                                Select::make('tone')
                                    ->options(__('app.shop_home.tones'))
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
            ->title(__('app.shop_home.saved'))
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
        return FileUpload::make('layout_data.shared.image_path')
            ->label(__('app.shop_home.page_image'))
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
        return __('app.shop_home.title');
    }

    /** @return array<int, Component> */
    private static function translatedLayoutFields(string $locale, bool $required = false): array
    {
        $fields = [
            TextInput::make("layout_data.{$locale}.eyebrow")
                ->label(__('app.shop_home.small_heading'))
                ->maxLength(120),
            TextInput::make("layout_data.{$locale}.headline")
                ->label(__('app.shop_home.main_heading'))
                ->required($required)
                ->maxLength(180),
            Textarea::make("layout_data.{$locale}.description")
                ->label(__('app.shop_home.introduction'))
                ->required($required)
                ->maxLength(1200),
            TextInput::make("layout_data.{$locale}.button_label")
                ->label(__('app.shop_home.booking_button'))
                ->required($required)
                ->maxLength(80),
        ];

        $fields[] = TextInput::make("layout_data.{$locale}.story_quote")
            ->label(__('app.shop_home.quote'))
            ->maxLength(500)
            ->visible(fn (Get $get): bool => $get('layout') === 'story');
        $fields[] = TextInput::make("layout_data.{$locale}.story_byline")
            ->label(__('app.shop_home.quote_attribution'))
            ->maxLength(120)
            ->visible(fn (Get $get): bool => $get('layout') === 'story');

        return $fields;
    }

    private static function richContentField(string $locale): RichEditor
    {
        return RichEditor::make("custom_content.{$locale}")
            ->label(__('app.shop_home.page_sections'))
            ->customBlocks([ShopCalloutBlock::class, ShopFeatureBlock::class]);
    }

    private static function noticeField(string $locale, bool $required = false): TextInput
    {
        return TextInput::make("message.{$locale}")
            ->label(__('app.shop_home.message'))
            ->required($required)
            ->maxLength(240);
    }
}
