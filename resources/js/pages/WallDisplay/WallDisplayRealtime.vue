<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';
import { useDayStore, type DayBike, type DayReservation } from '@/stores/day';

const props = defineProps<{ tenantId: string; locationId: number; displayId: number }>();
type DisplayUpdate = {
    state: string;
    display: { id: number; name: string; dark_mode: boolean };
    feed: { slug: string; name: string; component: string; data: Record<string, unknown> } | null;
};
type FeedUpdate = { feed: NonNullable<DisplayUpdate['feed']> };
const emit = defineEmits<{
    displayUpdated: [payload: DisplayUpdate];
    feedUpdated: [payload: FeedUpdate];
    weatherUpdated: [payload: Record<string, unknown>];
}>();
const day = useDayStore();
const channel = `tenant.${props.tenantId}.location.${props.locationId}`;
const patchReservation = (reservation: DayReservation): void => {
    day.patchReservation({
        ...reservation,
        customer: { id: 0, name: '' },
        owed_minor_units: 0,
        paid_minor_units: 0,
        myrental_token: null,
        waiver_accepted_at: null,
        lines: reservation.lines.map((line) => ({
            ...line,
            rider_name: null,
            rider_height_cm: null,
        })),
    });
};
useEcho<DayBike>(channel, ['.BikeCreated', '.BikeUpdated'], (bike) => day.patchBike(bike));
useEcho<{ id: number }>(channel, '.BikeDeleted', ({ id }) => day.removeBike(id));
useEcho<DayReservation>(channel, ['.ReservationCreated', '.ReservationUpdated'], patchReservation);
useEcho<{ id: number }>(channel, '.ReservationDeleted', ({ id }) => day.removeReservation(id));
useEcho<DisplayUpdate>(channel, '.DisplayUpdated', (payload) => {
    if (payload.display.id === props.displayId) emit('displayUpdated', payload);
});
useEcho<FeedUpdate>(channel, '.WallDisplayFeedUpdated', (payload) => emit('feedUpdated', payload));
useEcho<Record<string, unknown>>(channel, '.WeatherUpdated', (payload) => emit('weatherUpdated', payload));
</script>

<template><span class="hidden" /></template>
