<?php

namespace App\Filament\Shop\Pages;

use App\Actions\Shop\UpdateShopBrandingAction;
use App\Enums\StaffRole;
use App\Models\ShopSetting;
use App\Models\Staff;
use Filament\Facades\Filament;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\ColorPicker;
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

class Branding extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|\UnitEnum|null $navigationGroup = 'Shop settings';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.shop.pages.branding';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $staff = Filament::auth()->user();

        return $staff instanceof Staff && $staff->role === StaffRole::Manager;
    }

    public function mount(): void
    {
        $this->form->fill(ShopSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('brand_name')
                    ->label('Brand name')
                    ->helperText('Leave blank to use the shop name.')
                    ->maxLength(255),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary color')
                            ->helperText('Choose one HEX color. Filament generates the matching shades.')
                            ->hex()
                            ->live(),
                        View::make('filament.shop.components.brand-color-palette')
                            ->viewData(fn (Get $get): array => self::palettePreview(
                                $get('primary_color'),
                                ShopSetting::DEFAULT_PRIMARY_COLOR,
                                'Primary shades',
                            )),
                    ]),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        ColorPicker::make('brand_gray_color')
                            ->label('Brand gray')
                            ->helperText('Used by Filament for neutral surfaces and interface elements.')
                            ->hex()
                            ->live(),
                        View::make('filament.shop.components.brand-color-palette')
                            ->viewData(fn (Get $get): array => self::palettePreview(
                                $get('brand_gray_color'),
                                ShopSetting::DEFAULT_GRAY_COLOR,
                                'Gray shades',
                            )),
                    ]),
                self::brandingImageUpload('logo_path', 'Panel logo', ['image/png', 'image/jpeg', 'image/webp'], 2048),
                self::brandingImageUpload('favicon_path', 'Favicon', ['image/png'], 1024),
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
        $palette = filled($color) ? Color::hex($color) : Color::hex($defaultColor);

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
        $branding = $this->form->getState();

        app(UpdateShopBrandingAction::class)($branding);

        Notification::make()
            ->title('Branding saved')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Branding';
    }
}
