<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ShowWeatherPrototypeAction from '@/actions/Embiken/Weather/Actions/ShowWeatherPrototypeAction';
import PrototypeSwitcher from '@/components/PrototypeSwitcher.vue';
import WeatherStrip from '@weather/components/WeatherStrip.vue';
import WeatherRadar from '@weather/components/WeatherRadar.vue';
import { useWeatherStore, type WeatherSiteSnapshot } from '@weather/stores/weather';

const variants = [
    { key: 'strip', name: 'Chrome strip' },
    { key: 'board', name: 'Full board' },
    { key: 'ticket', name: 'Ticket adjacent' },
];

const page = usePage();
const weather = useWeatherStore();
const coverageNotice = page.props.coverageNotice as string;

weather.hydrate({
    sites: (page.props.sites as WeatherSiteSnapshot[]) ?? [],
    settings: page.props.settings as { radar_source: string; stale_forecast_retention_minutes: number } | undefined,
});

const current = computed(() => {
    const query = new URL(page.url, 'http://localhost').searchParams.get('variant');

    return variants.some((variant) => variant.key === query) ? (query as string) : 'strip';
});

function select(key: string): void {
    router.get(
        ShowWeatherPrototypeAction.url({ query: { variant: key } }),
        {},
        { replace: true, preserveState: true, preserveScroll: true },
    );
}

const first = computed(() => weather.sites[0] ?? null);
</script>

<template>
    <div class="min-h-screen bg-neutral-950 text-white">
        <Head title="Weather prototype" />

        <div v-if="current === 'strip'" class="flex min-h-screen flex-col">
            <div class="border-b border-white/10 p-4">
                <WeatherStrip :sites="weather.sites" :coverage-notice="coverageNotice" />
            </div>
            <div class="flex flex-1 items-center justify-center text-white/40">Floor / ticket well</div>
        </div>

        <div v-else-if="current === 'board'" class="space-y-4 p-4">
            <WeatherStrip :sites="weather.sites" :coverage-notice="coverageNotice" />
            <WeatherRadar :latitude="first?.latitude" :longitude="first?.longitude" />
        </div>

        <div v-else class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
            <div class="border-r border-white/10 p-6 text-white/40">Ticket</div>
            <div class="space-y-4 p-4">
                <WeatherStrip :sites="weather.sites" :coverage-notice="coverageNotice" />
                <WeatherRadar :latitude="first?.latitude" :longitude="first?.longitude" />
            </div>
        </div>

        <PrototypeSwitcher :variants="variants" :current="current" @select="select" />
    </div>
</template>
