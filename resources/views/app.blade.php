<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::intlLocale(request()->attributes->get('embiken.locale')) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($tenantFaviconPath = tenant() ? \App\Models\ShopSetting::query()->value('favicon_path') : null)
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
