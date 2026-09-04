<script setup lang="ts">
/**
 * PROTOTYPE Variant K — Place settings. Each reservation is a table; each line is a cover.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeCaption,
    bikeFor,
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
    <div class="flex min-h-screen flex-col bg-amber-950 pb-24 text-amber-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Covers</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-amber-400 px-4 font-bold text-amber-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="grid min-h-0 flex-1 grid-cols-2 gap-3 overflow-auto p-3 lg:grid-cols-3">
            <button
                v-for="reservation in openReservations(shop)"
                :key="reservation.id"
                type="button"
                class="rounded-3xl bg-amber-900 p-4 text-left"
                @click="openTicket(reservation)"
            >
                <div class="text-xl font-bold">{{ reservation.customer }}</div>
                <div class="mb-3 text-sm text-amber-400">{{ reservation.starts }}–{{ reservation.ends }}</div>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="line in reservation.lines"
                        :key="line.id"
                        class="flex h-16 w-16 flex-col items-center justify-center rounded-full border-2 border-amber-200 text-center text-[10px] leading-tight"
                        :class="line.bike_id ? 'bg-amber-700' : 'border-dashed bg-amber-950'"
                    >
                        <span class="font-bold">{{ bikeFor(shop, line.bike_id)?.bid ?? 'empty' }}</span>
                        <span v-if="bikeFor(shop, line.bike_id)">{{
                            bikeCaption(shop, bikeFor(shop, line.bike_id)!)
                        }}</span>
                    </div>
                </div>
            </button>
        </div>
        <aside v-if="focus" class="border-t border-amber-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-amber-900" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-amber-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-amber-950" @click="desk.markReturned(focus)">Return</button>
                <button type="button" class="h-14 rounded-xl bg-amber-900" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="desk.takeCash(focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-amber-900" @click="desk.acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-amber-900" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
