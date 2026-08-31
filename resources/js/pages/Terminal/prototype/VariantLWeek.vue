<script setup lang="ts">
/**
 * PROTOTYPE Variant L — Week ribbon. Seven day rows; today is fat with hour bars.
 * Timeline as a diary week, not a single afternoon.
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

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const today = 'Wed';
const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const hourGrid = computed(
    () => `repeat(${hours.length}, minmax(0, 1fr))`,
);

function col(stamp: string): number {
    const hour = Math.min(18, Math.max(8, hourOf(stamp)));

    return hour - 7;
}

function barStyle(reservation: Reservation): Record<string, string> {
    const start = col(reservation.starts);
    const end = Math.min(hours.length + 1, Math.max(start + 1, col(reservation.ends)));

    return {
        gridColumn: `${start} / ${end}`,
    };
}

function walkIn(): void {
    focusId.value = startWalkIn(shop).id;
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (reservation) {
        pane.value = 'ticket';
        toast.value = `${reservation.starts}–${reservation.ends}`;
    }
}

function pickBike(bike: Bike): void {
    if (focus.value) {
        assignBike(shop, focus.value, bike.id);
        pane.value = 'ticket';
    }
}

function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        toast.value = 'Return Out bikes first';
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-emerald-950 pb-24 text-emerald-50">
        <header class="flex items-center gap-2 px-3 py-2">
            <span class="font-semibold">Week</span>
            <span class="font-mono text-xs">week › {{ today }}</span>
            <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            <button type="button" class="h-14 rounded-2xl bg-emerald-400 px-4 font-bold text-emerald-950" @click="walkIn">
                Walk-in
            </button>
        </header>
        <div class="min-h-0 flex-1 space-y-1 overflow-auto px-3">
            <div
                v-for="day in days"
                :key="day"
                class="rounded-2xl px-2 py-2"
                :class="day === today ? 'bg-emerald-800' : 'bg-emerald-900/50'"
            >
                <div class="flex items-start gap-2">
                    <span class="w-10 shrink-0 pt-1 text-sm font-bold">{{ day }}</span>
                    <div v-if="day === today" class="min-w-0 flex-1">
                        <div
                            class="mb-1 grid text-center text-[10px] text-emerald-300"
                            :style="{ gridTemplateColumns: hourGrid }"
                        >
                            <span v-for="hour in hours" :key="hour">{{ hour }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="reservation in openReservations(shop)"
                                :key="reservation.id"
                                class="grid h-10"
                                :style="{ gridTemplateColumns: hourGrid }"
                            >
                                <button
                                    type="button"
                                    class="truncate rounded-lg bg-emerald-400 px-2 text-left text-xs font-bold text-emerald-950"
                                    :style="barStyle(reservation)"
                                    @click="focusId = reservation.id"
                                >
                                    {{ reservation.customer }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="h-10 min-w-0 flex-1 rounded-lg bg-emerald-950/80 text-center text-xs leading-10 text-emerald-700"
                    >
                        {{ day === 'Sat' ? '6 holds' : 'quiet' }}
                    </div>
                </div>
            </div>
        </div>
        <aside v-if="focus" class="border-t border-emerald-800 p-3">
            <div class="text-2xl font-bold">{{ focus.customer }}</div>
            <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
            <div class="mb-2 text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <span v-for="line in focus.lines" :key="line.id" class="rounded-full bg-emerald-900 px-3 py-1">
                    {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? '—' }}
                </span>
            </div>
            <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="doExtend(false)">Keep owed</button>
                <button type="button" class="h-14 rounded-xl bg-amber-400 text-emerald-950" @click="doExtend(true)">Requote</button>
            </div>
            <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                <button
                    v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                    :key="bike.id"
                    type="button"
                    class="h-14 w-14 rounded-xl bg-emerald-800 font-bold"
                    @click="pickBike(bike)"
                >
                    {{ bike.bid }}
                </button>
            </div>
            <div v-else class="grid grid-cols-4 gap-2">
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="pane = 'assign'">Assign</button>
                <button type="button" class="h-14 rounded-xl bg-sky-700" @click="pickup(shop, focus)">Pickup</button>
                <button type="button" class="h-14 rounded-xl bg-orange-500 text-emerald-950" @click="markReturned(shop, focus, 'back')">Return</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="pane = 'extend'">Extend</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="takeCash(shop, focus)">Cash</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="acceptWaiver(focus)">Waiver</button>
                <button type="button" class="h-14 rounded-xl bg-emerald-800" @click="toast = focus.myrental">URL</button>
                <button type="button" class="h-14 rounded-xl bg-rose-900" @click="doCancel(focus)">Cancel</button>
            </div>
        </aside>
    </div>
</template>
