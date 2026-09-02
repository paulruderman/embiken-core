<script setup lang="ts">
/**
 * PROTOTYPE Variant Q — Dutch door. Morning rush at the half-door:
 * only staged parties show in the window; you pass the bike through.
 */
import { computed, reactive, ref } from 'vue';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    acceptWaiver,
    bikeFor,
    cancelReservation,
    createShop,
    markReturned,
    money,
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

function isStaged(reservation: Reservation): boolean {
    return reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'staged');
}

const windowQueue = computed(() => openReservations(shop).filter(isStaged));
const behind = computed(() => openReservations(shop).filter((reservation) => !isStaged(reservation)));

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
    flash(requote ? 'Requote stub' : 'Keep owed stub');
    pane.value = 'ticket';
}

function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        flash('Return Out bikes first');
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}

function handOut(reservation: Reservation): void {
    pickup(shop, reservation);
    flash(`Through the door — ${reservation.customer}`);
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-yellow-100 pb-24 text-stone-900">
        <header class="flex items-center gap-2 bg-yellow-900 px-3 py-2 text-yellow-50">
            <span class="font-semibold">Dutch door</span>
            <span class="ml-auto text-sm text-amber-200">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-yellow-300 px-4 font-bold text-yellow-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="relative min-h-0 flex-1">
            <div class="absolute inset-0 bg-stone-800 p-3 opacity-40">
                <p class="mb-2 text-xs tracking-widest text-stone-400 uppercase">Behind the door</p>
                <button
                    v-for="reservation in behind"
                    :key="reservation.id"
                    type="button"
                    class="mb-1 block text-left text-stone-400"
                    @click="openTicket(reservation)"
                >
                    {{ reservation.customer }}
                </button>
            </div>
            <div class="relative mx-auto mt-8 w-[min(100%,28rem)] rounded-t-[3rem] border-8 border-yellow-950 bg-yellow-50 p-4 shadow-2xl">
                <p class="mb-2 text-center text-xs tracking-widest uppercase">Window — staged, waiting</p>
                <p v-if="windowQueue.length === 0" class="py-10 text-center text-stone-400">Nobody at the door</p>
                <button
                    v-for="reservation in windowQueue"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 w-full rounded-2xl bg-yellow-200 p-4 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-3xl font-black">{{ reservation.customer }}</div>
                    <div class="text-lg">{{ reservation.starts }} — tap ticket, or hand through</div>
                    <span
                        class="mt-2 inline-block rounded-full bg-yellow-900 px-4 py-2 text-lg font-bold text-yellow-50"
                        @click.stop="handOut(reservation)"
                    >
                        Hand through
                    </span>
                </button>
            </div>
        </div>
        <aside v-if="focus" class="border-t-4 border-yellow-950 bg-yellow-50 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-red-700">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <PrototypePartyLines class="mb-2" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-stone-800 text-white" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700 text-white" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800 text-white" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800 text-white" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800 text-white" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-stone-800 text-white" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900 text-white" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
