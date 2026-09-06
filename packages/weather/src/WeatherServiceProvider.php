<?php

declare(strict_types=1);

namespace Embiken\Weather;

use Embiken\Weather\Actions\GetRadarFramesAction;
use Embiken\Weather\Actions\GetWeatherAction;
use Embiken\Weather\Actions\ListWeatherSitesAction;
use Embiken\Weather\Actions\RefreshWeatherAction;
use Embiken\Weather\Actions\ResolveSiteGridAction;
use Embiken\Weather\Actions\ShowWeatherPrototypeAction;
use Embiken\Weather\Actions\UpsertWeatherSiteAction;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;

class WeatherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            foreach ([
                GetRadarFramesAction::class,
                GetWeatherAction::class,
                ListWeatherSitesAction::class,
                RefreshWeatherAction::class,
                ResolveSiteGridAction::class,
                ShowWeatherPrototypeAction::class,
                UpsertWeatherSiteAction::class,
            ] as $action) {
                Actions::registerCommandsForAction($action);
            }
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'weather');
    }
}
