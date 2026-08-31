<script setup lang="ts">
/**
 * PROTOTYPE Variant O — Orbits. Three time rings (morning / midday / afternoon); tickets as beads.
 * Timeline as gravity wells, not rows.
 */
import { computed, reactive, ref } from 'vue';
import {
    acceptWaiver,
    assignBike,
    bikeFor,
    cancelReservation,
    createShop,
    extendReservation,
    hourOf,
    markReturned,
    money,
    openReservations,
    pickup,
    startWalkIn,
    takeCash,
    type Bike,
    type Reservation,
} from './mock';

const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

function ring(reservation: Reservation): 'morning' | 'midday' | 'afternoon' {
    const hour = hourOf(reservation.starts);

    if (hour < 11) {
        return 'morning';
    }

    if (hour < 14) {
        return 'midday';
    }

    return 'afternoon';
}

const rings = computed(() => ({
    morning: openReservations(shop).filter((reservation) => ring(reservation) === 'morning'),
    midday: openReservations(shop).filter((reservation) => ring(reservation) === 'midday'),
    afternoon: openReservations(shop).filter((reservation) => ring(reservation) === 'afternoon'),
}));

function walkIn(): void {
    focusId.value = startWalkIn(shop).id;
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (reservation) {
        pane.value = 'ticket';
        toast.value = `${reservation.starts}–${reservation.ends}`;
    }
}

function pickBike(bike: Bike): void {
    if (focus.value) {
        assignBike(shop, focus.value, bike.id);
        pane.value = 'ticket';
    }
}

function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        toast.value = 'Return Out bikes first';
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-sky-950 pb-24 text-sky-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Orbits</span>
            <span class="font-mono text-xs">rings of start-hour</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-sky-300 px-4 font-bold text-sky-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex flex-1 flex-col items-center justify-center gap-3 p-3">
            <div
                v-for="name in ['morning', 'midday', 'afternoon'] as const"
                :key="name"
                class="flex w-full max-w-xl flex-wrap items-center gap-2 rounded-full border-2 border-sky-700 px-4 py-3"
            >
                <span class="w-24 text-xs tracking-widest uppercase">{{ name }}</span>
                <button
                    v-for="reservation in rings[name]"
                    :key="reservation.id"
                    type="button"
                    class="h-16 min-w-16 rounded-full bg-sky-400 px-3 font-bold text-sky-950"
                    @click="focusId = reservation.id"
                >
                    {{ reservation.customer.split(' ')[0] }}
                </button>
            </div>
        </div>
        <aside v-if="focus" class="border-t border-sky-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <span v-for="line in focus.lines" :key="line.id" class="rounded-full bg-sky-900 px-3 py-1">
                    {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? '—' }}
                </span>
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-sky-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-sky-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-600" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-sky-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
