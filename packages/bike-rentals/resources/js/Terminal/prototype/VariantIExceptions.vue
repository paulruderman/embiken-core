<script setup lang="ts">
/**
 * PROTOTYPE Variant I — Exception strip. Home is only what is broken.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeFor,
    exceptionFlags,
    money,
    openReservations,
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

const broken = computed(() =>
    openReservations(shop)
        .map((reservation) => ({ reservation, flags: exceptionFlags(shop, reservation) }))
        .filter((row) => row.flags.length > 0),
);

function flash(message: string): void {
    toast.value = message;
}

function openTicket(reservation: Reservation): void {
    focusId.value = reservation.id;
    pane.value = 'ticket';
}

function walkIn(): void {
    desk.startWalkIn((id) => {
        const created = shop.reservations.find((item) => item.id === id);
        if (created) {
            openTicket(created);
        }
    });
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = focus.value;

    if (!reservation) {
        return;
    }

    desk.extendReservation(reservation, requote);

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

    desk.cancelReservation(reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-950 pb-24 text-rose-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Exceptions</span>
            <span class="font-mono text-xs text-rose-400">{{ broken.length }} open</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-rose-500 px-4 font-bold text-zinc-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="min-h-0 flex-1 space-y-2 overflow-auto px-3">
            <p v-if="broken.length === 0" class="py-16 text-center text-2xl text-zinc-600">Quiet shop</p>
            <button
                v-for="row in broken"
                :key="row.reservation.id"
                type="button"
                class="w-full rounded-2xl bg-rose-950 p-4 text-left"
                @click="openTicket(row.reservation)"
            >
                <div class="text-2xl font-bold">{{ row.reservation.customer }}</div>
                <div class="text-rose-300">{{ row.flags.join(' · ') }}</div>
            </button>
        </div>
        <aside v-if="focus" class="border-t border-rose-900 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-zinc-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-zinc-950" @click="desk.markReturned(focus)">Return</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="desk.takeCash(focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="desk.acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
