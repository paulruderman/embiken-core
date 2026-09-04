<script setup lang="ts">
/**
 * PROTOTYPE Variant B — Ticket queue is home. Full-screen stack (queue → ticket).
 * Floor is not a screen; Assign/Swap are on the ticket lines.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeFor,
    money,
    openReservations,
    type Reservation,
} from './mock';

type Screen = 'queue' | 'ticket' | 'extend' | 'cancel';

const shop = useShopFloor();
const desk = useTerminalDesk();
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
    desk.startWalkIn((id) => {
        const created = shop.reservations.find((item) => item.id === id);
        if (created) {
            openTicket(created);
        }
    });
}

function doPickup(reservation: Reservation): void {
    desk.pickup(reservation);
    flash('Picked up');
}

function doReturn(reservation: Reservation): void {
    desk.markReturned(reservation);
    flash('Returned → back. Put-away later.');
}

function doCash(reservation: Reservation): void {
    desk.takeCash(reservation);
    flash('Cash recorded');
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

    desk.cancelReservation(reservation);
    screen.value = 'queue';
    focusId.value = null;
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
            <p class="text-lg">{{ focus.stage }}</p>
            <PrototypePartyLines :shop="shop" :reservation="focus" />
            <div class="mt-auto grid grid-cols-2 gap-3">
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
                <button type="button" class="h-24 rounded-2xl bg-stone-800 text-2xl text-white" @click="desk.acceptWaiver(focus)">
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
