<?php

namespace Embiken\Weather\Filament\Pages;

use App\Filament\Concerns\RequiresTenantModule;
use BackedEnum;
use Embiken\Weather\Actions\RefreshWeatherAction;
use Embiken\Weather\Enums\RadarSource;
use Embiken\Weather\Models\WeatherPeriod;
use Embiken\Weather\Models\WeatherSettings;
use Embiken\Weather\Models\WeatherSite;
use Embiken\Weather\Services\WeatherReader;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class WeatherDashboard extends Page
{
    use RequiresTenantModule;

    public static function tenantModuleId(): string
    {
        return 'embiken/weather';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static string|\UnitEnum|null $navigationGroup = 'Weather';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Weather';

    protected string $view = 'weather::filament.pages.weather-dashboard';

    public ?string $selectedSiteCode = null;

    public function mount(): void
    {
        $this->selectedSiteCode ??= WeatherSite::query()->enabled()->ordered()->value('code');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function siteSummaries(): array
    {
        return app(WeatherReader::class)->snapshot()['sites'];
    }

    public function selectedSite(): ?WeatherSite
    {
        if ($this->selectedSiteCode === null) {
            return null;
        }

        return WeatherSite::query()->enabled()->where('code', $this->selectedSiteCode)->first();
    }

    /**
     * @return list<WeatherPeriod>
     */
    public function forecast(): array
    {
        $site = $this->selectedSite();

        if ($site === null) {
            return [];
        }

        return $site->periods()->orderBy('starts_at')->limit(48)->get()->all();
    }

    /**
     * @return array<string, string>
     */
    public function siteOptions(): array
    {
        return WeatherSite::query()->enabled()->ordered()->pluck('label', 'code')->all();
    }

    public function radarSource(): string
    {
        return WeatherSettings::current()->radar_source->value;
    }

    public function radarEmbedUrl(): ?string
    {
        $site = $this->selectedSite();

        if ($site === null) {
            return null;
        }

        $lat = number_format((float) $site->latitude, 4, '.', '');
        $lng = number_format((float) $site->longitude, 4, '.', '');

        if (WeatherSettings::current()->radar_source === RadarSource::Noaa) {
            return 'https://radar.weather.gov/?settings=v1#/?center='.$lat.','.$lng.'&zoom=7';
        }

        return "https://www.rainviewer.com/map.html?loc={$lat},{$lng},8&layer=radar&sm=1&sn=1";
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('useRainviewer')
                ->label('RainViewer')
                ->visible(fn (): bool => WeatherSettings::current()->radar_source !== RadarSource::Rainviewer)
                ->action(function (): void {
                    $settings = WeatherSettings::current();
                    $settings->radar_source = RadarSource::Rainviewer;
                    $settings->save();
                }),
            Action::make('useNoaa')
                ->label('NOAA radar')
                ->visible(fn (): bool => WeatherSettings::current()->radar_source !== RadarSource::Noaa)
                ->action(function (): void {
                    $settings = WeatherSettings::current();
                    $settings->radar_source = RadarSource::Noaa;
                    $settings->save();
                }),
            Action::make('refresh')
                ->label('Update weather')
                ->action(function (): void {
                    try {
                        app(RefreshWeatherAction::class)();
                        Notification::make()->title('Weather updated')->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
