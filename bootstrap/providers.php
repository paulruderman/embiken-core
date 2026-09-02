<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\PlatformPanelProvider;
use App\Providers\Filament\ShopPanelProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    PlatformPanelProvider::class,
    ShopPanelProvider::class,
    TenancyServiceProvider::class,
];
