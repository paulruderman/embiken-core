<script setup lang="ts">
/**
 * PROTOTYPE Variant N — Waterfall. Tickets stacked by start; indent is the first free overlap lane.
 * Finished tickets free their column so later ones wrap back left — not a chain of margins.
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

const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const ranked = computed(() => {
    const rows = [...openReservations(shop)].sort(
        (left, right) => hourOf(left.starts) - hourOf(right.starts),
    );
    const placed: { reservation: Reservation; lane: number }[] = [];

    for (const reservation of rows) {
        const start = hourOf(reservation.starts);
        const used = new Set(
            placed
                .filter((other) => hourOf(other.reservation.ends) > start)
                .map((other) => other.lane),
        );
        let lane = 0;

        while (used.has(lane)) {
            lane++;
        }

        placed.push({ reservation, lane });
    }

    return placed;
});

const cascadeColumns = computed(() => {
    const lanes = Math.max(0, ...ranked.value.map((row) => row.lane));

    return `repeat(${lanes}, 2.75rem) minmax(0, 1fr)`;
});

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
    <div class="flex min-h-screen flex-col bg-orange-950 pb-24 text-orange-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Waterfall</span>
            <span class="font-mono text-xs">ended tickets wrap back</span>
            <span class="ml-auto text-sm text-amber-200">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-orange-400 px-4 font-bold text-orange-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="flex-1 overflow-auto px-3">
            <div class="grid gap-y-2" :style="{ gridTemplateColumns: cascadeColumns }">
                <button
                    v-for="row in ranked"
                    :key="row.reservation.id"
                    type="button"
                    class="h-20 min-w-0 rounded-2xl bg-orange-800 px-4 text-left"
                    :style="{ gridColumn: `${row.lane + 1} / -1` }"
                    @click="focusId = row.reservation.id"
                >
                    <div class="truncate text-xl font-bold">{{ row.reservation.customer }}</div>
                    <div class="truncate text-sm">
                        {{ row.reservation.starts }}–{{ row.reservation.ends }} ·
                        {{ row.reservation.lines.length }} lines
                    </div>
                </button>
            </div>
        </div>
        <aside v-if="focus" class="border-t border-orange-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <span v-for="line in focus.lines" :key="line.id" class="rounded-full bg-orange-900 px-3 py-1">
                    {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? '—' }}
                </span>
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-orange-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-orange-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-orange-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-orange-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-400 text-orange-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-orange-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-orange-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-orange-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
