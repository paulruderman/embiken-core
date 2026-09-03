<script setup lang="ts">
/**
 * PROTOTYPE Variant J — Dual dock. The shop is two bays: OUT (leaving) and IN (coming back).
 * No calendar. Physical doors, not time.
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

const shop = useShopFloor();
const desk = useTerminalDesk();
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => (focus.value ? `docks › ${pane.value}` : 'docks'));

function situationOf(reservation: Reservation): string {
    return bikeFor(shop, reservation.lines[0]?.bike_id ?? null)?.situation ?? 'none';
}

const outbound = computed(() =>
    openReservations(shop).filter((reservation) =>
        ['home', 'prepping', 'staged'].includes(situationOf(reservation)),
    ),
);

const inbound = computed(() =>
    openReservations(shop).filter((reservation) =>
        ['rented_out', 'back'].includes(situationOf(reservation)),
    ),
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
    <div class="flex min-h-screen flex-col bg-teal-950 pb-24 text-teal-50">
        <header class="flex items-center gap-3 px-3 py-2">
            <div class="text-lg font-semibold">Docks</div>
            <div class="rounded-full bg-teal-900 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-300">{{ toast }}</div>
            <button
                type="button"
                class="h-14 rounded-2xl bg-teal-300 px-5 text-lg font-semibold text-teal-950"
                @click="walkIn"
            >
                Walk-in
            </button>
        </header>

        <div class="grid min-h-0 flex-1 grid-cols-2 gap-3 px-3">
            <section class="flex flex-col rounded-3xl bg-teal-900 p-3">
                <h2 class="mb-3 text-center text-2xl font-black tracking-tight">OUT</h2>
                <p class="mb-2 text-center text-xs text-teal-400">Prepping, staged, not yet gone</p>
                <button
                    v-for="reservation in outbound"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 min-h-24 rounded-2xl bg-teal-800 p-4 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-2xl font-bold">{{ reservation.customer }}</div>
                    <div class="text-lg">{{ reservation.starts }} leave</div>
                    <div class="text-teal-300">
                        {{
                            reservation.lines
                                .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                .join(' ')
                        }}
                        · owed {{ money(reservation.owed) }}
                    </div>
                </button>
            </section>
            <section class="flex flex-col rounded-3xl bg-cyan-950 p-3">
                <h2 class="mb-3 text-center text-2xl font-black tracking-tight">IN</h2>
                <p class="mb-2 text-center text-xs text-cyan-600">Out on the road, or back needing put-away</p>
                <button
                    v-for="reservation in inbound"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 min-h-24 rounded-2xl bg-cyan-900 p-4 text-left"
                    @click="openTicket(reservation)"
                >
                    <div class="text-2xl font-bold">{{ reservation.customer }}</div>
                    <div class="text-lg">{{ reservation.ends }} due</div>
                    <div class="text-cyan-300">
                        {{
                            reservation.lines
                                .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                .join(' ')
                        }}
                        · owed {{ money(reservation.owed) }}
                    </div>
                </button>
            </section>
        </div>

        <aside v-if="focus" class="border-t border-teal-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl font-bold">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-3 text-xl text-amber-300">
                owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
            </div>
            <PrototypePartyLines class="mb-3" :shop="shop" :reservation="focus" />
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-16 rounded-2xl bg-teal-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-16 rounded-2xl bg-amber-400 text-teal-950" @click="doExtend(true)">
                    Requote
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="desk.pickup(focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-teal-950" @click="desk.markReturned(focus)">
                    Return
                </button>
                <button type="button" class="h-14 rounded-xl bg-teal-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-700" @click="desk.takeCash(focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-teal-800" @click="desk.acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-teal-800" @click="flash(focus.myrental)">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
