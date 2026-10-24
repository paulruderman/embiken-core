<script setup lang="ts">
import type { WeatherSiteSnapshot } from '@weather/stores/weather';

defineProps<{
    sites: WeatherSiteSnapshot[];
    coverageNotice: string;
}>();
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap gap-3">
            <div
                v-for="site in sites"
                :key="site.code"
                class="min-w-40 flex-1 rounded-xl bg-black/40 px-4 py-3 text-white ring-1 ring-white/15"
            >
                <p class="text-xs uppercase tracking-wide text-white/60">{{ site.label }}</p>
                <p class="mt-1 text-3xl font-light tabular-nums">
                    {{ site.current?.temperature_f ?? '—' }}°
                    <span class="text-lg text-white/50">{{ site.current?.temperature_c ?? '—' }}C</span>
                </p>
                <p class="mt-1 text-sm">{{ site.current?.short_forecast ?? 'No forecast' }}</p>
                <p class="mt-1 text-xs text-white/50">
                    {{ site.current?.wind_direction }} {{ site.current?.wind_speed }}
                </p>
            </div>
        </div>
        <p class="text-xs text-white/50">{{ coverageNotice }}</p>
    </div>
</template>
