<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TerminalDayEcho from '@/layouts/TerminalDayEcho.vue';
import { useDayStore, type DaySnapshot } from '@/stores/day';

const page = usePage();
const day = useDayStore();
const echoReady = ref(false);

const snapshot = computed(() => page.props as unknown as DaySnapshot);

day.hydrate(snapshot.value);

watch(
    () => snapshot.value,
    (nextSnapshot, previousSnapshot) => {
        if (nextSnapshot !== previousSnapshot) {
            day.hydrate(nextSnapshot);
        }
    },
    { flush: 'sync' },
);

onMounted(() => {
    echoReady.value = true;
});
</script>

<template>
    <div>
        <TerminalDayEcho
            v-if="echoReady"
            :tenant-id="snapshot.tenant_id"
            :location-id="snapshot.location_id"
        />
        <slot />
    </div>
</template>
