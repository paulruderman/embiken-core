<script setup lang="ts">
/**
 * PROTOTYPE Variant I — Clock face. Hours around a ring; the well is the selected hour.
 * Timeline as a watch, not a spreadsheet.
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

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
const shop = reactive(createShop());
const selectedHour = ref(10);
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const inHour = computed(() =>
    openReservations(shop).filter((reservation) => hourOf(reservation.starts) === selectedHour.value),
);

const stack = computed(() =>
    focus.value ? `clock ${selectedHour.value} › ${pane.value}` : `clock ${selectedHour.value}`,
);

function wedgeStyle(hour: number): Record<string, string> {
    const index = hours.indexOf(hour);
    const angle = index * 30;

    return {
        transform: `rotate(${angle}deg) translateY(-10.5rem) rotate(${-angle}deg)`,
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
}

function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        flash('Out bikes — return first.');
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-950 pb-24 text-amber-100">
        <header class="flex items-center gap-3 px-3 py-2">
            <div class="text-lg font-semibold">Clock</div>
            <div class="rounded-full bg-stone-900 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-400">{{ toast }}</div>
            <button
                type="button"
                class="h-14 rounded-2xl bg-amber-400 px-5 text-lg font-semibold text-stone-950"
                @click="walkIn"
            >
                Walk-in
            </button>
        </header>

        <div class="flex min-h-0 flex-1 items-center justify-center">
            <div class="relative size-96">
                <div class="absolute inset-8 rounded-full border-4 border-amber-700" />
                <button
                    v-for="hour in hours"
                    :key="hour"
                    type="button"
                    class="absolute top-1/2 left-1/2 h-14 w-14 -translate-x-1/2 -translate-y-1/2 rounded-full text-lg font-bold"
                    :class="
                        selectedHour === hour ? 'bg-amber-400 text-stone-950' : 'bg-stone-800 text-amber-200'
                    "
                    :style="wedgeStyle(hour)"
                    @click="selectedHour = hour"
                >
                    {{ hour }}
                </button>
                <div class="absolute inset-0 flex flex-col items-center justify-center p-16 text-center">
                    <div class="text-sm text-amber-600">Starts {{ selectedHour }}:00</div>
                    <button
                        v-for="reservation in inHour"
                        :key="reservation.id"
                        type="button"
                        class="mt-2 max-w-36 rounded-xl bg-stone-800 px-3 py-2"
                        @click="openTicket(reservation)"
                    >
                        {{ reservation.customer }}
                    </button>
                    <div v-if="inHour.length === 0" class="text-amber-800">Quiet hour</div>
                </div>
            </div>
        </div>

        <aside v-if="focus" class="border-t border-amber-900 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl font-bold">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-3 text-xl text-amber-400">
                owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-stone-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-16 rounded-2xl bg-amber-400 text-stone-950" @click="doExtend(true)">
                    Requote
                </button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-stone-800 text-lg font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-800" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-stone-950" @click="markReturned(shop, focus, 'back')">
                    Return
                </button>
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
