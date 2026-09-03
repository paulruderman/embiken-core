<script setup lang="ts">
/**
 * PROTOTYPE Variant G — Bike-lane Gantt. Time on X, each bid a row.
 * Timeline they liked, fused with occupancy. Hotel rack / resource schedule.
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

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
const shop = useShopFloor();
const desk = useTerminalDesk();
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => (focus.value ? `lanes › ${pane.value}` : 'lanes'));

function col(stamp: string): number {
    return Math.min(18, Math.max(8, hourOf(stamp))) - 7;
}

function barStyle(reservation: Reservation): Record<string, string> {
    const start = col(reservation.starts);
    const end = Math.max(start + 1, col(reservation.ends));

    return { gridColumn: `${start + 1} / ${end + 1}` };
}

function flash(message: string): void {
    toast.value = message;
}

function openTicket(reservation: Reservation): void {
    focusId.value = reservation.id;
    pane.value = 'ticket';
}

function tapLane(bike: Bike): void {
    const reservation = reservationForBike(shop, bike.id);

    if (reservation) {
        openTicket(reservation);
        return;
    }

    flash(`${bike.bid} idle — Walk-in to occupy`);
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

    if (!reservation) {
        return;
    }

    pane.value = 'ticket';
    flash(`Now ${reservation.starts}–${reservation.ends}, owed ${money(reservation.owed)}`);
}


function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        flash('Out bikes — return first.');
        return;
    }

    desk.cancelReservation(reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-indigo-950 pb-24 text-indigo-50">
        <header class="flex items-center gap-3 px-3 py-2">
            <div class="text-lg font-semibold">Lanes</div>
            <div class="rounded-full bg-indigo-900 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-300">{{ toast }}</div>
            <button
                type="button"
                class="h-14 rounded-2xl bg-violet-400 px-5 text-lg font-semibold text-indigo-950"
                @click="walkIn"
            >
                Walk-in
            </button>
        </header>

        <div class="min-h-0 flex-1 overflow-auto px-2">
            <div
                class="mb-1 ml-14 grid text-center text-[10px] text-indigo-400"
                :style="{ gridTemplateColumns: `repeat(${hours.length}, minmax(2.5rem, 1fr))` }"
            >
                <span v-for="hour in hours" :key="hour">{{ hour }}</span>
            </div>
            <div v-for="bike in shop.bikes" :key="bike.id" class="mb-1 flex items-stretch gap-1">
                <button
                    type="button"
                    class="flex h-14 w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-indigo-900 text-sm font-bold"
                    @click="tapLane(bike)"
                >
                    <span>{{ bike.bid }}</span>
                    <span class="w-full truncate px-0.5 text-[9px] font-normal opacity-80">{{
                        bikeCaption(shop, bike)
                    }}</span>
                </button>
                <div
                    class="relative grid h-12 flex-1 rounded-lg bg-indigo-900/60"
                    :style="{ gridTemplateColumns: `repeat(${hours.length}, minmax(2.5rem, 1fr))` }"
                >
                    <button
                        v-if="reservationForBike(shop, bike.id)"
                        type="button"
                        class="z-10 mx-0.5 my-1 rounded-md bg-violet-500 text-left text-xs font-semibold"
                        :style="barStyle(reservationForBike(shop, bike.id)!)"
                        @click="openTicket(reservationForBike(shop, bike.id)!)"
                    >
                        <span class="block truncate px-1">
                            {{ reservationForBike(shop, bike.id)?.customer }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <aside v-if="focus" class="border-t border-indigo-800 bg-indigo-950 p-3">
            <div class="mb-2 text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl font-bold">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-3 text-xl text-amber-300">
                owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
            </div>
            <PrototypePartyLines class="mb-3" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-indigo-800" @click="doExtend(false)">
                    Keep owed
                </button>
                <button type="button" class="h-16 rounded-2xl bg-amber-400 text-indigo-950" @click="doExtend(true)">
                    Requote
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-indigo-950" @click="desk.markReturned(focus)">
                    Return
                </button>
                <button type="button" class="h-14 rounded-xl bg-indigo-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="desk.takeCash(focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-indigo-800" @click="desk.acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-indigo-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
