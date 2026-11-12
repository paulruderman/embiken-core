<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Download the CSV templates</x-slot>
            <x-slot name="description">Set up rental packages first, then connect each package to catalog variants using descriptive catalog fields.</x-slot>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['name' => 'packages', 'number' => '01', 'description' => 'Define package price meter, booking visibility, and payment confirmation threshold.'],
                    ['name' => 'package_rates', 'number' => '02', 'description' => 'Choose offered variants and set each package rate in the shop currency minor units.'],
                ] as $template)
                    <div class="flex min-h-36 flex-col justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">{{ $template['number'] }}</span>
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ ucfirst(str_replace('_', ' ', $template['name'])) }}</h3>
                                <p class="text-sm leading-5 text-gray-600 dark:text-gray-400">{{ $template['description'] }}</p>
                            </div>
                        </div>
                        <x-filament::button color="gray" size="sm" icon="heroicon-o-arrow-down-tray" wire:click="downloadTemplate('{{ $template['name'] }}')" class="self-start">Download CSV</x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        @if (in_array($importRunStatus, ['queued', 'processing'], true))
            <div wire:poll.3s="refreshImportRun" class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800 dark:border-primary-400/20 dark:bg-primary-400/10 dark:text-primary-200">
                <div class="flex items-center gap-3"><x-filament::icon icon="heroicon-o-arrow-path" class="size-5 animate-spin" /><div><p class="font-medium">Import {{ $importRunStatus }}</p><p class="mt-1">Package and rate files are being processed in the background.</p></div></div>
            </div>
        @elseif ($importRunStatus === 'failed')
            <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700 dark:border-danger-400/20 dark:bg-danger-400/10 dark:text-danger-300">The import could not be completed. Upload the two CSV files again and retry.</div>
        @elseif ($importRunStatus === 'completed')
            <div class="rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-400/20 dark:bg-success-400/10 dark:text-success-300">The package and rate import completed successfully.</div>
        @elseif ($importRunStatus === 'not_applied')
            <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-400/20 dark:bg-warning-400/10 dark:text-warning-200">The import was not applied. Review the report below.</div>
        @endif

        <form wire:submit="preview" class="space-y-6">
            {{ $this->form }}
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">Preview import</x-filament::button>
                @if ($report !== null && ! ($report['applied'] ?? false) && $importRunStatus === null)
                    <x-filament::button color="success" icon="heroicon-o-arrow-up-tray" wire:click="applyImport" wire:loading.attr="disabled" wire:target="applyImport" :disabled="! ($report['can_apply'] ?? false)">Apply import</x-filament::button>
                @endif
            </div>
        </form>

        @if ($report !== null)
            <section class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div><h2 class="text-base font-semibold">Import preview</h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ number_format($report['valid_rows']) }} rows can be processed. @if ($report['applied'] ?? false) The import has been applied. @elseif ($report['can_apply']) Review the counts and issues, then apply. @else Resolve the reported issues before applying. @endif</p></div>
                <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-gray-200 dark:border-gray-700"><th class="py-2 pr-4">File</th><th class="py-2 pr-4">Create</th><th class="py-2 pr-4">Update</th><th class="py-2 pr-4">Skip</th><th class="py-2 pr-4">Conflicts</th><th class="py-2">Invalid</th></tr></thead><tbody>
                    @foreach ($report['summary'] as $file => $counts)
                        <tr class="border-b border-gray-100 dark:border-gray-800"><th class="py-2 pr-4 font-medium">{{ ucfirst(str_replace('_', ' ', $file)) }}</th><td class="py-2 pr-4">{{ $counts['create'] }}</td><td class="py-2 pr-4">{{ $counts['update'] }}</td><td class="py-2 pr-4">{{ $counts['skip'] }}</td><td class="py-2 pr-4">{{ $counts['conflict'] }}</td><td class="py-2">{{ $counts['invalid'] }}</td></tr>
                    @endforeach
                </tbody></table></div>
                @if ($report['issues'] !== [])
                    <div><h3 class="font-medium">Issues</h3><ul class="mt-2 list-inside list-disc space-y-1 text-sm text-danger-600 dark:text-danger-400">@foreach ($report['issues'] as $issue)<li>{{ ucfirst(str_replace('_', ' ', $issue['file'])) }}{{ $issue['row'] === null ? '' : ' row '.$issue['row'] }}: {{ $issue['message'] }}</li>@endforeach</ul></div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
