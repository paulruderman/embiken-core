@php
    $toneClasses = match ($tone) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
        default => 'border-sky-200 bg-sky-50 text-sky-950',
    };
@endphp
<div class="rounded-xl border p-4 {{ $toneClasses }}">
    <strong>{{ $heading }}</strong>
    <p class="mt-1 text-sm">{{ $message }}</p>
</div>
