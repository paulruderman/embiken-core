<section class="flex flex-col gap-3">
    <div class="flex flex-col gap-1">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $section['label'] }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $section['description'] }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($section['steps'] as $step)
            <article class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                    <h4 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $step['label'] }}</h4>

                    <span @class([
                        'shrink-0 rounded-full px-2 py-1 text-xs font-medium',
                        'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' => $step['complete'],
                        'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400' => ! $step['complete'] && ! $step['blocked'],
                        'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' => $step['blocked'],
                    ])>
                        {{ $step['status'] }}
                    </span>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $step['description'] }}</p>

                @if ($step['action_url'] !== null)
                    <a href="{{ $step['action_url'] }}" wire:navigate class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                        {{ $step['action_label'] }}
                        <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
