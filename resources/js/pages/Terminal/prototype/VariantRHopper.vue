<script setup lang="ts">
/**
 * PROTOTYPE Variant R — Put-away hopper. Afternoon is back bikes, not the booking diary.
 */
import { computed, reactive, ref } from 'vue';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    acceptWaiver,
    bikeCaption,
    bikeFor,
    cancelReservation,
    createShop,
    extendReservation,
    markReturned,
    money,
    pickup,
    reservationForBike,
    startWalkIn,
    takeCash,
    type Bike,
    type Reservation,
} from './mock';

const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const hopper = computed(() => shop.bikes.filter((bike) => bike.situation === 'back'));
const rest = computed(() => shop.bikes.filter((bike) => bike.situation !== 'back'));

function flash(message: string): void {
    toast.value = message;
}

function openBike(bike: Bike): void {
    const reservation = reservationForBike(shop, bike.id);

    if (reservation) {
        focusId.value = reservation.id;
        pane.value = 'ticket';
        return;
    }

    flash(`${bike.bid} — no ticket`);
}

function walkIn(): void {
    focusId.value = startWalkIn(shop).id;
    pane.value = 'ticket';
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (reservation) {
        pane.value = 'ticket';
        flash(`${reservation.starts}–${reservation.ends}`);
    }
}


function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        flash('Return Out bikes first');
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-950 pb-24 text-stone-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Hopper</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-stone-200 px-4 font-bold text-stone-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="min-h-0 flex-1 overflow-auto px-3">
            <h2 class="mb-2 text-xs tracking-widest text-stone-500 uppercase">Back — put away</h2>
            <div class="mb-6 grid grid-cols-2 gap-3">
                <button
                    v-for="bike in hopper"
                    :key="bike.id"
                    type="button"
                    class="min-h-28 rounded-3xl bg-orange-600 p-4 text-left text-stone-950"
                    @click="openBike(bike)"
                >
                    <div class="text-4xl font-black">{{ bike.bid }}</div>
                    <div class="text-sm">{{ bikeCaption(shop, bike) }}</div>
                </button>
                <p v-if="hopper.length === 0" class="col-span-2 py-8 text-center text-stone-600">Hopper empty</p>
            </div>
            <h2 class="mb-2 text-xs tracking-widest text-stone-500 uppercase">Everyone else</h2>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="bike in rest"
                    :key="bike.id"
                    type="button"
                    class="h-16 min-w-20 rounded-xl bg-stone-800 px-3 text-left"
                    @click="openBike(bike)"
                >
                    <div class="font-bold">{{ bike.bid }}</div>
                    <div class="truncate text-[10px] text-stone-400">{{ bikeCaption(shop, bike) }}</div>
                </button>
            </div>
        </div>
        <aside v-if="focus" class="border-t border-stone-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-stone-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-stone-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
