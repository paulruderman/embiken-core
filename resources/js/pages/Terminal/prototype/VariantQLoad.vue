<script setup lang="ts">
/**
 * PROTOTYPE Variant Q — Load histogram. Each hour is a stacked tower of busy bikes.
 * Timeline as capacity, “how full is 2pm”, not who.
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
const pickedHour = ref<number | null>(12);
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

function load(hour: number): Bike[] {
    return shop.bikes.filter((bike) => busyAt(shop, bike.id, hour));
}

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
    <div class="flex min-h-screen flex-col bg-violet-950 pb-24 text-violet-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Load</span>
            <span class="font-mono text-xs">busy count / hour</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-violet-400 px-4 font-bold text-violet-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex h-64 items-end justify-around gap-1 px-3">
            <button
                v-for="hour in hours"
                :key="hour"
                type="button"
                class="flex w-10 flex-col items-center"
                @click="pickedHour = hour"
            >
                <div
                    class="w-8 rounded-t-lg"
                    :class="pickedHour === hour ? 'bg-violet-300' : 'bg-violet-600'"
                    :style="{ height: `${Math.max(12, load(hour).length * 28)}px` }"
                />
                <span class="mt-1 text-xs">{{ hour }}</span>
            </button>
        </div>
        <div class="flex flex-wrap gap-2 p-3">
            <button
                v-for="bike in load(pickedHour ?? 12)"
                :key="bike.id"
                type="button"
                class="h-16 min-w-16 rounded-2xl bg-violet-400 px-3 font-bold text-violet-950"
                @click="focusId = busyAt(shop, bike.id, pickedHour ?? 12)?.id ?? null"
            >
                {{ bike.bid }}
            </button>
            <p v-if="load(pickedHour ?? 12).length === 0" class="text-violet-500">Empty hour.</p>
        </div>
        <aside v-if="focus" class="mt-auto border-t border-violet-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <span v-for="line in focus.lines" :key="line.id" class="rounded-full bg-violet-900 px-3 py-1">
                    {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? '—' }}
                </span>
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-violet-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-violet-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-violet-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-violet-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-violet-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-violet-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-violet-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-violet-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
