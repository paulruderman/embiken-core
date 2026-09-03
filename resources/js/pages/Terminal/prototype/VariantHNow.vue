<script setup lang="ts">
/**
 * PROTOTYPE Variant H — Now playhead. The day is not equal hours; NOW is the spine.
 * Left = should already have happened. Center = in the hour. Right = later.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeFor,
    hourOf,
    money,
    openReservations,
    type Reservation,
} from './mock';

const NOW = 12;
const shop = useShopFloor();
const desk = useTerminalDesk();
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => (focus.value ? `now › ${pane.value}` : 'now'));

function startH(reservation: Reservation): number {
    return hourOf(reservation.starts);
}

function endH(reservation: Reservation): number {
    return hourOf(reservation.ends);
}

const overdue = computed(() =>
    openReservations(shop).filter((reservation) => endH(reservation) <= NOW),
);
const current = computed(() =>
    openReservations(shop).filter(
        (reservation) => startH(reservation) <= NOW && endH(reservation) > NOW,
    ),
);
const later = computed(() =>
    openReservations(shop).filter((reservation) => startH(reservation) > NOW),
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
    <div class="flex min-h-screen flex-col bg-zinc-900 pb-24 text-zinc-100">
        <header class="flex items-center gap-3 px-3 py-2">
            <div class="text-lg font-semibold">Now {{ NOW }}:00</div>
            <div class="rounded-full bg-zinc-800 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-300">{{ toast }}</div>
            <button
                type="button"
                class="h-14 rounded-2xl bg-red-500 px-5 text-lg font-semibold"
                @click="walkIn"
            >
                Walk-in
            </button>
        </header>

        <div class="grid min-h-0 flex-1 grid-cols-[1fr_auto_1fr] gap-2 px-2">
            <section class="overflow-auto rounded-2xl bg-zinc-950 p-2">
                <h2 class="mb-2 text-xs tracking-widest text-red-400 uppercase">Behind</h2>
                <button
                    v-for="reservation in overdue"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 w-full rounded-2xl bg-red-950 p-3 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-xl font-bold">{{ reservation.customer }}</div>
                    <div class="text-sm text-red-300">ended {{ reservation.ends }}</div>
                    <div class="text-lg">owed {{ money(reservation.owed) }}</div>
                </button>
            </section>

            <section class="flex w-44 flex-col rounded-2xl bg-red-600 p-2 text-white">
                <h2 class="mb-2 text-center text-xs tracking-widest uppercase">Live</h2>
                <button
                    v-for="reservation in current"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 min-h-24 rounded-2xl bg-red-800 p-3 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-lg font-bold">{{ reservation.customer }}</div>
                    <div class="text-sm">{{ reservation.starts }}–{{ reservation.ends }}</div>
                </button>
            </section>

            <section class="overflow-auto rounded-2xl bg-zinc-950 p-2">
                <h2 class="mb-2 text-xs tracking-widest text-zinc-500 uppercase">Later</h2>
                <button
                    v-for="reservation in later"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 w-full rounded-2xl bg-zinc-800 p-3 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-xl font-bold">{{ reservation.customer }}</div>
                    <div class="text-sm text-zinc-400">starts {{ reservation.starts }}</div>
                    <div class="text-lg">owed {{ money(reservation.owed) }}</div>
                </button>
            </section>
        </div>

        <aside v-if="focus" class="border-t border-zinc-700 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl font-bold">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-3 text-xl text-amber-300">
                owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
            </div>
            <PrototypePartyLines class="mb-3" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-zinc-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-16 rounded-2xl bg-amber-400 text-zinc-950" @click="doExtend(true)">
                    Requote
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-zinc-950" @click="desk.markReturned(focus)">
                    Return
                </button>
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="desk.takeCash(focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="desk.acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-zinc-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
