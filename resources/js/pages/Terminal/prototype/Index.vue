<script setup lang="ts">
/**
 * Twenty Terminal POS screen maps, switchable via ?variant=,
 * on throwaway /prototype/terminal.
 * Question: what is always on screen vs a drill-in.
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TerminalLayout from '@/layouts/TerminalLayout.vue';
import ShowTerminalPrototypeAction from '@/actions/App/Actions/Terminal/ShowTerminalPrototypeAction';
import PrototypeSwitcher from '@/components/PrototypeSwitcher.vue';
import VariantAFloor from './VariantAFloor.vue';
import VariantBQueue from './VariantBQueue.vue';
import VariantCDeck from './VariantCDeck.vue';
import VariantDTimeline from './VariantDTimeline.vue';
import VariantESplit from './VariantESplit.vue';
import VariantFDue from './VariantFDue.vue';
import VariantGLanes from './VariantGLanes.vue';
import VariantHNow from './VariantHNow.vue';
import VariantIExceptions from './VariantIExceptions.vue';
import VariantJDocks from './VariantJDocks.vue';
import VariantKCovers from './VariantKCovers.vue';
import VariantLWeek from './VariantLWeek.vue';
import VariantMRoles from './VariantMRoles.vue';
import VariantNFence from './VariantNFence.vue';
import VariantOMoney from './VariantOMoney.vue';
import VariantPStaves from './VariantPStaves.vue';
import VariantQDoor from './VariantQDoor.vue';
import VariantRHopper from './VariantRHopper.vue';
import VariantSPortraits from './VariantSPortraits.vue';
import VariantTShuttle from './VariantTShuttle.vue';

defineOptions({ layout: TerminalLayout });

const variants = [
    { key: 'A', name: 'Floor board' },
    { key: 'B', name: 'Ticket queue' },
    { key: 'C', name: 'Verb deck' },
    { key: 'D', name: 'Day timeline' },
    { key: 'E', name: 'Split panes' },
    { key: 'F', name: 'Due well' },
    { key: 'G', name: 'Bike lanes' },
    { key: 'H', name: 'Now playhead' },
    { key: 'I', name: 'Exceptions' },
    { key: 'J', name: 'Dual dock' },
    { key: 'K', name: 'Covers' },
    { key: 'L', name: 'Week ribbon' },
    { key: 'M', name: 'Roles' },
    { key: 'N', name: 'Fence rail' },
    { key: 'O', name: 'Money rail' },
    { key: 'P', name: 'Model staves' },
    { key: 'Q', name: 'Dutch door' },
    { key: 'R', name: 'Hopper' },
    { key: 'S', name: 'Portraits' },
    { key: 'T', name: 'Shuttle truck' },
];

const page = usePage();

const current = computed(() => {
    const query = new URL(page.url, 'http://localhost').searchParams.get('variant');

    return variants.some((variant) => variant.key === query) ? (query as string) : 'A';
});

function select(key: string): void {
    router.get(
        ShowTerminalPrototypeAction.url({ query: { variant: key } }),
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
        <VariantIExceptions v-else-if="current === 'I'" />
        <VariantJDocks v-else-if="current === 'J'" />
        <VariantKCovers v-else-if="current === 'K'" />
        <VariantLWeek v-else-if="current === 'L'" />
        <VariantMRoles v-else-if="current === 'M'" />
        <VariantNFence v-else-if="current === 'N'" />
        <VariantOMoney v-else-if="current === 'O'" />
        <VariantPStaves v-else-if="current === 'P'" />
        <VariantQDoor v-else-if="current === 'Q'" />
        <VariantRHopper v-else-if="current === 'R'" />
        <VariantSPortraits v-else-if="current === 'S'" />
        <VariantTShuttle v-else-if="current === 'T'" />
        <PrototypeSwitcher :variants="variants" :current="current" @select="select" />
    </div>
</template>
