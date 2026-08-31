<script setup lang="ts">
/**
 * PROTOTYPE Variant R — Pigeonholes. One cubby per start hour; tickets stuffed in.
 * Time as mail slots, not bars.
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

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

function inCubby(hour: number): Reservation[] {
    return openReservations(shop).filter((reservation) => hourOf(reservation.starts) === hour);
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
    <div class="flex min-h-screen flex-col bg-amber-950 pb-24 text-amber-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Pigeonholes</span>
            <span class="font-mono text-xs">start-hour cubbies</span>
            <span class="ml-auto text-sm text-amber-200">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-amber-400 px-4 font-bold text-amber-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="grid flex-1 grid-cols-4 gap-2 overflow-auto p-3">
            <section v-for="hour in hours" :key="hour" class="min-h-28 rounded-2xl bg-amber-900 p-2">
                <h2 class="text-center text-lg font-black">{{ hour }}</h2>
                <button
                    v-for="reservation in inCubby(hour)"
                    :key="reservation.id"
                    type="button"
                    class="mt-1 w-full rounded-lg bg-amber-200 py-2 text-sm font-bold text-amber-950"
                    @click="focusId = reservation.id"
                >
                    {{ reservation.customer.split(' ')[0] }}
                    <span class="block text-xs font-normal">{{ reservation.lines.length }} lines</span>
                </button>
            </section>
        </div>
        <aside v-if="focus" class="border-t border-amber-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <span v-for="line in focus.lines" :key="line.id" class="rounded-full bg-amber-900 px-3 py-1">
                    {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? '—' }}
                </span>
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-amber-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-300 text-amber-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-amber-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-amber-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-amber-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-amber-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-amber-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-amber-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
