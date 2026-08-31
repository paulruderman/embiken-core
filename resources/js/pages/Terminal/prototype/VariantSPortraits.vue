<script setup lang="ts">
/**
 * PROTOTYPE Variant S — Portrait cards. One ticket fills the glass; prev/next, not a board.
 * Not a timeline. Customer as a playing card.
 */
import { computed, reactive, ref } from 'vue';
import {
    acceptWaiver,
    assignBike,
    bikeFor,
    cancelReservation,
    createShop,
    extendReservation,
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
const index = ref(0);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const tickets = computed(() => openReservations(shop));

const focus = computed(() => tickets.value[index.value] ?? tickets.value[0]);

function prev(): void {
    index.value = (index.value + tickets.value.length - 1) % Math.max(1, tickets.value.length);
}

function next(): void {
    index.value = (index.value + 1) % Math.max(1, tickets.value.length);
}

function walkIn(): void {
    startWalkIn(shop);
    index.value = 0;
}

function doExtend(requote: boolean): void {
    if (!focus.value) {
        return;
    }

    const reservation = extendReservation(shop, focus.value.id, requote);

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
    index.value = 0;
}
</script>

<template>
    <div v-if="focus" class="flex min-h-screen flex-col bg-neutral-950 pb-24 text-neutral-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Portraits</span>
            <span class="font-mono text-xs">{{ index + 1 }}/{{ tickets.length }}</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-white px-4 font-bold text-neutral-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex flex-1 items-stretch gap-2 px-3">
            <button type="button" class="h-auto w-16 rounded-2xl bg-neutral-800 text-3xl" @click="prev">‹</button>
            <article class="flex flex-1 flex-col rounded-[2.5rem] bg-neutral-100 p-6 text-neutral-950">
                <h1 class="text-5xl font-black">{{ focus.customer }}</h1>
                <p class="mt-4 text-4xl font-bold">{{ focus.starts }}–{{ focus.ends }}</p>
                <p class="mt-2 text-3xl text-red-700">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</p>
                <ul class="mt-6 space-y-2 text-2xl">
                    <li v-for="line in focus.lines" :key="line.id">
                        {{ line.product }} · {{ bikeFor(shop, line.bike_id)?.bid ?? 'unassigned' }}
                    </li>
                </ul>
                <p class="mt-auto text-lg text-neutral-500">{{ focus.stage }} · waiver {{ focus.waiver ? 'yes' : 'no' }}</p>
            </article>
            <button type="button" class="h-auto w-16 rounded-2xl bg-neutral-800 text-3xl" @click="next">›</button>
        </div>
        <div class="p-3">
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-neutral-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-16 rounded-2xl bg-amber-400 text-neutral-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-neutral-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-neutral-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-16 rounded-2xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-16 rounded-2xl bg-orange-500 text-neutral-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-16 rounded-2xl bg-neutral-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-16 rounded-2xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-16 rounded-2xl bg-neutral-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-16 rounded-2xl bg-neutral-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-16 rounded-2xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </div>
    </div>
</template>
