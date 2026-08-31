<script setup lang="ts">
/**
 * PROTOTYPE Variant K — Scrubber freeze-frame. Drag (tap) an hour; the floor is who is busy THEN.
 * Timeline as a time machine, not a bar chart.
 */
import { computed, reactive, ref } from 'vue';
import {
    acceptWaiver,
    assignBike,
    bikeFor,
    busyAt,
    cancelReservation,
    createShop,
    extendReservation,
    markReturned,
    money,
    pickup,
    startWalkIn,
    takeCash,
    type Bike,
    type Reservation,
} from './mock';

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
const shop = reactive(createShop());
const hour = ref(12);
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => `scrub ${hour.value}:00`);

function tapBike(bike: Bike): void {
    const reservation = busyAt(shop, bike.id, hour.value);

    if (reservation) {
        focusId.value = reservation.id;
        pane.value = 'ticket';
        return;
    }

    toast.value = `${bike.bid} free at ${hour.value}:00`;
}

function walkIn(): void {
    const reservation = startWalkIn(shop);
    focusId.value = reservation.id;
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (reservation) {
        pane.value = 'ticket';
        toast.value = `${reservation.starts}–${reservation.ends} · ${money(reservation.owed)}`;
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
    <div class="flex min-h-screen flex-col bg-fuchsia-950 pb-24 text-fuchsia-50">
        <header class="flex flex-wrap items-center gap-2 px-3 py-2">
            <span class="font-semibold">Scrubber</span>
            <span class="font-mono text-xs">{{ stack }}</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-fuchsia-400 px-4 font-bold text-fuchsia-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex gap-1 overflow-x-auto px-3 pb-2">
            <button
                v-for="item in hours"
                :key="item"
                type="button"
                class="h-14 min-w-14 rounded-xl text-lg font-bold"
                :class="hour === item ? 'bg-fuchsia-300 text-fuchsia-950' : 'bg-fuchsia-900'"
                @click="hour = item"
            >
                {{ item }}
            </button>
        </div>
        <p class="px-3 text-sm text-fuchsia-400">Floor at {{ hour }}:00 — not “now”, the scrubbed instant.</p>
        <div class="grid grid-cols-5 gap-2 p-3">
            <button
                v-for="bike in shop.bikes"
                :key="bike.id"
                type="button"
                class="h-20 rounded-2xl text-xl font-bold"
                :class="busyAt(shop, bike.id, hour) ? 'bg-fuchsia-400 text-fuchsia-950' : 'bg-fuchsia-900'"
                @click="tapBike(bike)"
            >
                {{ bike.bid }}
                <span class="block text-xs font-normal">
                    {{ busyAt(shop, bike.id, hour)?.customer ?? 'free' }}
                </span>
            </button>
        </div>
        <aside v-if="focus" class="mt-auto border-t border-fuchsia-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <span v-for="line in focus.lines" :key="line.id" class="rounded-full bg-fuchsia-900 px-3 py-1">
                    {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? '—' }}
                </span>
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-fuchsia-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-fuchsia-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-fuchsia-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-fuchsia-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-fuchsia-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-fuchsia-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-fuchsia-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-fuchsia-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
