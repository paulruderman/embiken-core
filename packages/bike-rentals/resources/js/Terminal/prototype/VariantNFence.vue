<script setup lang="ts">
/**
 * PROTOTYPE Variant N — Fence rail. Saturday morning: bikes already chained
 * along the chain-link in the order they will roll out of the lot.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeCaption,
    bikeFor,
    hourOf,
    money,
    reservationForBike,
    type Bike,
    type Reservation,
} from './mock';

const shop = useShopFloor();
const desk = useTerminalDesk();
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const onFence = computed(() =>
    shop.bikes
        .filter((bike) => ['prepping', 'staged', 'rented_out'].includes(bike.situation))
        .sort((left, right) => {
            const leftStart = reservationForBike(shop, left.id)?.starts ?? '99:00';
            const rightStart = reservationForBike(shop, right.id)?.starts ?? '99:00';

            return hourOf(leftStart) - hourOf(rightStart) || left.bid.localeCompare(right.bid);
        }),
);

const inShed = computed(() => shop.bikes.filter((bike) => bike.situation === 'home' || bike.situation === 'back'));

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

    flash(bikeCaption(shop, bike));
}

function walkIn(): void {
    desk.startWalkIn((id) => {
        focusId.value = id;
    });
}

function doExtend(requote: boolean): void {
    flash(requote ? 'Requote stub' : 'Keep owed stub');
    pane.value = 'ticket';
}

function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        flash('Return Out bikes first');
        return;
    }

    desk.cancelReservation(reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-800 pb-24 text-zinc-100">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Fence</span>
            <span class="text-xs text-zinc-400">left = next off the lot</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-lime-500 px-4 font-bold text-zinc-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <p class="px-3 text-xs tracking-widest text-zinc-500 uppercase">Chain-link — staged for rollout</p>
        <div class="flex min-h-40 gap-2 overflow-x-auto px-3 py-2">
            <div class="w-4 shrink-0 rounded-sm bg-zinc-600" title="post" />
            <button
                v-for="bike in onFence"
                :key="bike.id"
                type="button"
                class="w-28 shrink-0 rounded-lg border-2 border-zinc-500 bg-zinc-700 p-2 text-left"
                @click="openBike(bike)"
            >
                <div class="text-2xl font-black">{{ bike.bid }}</div>
                <div class="text-[10px] leading-tight text-zinc-300">{{ bikeCaption(shop, bike) }}</div>
            </button>
            <div class="w-4 shrink-0 rounded-sm bg-zinc-600" />
        </div>
        <p class="mt-4 px-3 text-xs tracking-widest text-zinc-500 uppercase">Still in the shed</p>
        <div class="flex flex-wrap gap-2 px-3">
            <button
                v-for="bike in inShed"
                :key="bike.id"
                type="button"
                class="h-16 min-w-16 rounded-xl bg-zinc-900 px-3 text-left"
                @click="openBike(bike)"
            >
                <div class="font-bold">{{ bike.bid }}</div>
                <div class="truncate text-[10px] text-zinc-500">{{ bikeCaption(shop, bike) }}</div>
            </button>
        </div>
        <aside v-if="focus" class="mt-auto border-t border-zinc-700 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-zinc-700" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-zinc-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-zinc-950" @click="desk.markReturned(focus)">Return</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-700" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="desk.takeCash(focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-700" @click="desk.acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-700" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
