<script setup lang="ts">
import WeatherStrip from '../components/WeatherStrip.vue';

defineProps<{ feed: { name: string; data: { sites: never[]; coverage_notice: string } } }>();
</script>

<template>
    <section class="mx-auto max-w-7xl space-y-6">
        <h1 class="text-4xl font-semibold">{{ feed.name }}</h1>
        <WeatherStrip :sites="feed.data.sites" :coverage-notice="feed.data.coverage_notice" />
    </section>
</template>
