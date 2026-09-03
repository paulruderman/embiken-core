<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TerminalDayEcho from '@/layouts/TerminalDayEcho.vue';
import {
    useDayStore,
    type DaySnapshot,
} from '@/stores/day';

const page = usePage();
const day = useDayStore();
const echoReady = ref(false);

const snapshot = page.props as unknown as DaySnapshot;

if (!day.hydrated) {
    day.hydrate(snapshot);
}

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
