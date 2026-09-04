<script setup lang="ts">
/**
 * PROTOTYPE Variant T — Shuttle truck. This shop runs trailhead drops.
 * The glass is the truck bed: one party's bikes strapped in as a load.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeFor,
    money,
    openReservations,
    partyLines,
    type Reservation,
} from './mock';

const shop = useShopFloor();
const desk = useTerminalDesk();
const index = ref(0);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const tickets = computed(() => openReservations(shop));

const load = computed(() => tickets.value[index.value] ?? tickets.value[0]);

function flash(message: string): void {
    toast.value = message;
}

function prev(): void {
    if (tickets.value.length === 0) {
        return;
    }

    index.value = (index.value + tickets.value.length - 1) % tickets.value.length;
}

function next(): void {
    if (tickets.value.length === 0) {
        return;
    }

    index.value = (index.value + 1) % tickets.value.length;
}

function walkIn(): void {
    desk.startWalkIn((id) => {
        const idx = tickets.value.findIndex((ticket) => ticket.id === id);

        if (idx >= 0) {
            index.value = idx;
        }
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
}
</script>

<template>
    <div v-if="load" class="flex min-h-screen flex-col bg-sky-950 pb-24 text-sky-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Shuttle</span>
            <span class="font-mono text-xs">load {{ index + 1 }}/{{ tickets.length }}</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-sky-300 px-4 font-bold text-sky-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex flex-1 items-stretch gap-2 px-3">
            <button type="button" class="w-14 rounded-2xl bg-sky-900 text-3xl" @click="prev">‹</button>
            <div class="flex min-w-0 flex-1 flex-col">
                <p class="mb-2 text-center text-xs tracking-widest text-sky-400 uppercase">Truck bed — {{ load.customer }}</p>
                <div class="relative min-h-56 rounded-t-3xl bg-sky-800 p-4">
                    <div class="mb-3 h-3 rounded-full bg-sky-950" title="tailgate" />
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="row in partyLines(shop, load)"
                            :key="row.line.id"
                            class="flex h-24 items-end rounded-xl bg-sky-950 p-2"
                        >
                            <div
                                class="h-20 w-8 rounded-t-full"
                                :class="row.bike ? 'bg-orange-400' : 'border-2 border-dashed border-sky-600'"
                            />
                            <div class="ml-2 min-w-0">
                                <div class="font-black">{{ row.bike?.bid ?? 'empty strap' }}</div>
                                <div class="truncate text-xs text-sky-300">{{ row.line.product }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-b-3xl bg-sky-900 p-4">
                    <div class="text-3xl font-black">{{ load.customer }}</div>
                    <div class="text-xl">{{ load.starts }} leave the lot</div>
                    <div class="text-amber-300">owed {{ money(load.owed) }} / paid {{ money(load.paid) }}</div>
                    <PrototypePartyLines class="mt-2" :shop="shop" :reservation="load" />
                </div>
            </div>
            <button type="button" class="w-14 rounded-2xl bg-sky-900 text-3xl" @click="next">›</button>
        </div>
        <div class="p-3">
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-sky-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(load)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-sky-950" @click="desk.markReturned(load)">Return</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="desk.takeCash(load)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="desk.acceptWaiver(load)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="flash(load.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(load)">Cancel</button>
            </div>
        </div>
    </div>
</template>
