<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Lorisleiva\Actions\Facades\Actions;

Actions::registerCommands('app/Actions');

foreach ([
    base_path('packages/bike-rentals/src/Actions'),
    base_path('packages/weather/src/Actions'),
] as $path) {
    Actions::registerCommands($path);
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('weather:refresh')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
