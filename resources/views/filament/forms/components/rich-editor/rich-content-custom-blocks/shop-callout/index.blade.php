@php
    $toneClasses = match ($tone) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
        default => 'border-sky-200 bg-sky-50 text-sky-950',
    };
@endphp
<aside class="shop-callout rounded-2xl border p-5 {{ $toneClasses }}">
    <h2 class="text-lg font-semibold">{{ $heading }}</h2>
    <p class="mt-2 leading-7">{{ $message }}</p>
</aside>
