<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';
import {
    useDayStore,
    type DayBike,
    type DayReservation,
} from '@/stores/day';

const props = defineProps<{
    tenantId: string;
    locationId: number;
}>();

const day = useDayStore();
const channel = `tenant.${props.tenantId}.location.${props.locationId}`;

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
