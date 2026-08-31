<script setup lang="ts">
/**
 * PROTOTYPE Variant D — Day is a time ribbon. Tickets are bars on a schedule.
 * Always on: when. Bike floor is not the home. Drawer for the tap.
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

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() =>
    focus.value ? `timeline › ${pane.value}` : 'timeline',
);

function col(stamp: string): number {
    const hour = Math.min(18, Math.max(8, hourOf(stamp)));

    return hour - 7;
}

function barStyle(reservation: Reservation): Record<string, string> {
    const start = col(reservation.starts);
    const end = Math.max(start + 1, col(reservation.ends));

    return {
        gridColumn: `${start} / ${end}`,
    };
}

function flash(message: string): void {
    toast.value = message;
}

function openTicket(reservation: Reservation): void {
    focusId.value = reservation.id;
    pane.value = 'ticket';
}

function walkIn(): void {
    openTicket(startWalkIn(shop));
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (!reservation) {
        return;
    }

    pane.value = 'ticket';
    flash(`Now ${reservation.starts}–${reservation.ends}, owed ${money(reservation.owed)}`);
}

function pickBike(bike: Bike): void {
    if (!focus.value) {
        return;
    }

    assignBike(shop, focus.value, bike.id);
    pane.value = 'ticket';
    flash(`Assigned ${bike.bid}`);
}

function doCancel(reservation: Reservation): void {
    const out = reservation.lines.some(
        (line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out',
    );

    if (out) {
        flash('Out bikes — return first.');
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-900 pb-24 text-slate-100">
        <header class="flex items-center gap-3 px-4 py-3">
            <div class="text-lg font-semibold">Today</div>
            <div class="rounded-full bg-slate-800 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-300">{{ toast }}</div>
            <button
                type="button"
                class="h-16 min-w-32 rounded-2xl bg-cyan-400 px-5 text-lg font-semibold text-slate-950"
                @click="walkIn"
            >
                Walk-in
            </button>
        </header>

        <div class="min-h-0 flex-1 overflow-auto px-4">
            <div
                class="mb-2 grid text-center text-xs text-slate-500"
                :style="{ gridTemplateColumns: `repeat(${hours.length}, minmax(0, 1fr))` }"
            >
                <span v-for="hour in hours" :key="hour">{{ hour }}:00</span>
            </div>
            <button
                v-for="reservation in openReservations(shop)"
                :key="reservation.id"
                type="button"
                class="relative mb-2 grid h-20 w-full rounded-xl bg-slate-800"
                :style="{ gridTemplateColumns: `repeat(${hours.length}, minmax(0, 1fr))` }"
                @click="openTicket(reservation)"
            >
                <div
                    class="flex items-center rounded-xl bg-cyan-700 px-3 text-left"
                    :class="focusId === reservation.id ? 'ring-2 ring-cyan-300' : ''"
                    :style="barStyle(reservation)"
                >
                    <div>
                        <div class="text-lg font-bold">{{ reservation.customer }}</div>
                        <div class="text-xs">
                            {{ reservation.starts }}–{{ reservation.ends }}
                            ·
                            {{
                                reservation.lines
                                    .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                    .join(' ')
                            }}
                            · owed {{ money(reservation.owed) }}
                        </div>
                    </div>
                </div>
            </button>
        </div>

        <aside
            v-if="focus"
            class="h-[42vh] overflow-auto border-t border-slate-700 bg-slate-950 p-4"
        >
            <div class="mb-3 flex items-start justify-between">
                <div>
                    <div class="text-2xl font-semibold">{{ focus.customer }}</div>
                    <div class="text-3xl font-bold">{{ focus.starts }}–{{ focus.ends }}</div>
                    <div class="text-xl text-amber-300">
                        owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
                    </div>
                </div>
                <button type="button" class="h-14 w-14 rounded-2xl bg-slate-800" @click="focusId = null">
                    ✕
                </button>
            </div>

            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-slate-700 text-lg" @click="doExtend(false)">
                    Keep owed
                </button>
                <button
                    type="button"
                    class="h-16 rounded-2xl bg-amber-400 text-lg text-slate-950"
                    @click="doExtend(true)"
                >
                    Requote
                </button>
            </div>
            <div v-else-if="pane === 'assign'" class="grid grid-cols-4 gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-16 rounded-2xl bg-slate-700 text-xl font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-slate-800" @click="pane = 'assign'">
                    Assign
                </button>
                <button type="button" class="h-16 rounded-2xl bg-sky-700" @click="pickup(shop, focus)">
                    Pickup
                </button>
                <button
                    type="button"
                    class="h-16 rounded-2xl bg-orange-500 text-slate-950"
                    @click="markReturned(shop, focus, 'back')"
                >
                    Return
                </button>
                <button type="button" class="h-16 rounded-2xl bg-slate-800" @click="pane = 'extend'">
                    Extend
                </button>
                <button type="button" class="h-16 rounded-2xl bg-emerald-700" @click="takeCash(shop, focus)">
                    Cash
                </button>
                <button type="button" class="h-16 rounded-2xl bg-slate-800" @click="acceptWaiver(focus)">
                    Waiver
                </button>
                <button type="button" class="h-16 rounded-2xl bg-slate-800" @click="flash(focus.myrental)">
                    URL
                </button>
                <button type="button" class="h-16 rounded-2xl bg-rose-900" @click="doCancel(focus)">
                    Cancel
                </button>
            </div>
        </aside>
    </div>
</template>
