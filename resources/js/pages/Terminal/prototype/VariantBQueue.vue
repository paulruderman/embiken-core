<script setup lang="ts">
/**
 * PROTOTYPE Variant B — Ticket queue is home. Full-screen stack (queue → ticket → floor).
 * Board is a drill-in, not always on. More screens, reservation-first.
 */
import { computed, reactive, ref } from 'vue';
import {
    acceptWaiver,
    assignBike,
    bikeFor,
    cancelReservation,
    createShop,
    extendReservation,
    markReturned,
    money,
    openReservations,
    pickup,
    startWalkIn,
    takeCash,
    type Bike,
    type Reservation,
} from './mock';

type Screen = 'queue' | 'ticket' | 'floor' | 'extend' | 'cancel';

const shop = reactive(createShop());
const screen = ref<Screen>('queue');
const focusId = ref<number | null>(null);
const toast = ref('');
const extendChoice = ref<'keep' | 'requote' | null>(null);

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => {
    if (screen.value === 'queue') {
        return 'queue';
    }

    if (screen.value === 'ticket') {
        return 'queue › ticket';
    }

    return `queue › ticket › ${screen.value}`;
});

function openTicket(reservation: Reservation): void {
    focusId.value = reservation.id;
    screen.value = 'ticket';
}

function back(): void {
    if (screen.value === 'ticket') {
        screen.value = 'queue';
        return;
    }

    screen.value = 'ticket';
}

function flash(message: string): void {
    toast.value = message;
}

function walkIn(): void {
    openTicket(startWalkIn(shop));
}

function doPickup(reservation: Reservation): void {
    pickup(shop, reservation);
    flash('Picked up');
}

function doReturn(reservation: Reservation): void {
    markReturned(shop, reservation, 'back');
    flash('Returned → back. Put-away later.');
}

function doCash(reservation: Reservation): void {
    takeCash(shop, reservation);
    flash('Cash recorded');
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (!reservation) {
        return;
    }

    extendChoice.value = requote ? 'requote' : 'keep';
    screen.value = 'ticket';
    flash(`Now ${reservation.starts}–${reservation.ends}, owed ${money(reservation.owed)}`);
}

function confirmCancel(reservation: Reservation): void {
    const out = reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out');

    if (out) {
        flash('Out bikes still out. Return them from the ticket.');
        screen.value = 'ticket';
        return;
    }

    cancelReservation(shop, reservation);
    screen.value = 'queue';
    focusId.value = null;
}

function pickBike(bike: Bike): void {
    if (!focus.value) {
        return;
    }

    assignBike(shop, focus.value, bike.id);
    screen.value = 'ticket';
    flash(`Assigned ${bike.bid}`);
}

function dueBadge(reservation: Reservation): string {
    if (reservation.owed !== reservation.paid) {
        return `DUE ${money(reservation.owed - reservation.paid)}`;
    }

    return reservation.stage;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-200 pb-24 text-stone-900">
        <header class="flex items-center gap-3 bg-stone-800 px-4 py-3 text-stone-100">
            <button
                v-if="screen !== 'queue'"
                type="button"
                class="h-14 min-w-20 rounded-xl bg-stone-600 px-4 text-lg"
                @click="back"
            >
                Back
            </button>
            <div class="text-lg font-semibold">{{ screen === 'queue' ? 'Today' : screen }}</div>
            <div class="rounded-full bg-stone-700 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-200">{{ toast }}</div>
        </header>

        <main v-if="screen === 'queue'" class="flex-1 p-4">
            <button
                type="button"
                class="mb-4 h-20 w-full rounded-2xl bg-emerald-600 text-2xl font-semibold text-white"
                @click="walkIn"
            >
                New walk-in
            </button>
            <div class="flex flex-col gap-3">
                <button
                    v-for="reservation in openReservations(shop)"
                    :key="reservation.id"
                    type="button"
                    class="flex min-h-24 items-center justify-between rounded-2xl bg-amber-50 px-5 text-left shadow"
                    @click="openTicket(reservation)"
                >
                    <div>
                        <div class="text-2xl font-bold">{{ reservation.customer }}</div>
                        <div class="text-sm text-stone-600">
                            {{ reservation.starts }}–{{ reservation.ends }}
                            ·
                            {{
                                reservation.lines
                                    .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                    .join(' ')
                            }}
                        </div>
                    </div>
                    <div
                        class="rounded-xl px-3 py-2 text-lg font-semibold"
                        :class="
                            reservation.owed !== reservation.paid
                                ? 'bg-red-600 text-white'
                                : 'bg-stone-800 text-white'
                        "
                    >
                        {{ dueBadge(reservation) }}
                    </div>
                </button>
            </div>
        </main>

        <main v-else-if="screen === 'ticket' && focus" class="flex flex-1 flex-col gap-4 p-4">
            <h1 class="text-4xl font-bold">{{ focus.customer }}</h1>
            <p class="text-4xl font-bold">{{ focus.starts }}–{{ focus.ends }}</p>
            <p class="text-3xl font-semibold text-red-700">
                owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
            </p>
            <p class="text-lg">
                {{ focus.stage }} ·
                {{ focus.lines.map((line) => bikeFor(shop, line.bike_id)?.bid ?? 'unassigned').join(', ') }}
            </p>
            <div class="mt-auto grid grid-cols-2 gap-3">
                <button type="button" class="h-24 rounded-2xl bg-stone-800 text-2xl text-white" @click="screen = 'floor'">
                    Assign / swap
                </button>
                <button type="button" class="h-24 rounded-2xl bg-sky-700 text-2xl text-white" @click="doPickup(focus)">
                    Pickup
                </button>
                <button type="button" class="h-24 rounded-2xl bg-orange-500 text-2xl" @click="doReturn(focus)">
                    Return
                </button>
                <button type="button" class="h-24 rounded-2xl bg-stone-800 text-2xl text-white" @click="screen = 'extend'">
                    Extend
                </button>
                <button type="button" class="h-24 rounded-2xl bg-emerald-700 text-2xl text-white" @click="doCash(focus)">
                    Cash / other
                </button>
                <button type="button" class="h-24 rounded-2xl bg-stone-800 text-2xl text-white" @click="acceptWaiver(focus)">
                    {{ focus.waiver ? 'Waiver ✓' : 'Waiver' }}
                </button>
                <button type="button" class="h-24 rounded-2xl bg-stone-800 text-2xl text-white" @click="flash(focus.myrental)">
                    Reveal URL
                </button>
                <button type="button" class="h-24 rounded-2xl bg-red-800 text-2xl text-white" @click="screen = 'cancel'">
                    Cancel
                </button>
            </div>
        </main>

        <main v-else-if="screen === 'floor'" class="flex-1 p-4">
            <p class="mb-3 text-lg">Home bikes — tap to assign</p>
            <div class="grid grid-cols-3 gap-3">
                <button
                    v-for="bike in shop.bikes"
                    :key="bike.id"
                    type="button"
                    class="h-24 rounded-2xl text-2xl font-bold"
                    :class="
                        bike.situation === 'home' && bike.in_service
                            ? 'bg-stone-800 text-white'
                            : 'bg-stone-400 text-stone-600'
                    "
                    :disabled="!(bike.situation === 'home' && bike.in_service)"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                    <span class="block text-xs font-normal">{{ bike.situation }}</span>
                </button>
            </div>
        </main>

        <main v-else-if="screen === 'extend' && focus" class="flex flex-1 flex-col gap-4 p-6">
            <h1 class="text-3xl font-bold">Extend {{ focus.customer }}</h1>
            <p>Keep owed, or requote through the new ends_at.</p>
            <button
                type="button"
                class="h-24 rounded-2xl bg-stone-800 text-2xl text-white"
                @click="doExtend(false)"
            >
                Keep owed
            </button>
            <button
                type="button"
                class="h-24 rounded-2xl bg-amber-500 text-2xl"
                @click="doExtend(true)"
            >
                Requote
            </button>
        </main>

        <main v-else-if="screen === 'cancel' && focus" class="flex flex-1 flex-col gap-4 p-6">
            <h1 class="text-3xl font-bold">Cancel {{ focus.customer }}?</h1>
            <p>Out bikes are not auto-returned. Confirm they are in the shop first.</p>
            <button
                type="button"
                class="h-24 rounded-2xl bg-red-800 text-2xl text-white"
                @click="confirmCancel(focus)"
            >
                Cancel reservation
            </button>
        </main>
    </div>
</template>
