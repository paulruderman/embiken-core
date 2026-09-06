<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-3">
            @if (count($this->siteOptions()) > 0)
                <label for="weather-site-select" class="text-sm font-medium">Site</label>
                <select
                    id="weather-site-select"
                    wire:model.live="selectedSiteCode"
                    class="fi-select-input rounded-lg bg-white py-1.5 ps-3 pe-10 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20"
                >
                    @foreach ($this->siteOptions() as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">No weather sites yet. Add one under Weather → Sites.</p>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->siteSummaries() as $site)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $site['label'] }}</p>
                    <p class="mt-1 text-3xl font-light">
                        {{ $site['current']['temperature_f'] ?? '—' }}°F
                        <span class="text-lg text-gray-500">/ {{ $site['current']['temperature_c'] ?? '—' }}°C</span>
                    </p>
                    <p class="mt-1 text-sm">{{ $site['current']['short_forecast'] ?? 'No forecast yet' }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $site['current']['wind_direction'] ?? '' }} {{ $site['current']['wind_speed'] ?? '' }}
                    </p>
                </div>
            @endforeach
        </div>

        @if ($this->radarEmbedUrl())
            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                <iframe
                    title="Radar"
                    src="{{ $this->radarEmbedUrl() }}"
                    class="h-96 w-full border-0"
                ></iframe>
                <p class="px-4 py-2 text-xs text-gray-500">Radar source: {{ $this->radarSource() }}</p>
            </div>
        @endif

        @if ($this->forecast() !== [])
            <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-3 py-2">Start</th>
                            <th class="px-3 py-2">°F</th>
                            <th class="px-3 py-2">Forecast</th>
                            <th class="px-3 py-2">Wind</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->forecast() as $period)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="px-3 py-2 tabular-nums">{{ $period->starts_at->timezone(config('app.timezone'))->format('D H:i') }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $period->temperature_f }}</td>
                                <td class="px-3 py-2">{{ $period->short_forecast }}</td>
                                <td class="px-3 py-2">{{ $period->wind_direction }} {{ $period->wind_speed }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
