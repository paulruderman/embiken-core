<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Set up your shop</x-slot>

        <x-slot name="description">
            Complete the essentials to configure your catalog and rental offer.
        </x-slot>

        @if ($essentialsComplete)
            <div class="flex items-start gap-3 rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-400/20 dark:bg-success-400/5">
                <x-filament::icon icon="heroicon-o-check-circle" class="mt-0.5 size-6 shrink-0 text-success-600 dark:text-success-400" />

                <div class="flex flex-col gap-1">
                    <p class="font-semibold text-success-950 dark:text-success-100">
                        Your essential setup is complete
                    </p>
                    <p class="text-sm text-success-800 dark:text-success-200">
                        Your required shop configuration is complete. You can review the completed steps or continue with optional improvements below.
                    </p>
                    <p class="text-xs font-medium text-success-700 dark:text-success-300">
                        {{ $completedCount }} of {{ $totalCount }} essential setup steps complete
                    </p>
                </div>
            </div>

            <details class="mt-4 rounded-xl border border-gray-200 dark:border-white/10">
                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                    Review completed setup
                </summary>

                <div class="flex flex-col gap-6 border-t border-gray-200 p-4 dark:border-white/10">
                    @foreach ($sections as $section)
                        @include('filament.shop.widgets.partials.setup-section', ['section' => $section])
                    @endforeach
                </div>
            </details>
        @else
            <div class="mb-6 flex flex-col gap-2">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">Required setup</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ $completedCount }} of {{ $totalCount }} complete</span>
                </div>

                <div
                    class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                    role="progressbar"
                    aria-label="Required shop setup progress"
                    aria-valuemin="0"
                    aria-valuemax="{{ $totalCount }}"
                    aria-valuenow="{{ $completedCount }}"
                >
                    <div class="h-full rounded-full bg-primary-600 transition-all dark:bg-primary-400" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <label class="inline-flex w-fit cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input
                        type="checkbox"
                        wire:model.live="showOnlyReady"
                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600 dark:border-white/20 dark:bg-gray-900"
                    />
                    <span>Show only steps ready to set up now</span>
                </label>

                @if ($sections === [])
                    <p class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                        No setup steps are ready right now. Complete a waiting step to unlock the next ones.
                    </p>
                @else
                    @foreach ($sections as $section)
                        @include('filament.shop.widgets.partials.setup-section', ['section' => $section])
                    @endforeach
                @endif
            </div>
        @endif

        @if ($recommendations !== [])
            <div class="mt-6 border-t border-gray-200 pt-5 dark:border-white/10">
                <div class="mb-4 flex flex-col gap-1">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Optional improvements</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">These can help polish your customer experience, but they do not block your setup.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($recommendations as $recommendation)
                        <article class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-3">
                                <h4 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $recommendation['label'] }}</h4>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">Optional</span>
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $recommendation['description'] }}</p>

                            <div class="mt-auto flex flex-wrap gap-x-4 gap-y-2">
                                @foreach ($recommendation['actions'] as $action)
                                    <a href="{{ $action['url'] }}" wire:navigate class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
