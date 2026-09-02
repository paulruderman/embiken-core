<script setup lang="ts">
/**
 * PROTOTYPE Variant M — Counter vs floor. Two jobs, two columns.
 */
import { computed, reactive, ref } from 'vue';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    acceptWaiver,
    bikeFor,
    cancelReservation,
    createShop,
    extendReservation,
    markReturned,
    money,
    moneyLane,
    openReservations,
    pickup,
    startWalkIn,
    takeCash,
    type Reservation,
} from './mock';

const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

function onCounter(reservation: Reservation): boolean {
    return (
        moneyLane(reservation) !== 'settled' ||
        !reservation.waiver ||
        reservation.lines.some((line) => line.bike_id === null) ||
        reservation.stage === 'Provisional'
    );
}

function onFloor(reservation: Reservation): boolean {
    return reservation.lines.some((line) => {
        const situation = bikeFor(shop, line.bike_id)?.situation;

        return situation === 'prepping' || situation === 'staged' || situation === 'rented_out' || situation === 'back';
    });
}

const counter = computed(() => openReservations(shop).filter(onCounter));
const floor = computed(() => openReservations(shop).filter(onFloor));

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

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-950 pb-24 text-slate-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Roles</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-slate-200 px-4 font-bold text-slate-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="grid min-h-0 flex-1 grid-cols-2 gap-3 px-3">
            <section class="overflow-auto rounded-3xl bg-slate-900 p-3">
                <h2 class="mb-2 text-center text-2xl font-black">COUNTER</h2>
                <p class="mb-2 text-center text-xs text-slate-500">Pay, waiver, assign, provisional</p>
                <button
                    v-for="reservation in counter"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 w-full rounded-2xl bg-slate-800 p-3 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-xl font-bold">{{ reservation.customer }}</div>
                    <div class="text-slate-400">{{ moneyLane(reservation) }} · waiver {{ reservation.waiver ? 'yes' : 'NO' }}</div>
                </button>
            </section>
            <section class="overflow-auto rounded-3xl bg-orange-950 p-3">
                <h2 class="mb-2 text-center text-2xl font-black">FLOOR</h2>
                <p class="mb-2 text-center text-xs text-orange-400">Prep, stage, out, back</p>
                <button
                    v-for="reservation in floor"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 w-full rounded-2xl bg-orange-900 p-3 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-xl font-bold">{{ reservation.customer }}</div>
                    <div class="text-orange-200">
                        {{
                            reservation.lines
                                .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                .join(' ')
                        }}
                    </div>
                </button>
            </section>
        </div>
        <aside v-if="focus" class="border-t border-slate-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-slate-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-slate-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-slate-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-slate-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-slate-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-slate-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
