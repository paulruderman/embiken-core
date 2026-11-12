<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import { useDayStore, type DayBike, type DayReservation } from '@/stores/day';

const props = defineProps<{
    tenantId: string;
    locationId: number;
}>();

const day = useDayStore();
const channel = `tenant.${props.tenantId}.location.${props.locationId}`;
const allEvents = useEcho<unknown>(channel, [], () => {});
type GlobalEventChannel = {
    listenToAll(callback: (event: string, payload: unknown) => void): void;
    stopListeningToAll(
        callback: (event: string, payload: unknown) => void,
    ): void;
};
const eventChannel = allEvents.channel() as unknown as GlobalEventChannel;

const logEveryEvent = (event: string, payload: unknown): void => {
    day.logBroadcast(event, payload);
};

onMounted(() => {
    eventChannel.listenToAll(logEveryEvent);
});

onUnmounted(() => {
    eventChannel.stopListeningToAll(logEveryEvent);
});

useEcho<DayBike>(channel, ['.BikeCreated', '.BikeUpdated'], (payload) => {
    day.patchBike(payload);
});

useEcho<{ id: number }>(channel, '.BikeDeleted', (payload) => {
    day.removeBike(payload.id);
});

useEcho<DayReservation>(
    channel,
    ['.ReservationCreated', '.ReservationUpdated'],
    (payload) => {
        day.patchReservation(payload);
    },
);

useEcho<{ id: number }>(channel, '.ReservationDeleted', (payload) => {
    day.removeReservation(payload.id);
});
</script>

<template>
    <span class="hidden" />
</template>
