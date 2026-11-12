<?php

namespace App\Filament\Shop\Pages;

use App\Actions\Shop\UpdateShopBrandingAction;
use App\Enums\StaffRole;
use App\Filament\Shop\Forms\Components\BrandColorDropdown;
use App\Models\ShopSetting;
use App\Models\Staff;
use App\Support\BrandColor;
use Filament\Facades\Filament;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use LogicException;

class Branding extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop settings';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.shop.pages.branding';

    public static function getNavigationLabel(): string
    {
        return __('app.page_titles.branding');
    }

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $staff = Filament::auth()->user();

        return $staff instanceof Staff && $staff->role === StaffRole::Manager;
    }

    public function mount(): void
    {
        $this->brandingForm()->fill(ShopSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        $colorHelperText = __('app.branding_ui.color_help');

        return $schema
            ->components([
                TextInput::make('brand_name')
                    ->label(__('app.branding_ui.brand_name'))
                    ->helperText(__('app.branding_ui.brand_name_help'))
                    ->maxLength(255),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        BrandColorDropdown::make('primary_color')
                            ->label(__('app.branding_ui.primary_color'))
                            ->placeholder(__('app.branding_ui.primary_color_placeholder'))
                            ->helperText($colorHelperText)
                            ->maxLength(32)
                            ->live(),
                        View::make('filament.shop.components.brand-color-palette')
                            ->viewData(fn (Get $get): array => self::palettePreview(
                                $get('primary_color'),
                                ShopSetting::DEFAULT_PRIMARY_COLOR,
                                __('app.branding_ui.primary_shades'),
                            )),
                    ]),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        BrandColorDropdown::make('brand_gray_color')
                            ->label(__('app.branding_ui.brand_gray'))
                            ->placeholder(__('app.branding_ui.brand_gray_placeholder'))
                            ->helperText($colorHelperText)
                            ->maxLength(32)
                            ->live(),
                        View::make('filament.shop.components.brand-color-palette')
                            ->viewData(fn (Get $get): array => self::palettePreview(
                                $get('brand_gray_color'),
                                ShopSetting::DEFAULT_GRAY_COLOR,
                                __('app.branding_ui.gray_shades'),
                            )),
                    ]),
                self::brandingImageUpload('logo_path', __('app.branding_ui.panel_logo'), ['image/png', 'image/jpeg', 'image/webp'], 2048),
                self::brandingImageUpload('favicon_path', __('app.branding_ui.favicon'), ['image/png'], 1024)
                    ->helperText(__('app.branding_ui.favicon_help'))
                    ->imageAspectRatio('1:1')
                    ->automaticallyOpenImageEditorForAspectRatio(),
            ])
            ->statePath('data');
    }

    /**
     * @param  list<string>  $acceptedFileTypes
     */
    private static function brandingImageUpload(string $name, string $label, array $acceptedFileTypes, int $maxSize): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->acceptedFileTypes($acceptedFileTypes)
            ->maxSize($maxSize)
            ->disk('public')
            ->directory('branding')
            ->visibility('public')
            ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): array {
                $disk = $component->getDisk();

                return [
                    'name' => (is_array($storedFileNames) ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                    'size' => $disk->size($file),
                    'type' => $disk->mimeType($file),
                    'url' => tenant_asset($file),
                ];
            });
    }

    /**
     * @return array{label: string, palette: array<int, string>}
     */
    private static function palettePreview(?string $color, string $defaultColor, string $label): array
    {
        $palette = BrandColor::palette($color, $defaultColor);

        return [
            'label' => $label,
            'palette' => array_map(
                static fn (string $shade): string => Color::convertToHex($shade),
                $palette,
            ),
        ];
    }

    public function save(): void
    {
        $branding = $this->brandingForm()->getState();

        app(UpdateShopBrandingAction::class)($branding);

        Notification::make()
            ->title(__('app.branding_ui.saved'))
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    private function brandingForm(): Schema
    {
        return $this->getSchema('form') ?? throw new LogicException('The branding schema is unavailable.');
    }

    public function getTitle(): string|Htmlable
    {
        return (string) __('app.page_titles.branding');
    }
}
