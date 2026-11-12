<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::intlLocale(request()->attributes->get('embiken.locale')) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($tenantShopSettings = tenant() ? \App\Models\ShopSetting::query()->first() : null)
        @if ($tenantShopSettings !== null)
            @php($tenantBrandPrimary = \App\Support\BrandColor::palette($tenantShopSettings->primary_color, \App\Models\ShopSetting::DEFAULT_PRIMARY_COLOR))
            @php($tenantBrandGray = \App\Support\BrandColor::palette($tenantShopSettings->brand_gray_color, \App\Models\ShopSetting::DEFAULT_GRAY_COLOR))
            <style>
                :root {
                    @foreach ($tenantBrandPrimary as $shade => $color)
                        --tenant-brand-primary-{{ $shade }}: {{ $color }};
                    @endforeach
                    @foreach ($tenantBrandGray as $shade => $color)
                        --tenant-brand-gray-{{ $shade }}: {{ $color }};
                    @endforeach
                }
            </style>
        @endif

        @php($tenantFaviconPath = $tenantShopSettings?->favicon_path)
        @if (is_string($tenantFaviconPath) && $tenantFaviconPath !== '')
            <link rel="icon" href="{{ tenant_asset($tenantFaviconPath) }}" type="image/png">
            <link rel="apple-touch-icon" href="{{ tenant_asset($tenantFaviconPath) }}">
        @else
            <link rel="icon" href="{{ global_asset('favicon.ico') }}" sizes="any">
            <link rel="icon" href="{{ global_asset('favicon.svg') }}" type="image/svg+xml">
            <link rel="apple-touch-icon" href="{{ global_asset('apple-touch-icon.png') }}">
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
