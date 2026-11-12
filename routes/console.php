<?php

use App\Models\Tenant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Lorisleiva\Actions\Facades\Actions;

Actions::registerCommands('app/Actions');

Actions::registerCommands(base_path('packages/bike-rentals/src/Actions'));

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('weather:refresh')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::call(function (): void {
    foreach (Tenant::query()->orderBy('id')->cursor() as $tenant) {
        Artisan::call('reservations:tick', ['--tenant' => $tenant->id]);
    }
})
    ->name('reservations:tick')
    ->everyMinute()
    ->withoutOverlapping(30)
    ->onOneServer();
