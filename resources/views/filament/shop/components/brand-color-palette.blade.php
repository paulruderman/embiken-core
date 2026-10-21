<div class="space-y-2" aria-label="Generated primary color shades">
    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</p>

    <div class="grid grid-cols-6 gap-2 sm:grid-cols-11">
        @foreach ($palette as $shade => $color)
            <div class="space-y-1 text-center" aria-label="Shade {{ $shade }}: {{ $color }}">
                <div class="h-10 rounded-md border border-black/10 dark:border-white/15" style="display: block; width: 100%; min-height: 2.5rem; background-color: {{ $color }}"></div>
                <span class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ $shade }}</span>
            </div>
        @endforeach
    </div>
</div>
