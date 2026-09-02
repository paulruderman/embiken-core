<script setup lang="ts">
/**
 * PROTOTYPE Variant E — Split: bikes always left, ticket always right.
 * No overlay, no full-screen stack. Both panes stay. Empty right until a tap.
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
const focusId = ref<number | null>(101);
const mode = ref<'actions' | 'extend'>('actions');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => `split · ${focus.value ? 'ticket' : 'empty'}`);

function flash(message: string): void {
    toast.value = message;
}

function openBike(bike: Bike): void {
    const reservation = reservationForBike(shop, bike.id);

    if (reservation) {
        focusId.value = reservation.id;
        mode.value = 'actions';
        return;
    }

    flash(`${bike.bid} has no ticket`);
}

function walkIn(): void {
    const reservation = startWalkIn(shop);
    focusId.value = reservation.id;
    mode.value = 'actions';
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (!reservation) {
        return;
    }

    mode.value = 'actions';
    flash(`Now ${reservation.starts}–${reservation.ends}, owed ${money(reservation.owed)}`);
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

function tileClass(bike: Bike): string {
    if (!bike.in_service) {
        return 'bg-neutral-300 text-neutral-500';
    }

    return {
        home: 'bg-white text-neutral-900',
        prepping: 'bg-amber-300',
        staged: 'bg-sky-300',
        rented_out: 'bg-rose-400 text-white',
        back: 'bg-orange-300',
    }[bike.situation];
}
</script>

<template>
    <div class="flex min-h-screen bg-neutral-100 pb-24 text-neutral-900">
        <section class="flex w-[42%] flex-col border-r border-neutral-300 p-3">
            <div class="mb-2 flex items-center justify-between">
                <h1 class="text-xl font-semibold">Bikes</h1>
                <span class="font-mono text-xs text-neutral-500">{{ stack }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 overflow-auto">
                <button
                    v-for="bike in shop.bikes"
                    :key="bike.id"
                    type="button"
                    class="flex h-20 flex-col items-start justify-center rounded-2xl px-3 text-left shadow-sm"
                    :class="tileClass(bike)"
                    @click="openBike(bike)"
                >
                    <span class="text-2xl font-bold">{{ bike.bid }}</span>
                    <span class="text-xs">{{ bikeCaption(shop, bike) }}</span>
                </button>
            </div>
        </section>

        <section class="flex min-w-0 flex-1 flex-col p-4">
            <div class="mb-3 flex items-center gap-3">
                <button
                    type="button"
                    class="h-16 rounded-2xl bg-emerald-600 px-6 text-lg font-semibold text-white"
                    @click="walkIn"
                >
                    Walk-in
                </button>
                <div class="text-sm text-amber-700">{{ toast }}</div>
            </div>

            <div v-if="!focus" class="flex flex-1 items-center text-2xl text-neutral-400">
                Tap a bike. Ticket stays on this side.
            </div>
            <div v-else class="flex flex-1 flex-col">
                <h2 class="text-3xl font-bold">{{ focus.customer }}</h2>
                <p class="text-4xl font-bold">{{ focus.starts }}–{{ focus.ends }}</p>
                <p class="text-2xl font-semibold text-red-700">
                    owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
                </p>
                <p class="mb-2 text-neutral-500">{{ focus.stage }}</p>
                <PrototypePartyLines class="mb-4" :shop="shop" :reservation="focus" />
                <div v-if="mode === 'extend'" class="mt-auto grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="h-20 rounded-2xl bg-neutral-800 text-2xl text-white"
                        @click="doExtend(false)"
                    >
                        Keep owed
                    </button>
                    <button
                        type="button"
                        class="h-20 rounded-2xl bg-amber-400 text-2xl"
                        @click="doExtend(true)"
                    >
                        Requote
                    </button>
                </div>
                <div v-else class="mt-auto grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-sky-700 text-lg text-white"
                        @click="pickup(shop, focus)"
                    >
                        Pickup
                    </button>
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-orange-400 text-lg"
                        @click="markReturned(shop, focus, 'back')"
                    >
                        Return
                    </button>
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-neutral-800 text-lg text-white"
                        @click="mode = 'extend'"
                    >
                        Extend
                    </button>
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-emerald-700 text-lg text-white"
                        @click="takeCash(shop, focus)"
                    >
                        Cash
                    </button>
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-neutral-800 text-lg text-white"
                        @click="acceptWaiver(focus)"
                    >
                        Waiver
                    </button>
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-neutral-800 text-lg text-white"
                        @click="flash(focus.myrental)"
                    >
                        URL
                    </button>
                    <button
                        type="button"
                        class="h-16 rounded-2xl bg-red-800 text-lg text-white"
                        @click="doCancel(focus)"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
