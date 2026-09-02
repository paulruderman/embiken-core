<script setup lang="ts">
/**
 * PROTOTYPE Variant P — Model staves. Each BikeModel is a musical staff of time.
 * Timeline of the class, not the serial — quoteOccupancy remaining, visually.
 */
import { computed, reactive, ref } from 'vue';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    acceptWaiver,
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
    type Reservation,
} from './mock';

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const models = computed(() => [...new Set(shop.bikes.map((bike) => bike.model))]);

function onStaff(model: string): Reservation[] {
    return openReservations(shop).filter((reservation) =>
        reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.model === model),
    );
}

function col(stamp: string): number {
    return Math.min(18, Math.max(8, hourOf(stamp))) - 7;
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
    <div class="flex min-h-screen flex-col bg-rose-950 pb-24 text-rose-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Staves</span>
            <span class="font-mono text-xs">model × time</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-rose-400 px-4 font-bold text-rose-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex-1 space-y-3 overflow-auto px-3">
            <section v-for="model in models" :key="model">
                <h2 class="text-sm font-bold tracking-widest uppercase">{{ model }}</h2>
                <div
                    class="relative grid h-16 rounded-xl bg-rose-900"
                    :style="{ gridTemplateColumns: `repeat(${hours.length}, minmax(0, 1fr))` }"
                >
                    <button
                        v-for="reservation in onStaff(model)"
                        :key="reservation.id"
                        type="button"
                        class="z-10 my-2 rounded-md bg-rose-400 text-xs font-bold text-rose-950"
                        :style="{ gridColumn: `${col(reservation.starts)} / ${Math.max(col(reservation.starts) + 1, col(reservation.ends))}` }"
                        @click="focusId = reservation.id"
                    >
                        {{ reservation.customer.split(' ')[0] }}
                    </button>
                </div>
            </section>
        </div>
        <aside v-if="focus" class="border-t border-rose-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-rose-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-rose-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-rose-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-rose-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-rose-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-rose-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-950" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
