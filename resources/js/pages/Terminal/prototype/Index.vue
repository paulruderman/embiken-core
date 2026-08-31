<script setup lang="ts">
/**
 * Ten variants of the Terminal POS screen map, switchable via ?variant=,
 * on throwaway /prototype/terminal.
 * Question: what is always on screen vs a drill-in.
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { terminal } from '@/routes/prototype';
import PrototypeSwitcher from '@/components/PrototypeSwitcher.vue';
import VariantAFloor from './VariantAFloor.vue';
import VariantBQueue from './VariantBQueue.vue';
import VariantCDeck from './VariantCDeck.vue';
import VariantDTimeline from './VariantDTimeline.vue';
import VariantESplit from './VariantESplit.vue';
import VariantFDue from './VariantFDue.vue';
import VariantGLanes from './VariantGLanes.vue';
import VariantHNow from './VariantHNow.vue';
import VariantIClock from './VariantIClock.vue';
import VariantJDocks from './VariantJDocks.vue';

const variants = [
    { key: 'A', name: 'Floor board' },
    { key: 'B', name: 'Ticket queue' },
    { key: 'C', name: 'Verb deck' },
    { key: 'D', name: 'Day timeline' },
    { key: 'E', name: 'Split panes' },
    { key: 'F', name: 'Due well' },
    { key: 'G', name: 'Bike lanes' },
    { key: 'H', name: 'Now playhead' },
    { key: 'I', name: 'Clock face' },
    { key: 'J', name: 'Dual dock' },
];

const page = usePage();

const current = computed(() => {
    const query = new URL(page.url, 'http://localhost').searchParams.get('variant');

    return variants.some((variant) => variant.key === query) ? (query as string) : 'A';
});

function select(key: string): void {
    router.get(
        terminal.url({ query: { variant: key } }),
        {},
        { replace: true, preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <div>
        <Head title="Terminal POS prototype" />
        <VariantAFloor v-if="current === 'A'" />
        <VariantBQueue v-else-if="current === 'B'" />
        <VariantCDeck v-else-if="current === 'C'" />
        <VariantDTimeline v-else-if="current === 'D'" />
        <VariantESplit v-else-if="current === 'E'" />
        <VariantFDue v-else-if="current === 'F'" />
        <VariantGLanes v-else-if="current === 'G'" />
        <VariantHNow v-else-if="current === 'H'" />
        <VariantIClock v-else-if="current === 'I'" />
        <VariantJDocks v-else />
        <PrototypeSwitcher :variants="variants" :current="current" @select="select" />
    </div>
</template>
