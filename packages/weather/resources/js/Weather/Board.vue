<script setup lang="ts">
import WeatherStrip from '../components/WeatherStrip.vue';
import type { WeatherSiteSnapshot } from '@weather/stores/weather';

defineProps<{
    feed: {
        name: string;
        data: {
            sites: WeatherSiteSnapshot[];
            coverage_notice: string;
            empty_sites: string;
        };
    };
}>();
</script>

<template>
    <section class="mx-auto max-w-7xl space-y-6">
        <h1 class="text-4xl font-semibold">{{ feed.name }}</h1>
        <p
            v-if="feed.data.sites.length === 0"
            class="rounded-2xl bg-white/5 p-6 text-white/60 ring-1 ring-white/10"
        >
            {{ feed.data.empty_sites }}
        </p>
        <WeatherStrip
            v-else
            :sites="feed.data.sites"
            :coverage-notice="feed.data.coverage_notice"
        />
    </section>
</template>
