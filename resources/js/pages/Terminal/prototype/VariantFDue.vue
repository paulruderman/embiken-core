<script setup lang="ts">
/**
 * PROTOTYPE Variant F — Due well. Home is urgency (late / out / next / back), not the floor.
 * Floor is a drill-in tab. Walk-in is a full-height first row.
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

type Home = 'due' | 'floor';

const shop = reactive(createShop());
const home = ref<Home>('due');
const focusId = ref<number | null>(null);
const pane = ref<'ticket' | 'extend'>('ticket');
const toast = ref('');

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => {
    if (home.value === 'floor') {
        return 'due › floor';
    }

    return focus.value ? `due › ${pane.value}` : 'due';
});

function situationOf(reservation: Reservation): string {
    const bike = bikeFor(shop, reservation.lines[0]?.bike_id ?? null);

    return bike?.situation ?? 'none';
}

function bucket(label: string, match: (reservation: Reservation) => boolean): Reservation[] {
    return openReservations(shop).filter(match);
}

const late = computed(() =>
    bucket('late', (reservation) => situationOf(reservation) === 'rented_out' && hourLate(reservation)),
);
const out = computed(() =>
    bucket(
        'out',
        (reservation) => situationOf(reservation) === 'rented_out' && !hourLate(reservation),
    ),
);
const next = computed(() =>
    bucket('next', (reservation) => ['prepping', 'staged'].includes(situationOf(reservation))),
);
const back = computed(() =>
    bucket('back', (reservation) => situationOf(reservation) === 'back'),
);

function hourLate(reservation: Reservation): boolean {
    return reservation.id === 105;
}

function flash(message: string): void {
    toast.value = message;
}

function openTicket(reservation: Reservation): void {
    focusId.value = reservation.id;
    pane.value = 'ticket';
    home.value = 'due';
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
    if (!focus.value || bike.situation !== 'home' || !bike.in_service) {
        return;
    }

    assignBike(shop, focus.value, bike.id);
    home.value = 'due';
    flash(`Assigned ${bike.bid}`);
}

function doCancel(reservation: Reservation): void {
    const outLine = reservation.lines.some(
        (line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out',
    );

    if (outLine) {
        flash('Out bikes — return first.');
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-950 pb-24 text-yellow-300">
        <header class="flex items-center gap-2 px-3 py-2">
            <button
                type="button"
                class="h-14 flex-1 rounded-xl border-2 text-lg font-bold"
                :class="home === 'due' ? 'border-yellow-300 bg-yellow-300 text-black' : 'border-yellow-700'"
                @click="home = 'due'"
            >
                Due
            </button>
            <button
                type="button"
                class="h-14 flex-1 rounded-xl border-2 text-lg font-bold"
                :class="home === 'floor' ? 'border-yellow-300 bg-yellow-300 text-black' : 'border-yellow-700'"
                @click="home = 'floor'"
            >
                Floor
            </button>
            <div class="font-mono text-xs">{{ stack }}</div>
        </header>
        <div class="px-3 text-sm text-amber-200">{{ toast }}</div>

        <main v-if="home === 'floor'" class="grid grid-cols-3 gap-2 p-3">
            <p class="col-span-3 text-sm text-yellow-600">Home bikes assign to the open ticket.</p>
            <button
                v-for="bike in shop.bikes"
                :key="bike.id"
                type="button"
                class="h-20 rounded-2xl border border-yellow-800 text-2xl font-bold"
                @click="pickBike(bike)"
            >
                {{ bike.bid }}
                <span class="block text-xs font-normal">{{ bike.situation }}</span>
            </button>
        </main>

        <main v-else class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto p-3">
            <button
                type="button"
                class="h-20 w-full rounded-2xl bg-yellow-300 text-2xl font-bold text-black"
                @click="walkIn"
            >
                Walk-in
            </button>

            <section v-for="group in [
                { title: 'Late', rows: late },
                { title: 'Out', rows: out },
                { title: 'Next', rows: next },
                { title: 'Back (put-away)', rows: back },
            ]" :key="group.title">
                <h2 class="mb-1 text-xs tracking-widest text-yellow-700 uppercase">{{ group.title }}</h2>
                <button
                    v-for="reservation in group.rows"
                    :key="reservation.id"
                    type="button"
                    class="mb-2 flex min-h-20 w-full items-center justify-between rounded-2xl border-2 px-4 text-left"
                    :class="
                        focusId === reservation.id
                            ? 'border-yellow-300 bg-yellow-950'
                            : 'border-yellow-900'
                    "
                    @click="openTicket(reservation)"
                >
                    <div>
                        <div class="text-2xl font-bold">{{ reservation.customer }}</div>
                        <div class="text-sm text-yellow-600">
                            {{ reservation.starts }}–{{ reservation.ends }} · owed
                            {{ money(reservation.owed) }}
                        </div>
                    </div>
                    <div class="text-xl font-bold">
                        {{
                            reservation.lines
                                .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                .join(' ')
                        }}
                    </div>
                </button>
            </section>

            <div v-if="focus" class="mt-auto rounded-2xl border border-yellow-700 p-3">
                <div class="text-2xl font-bold text-yellow-100">{{ focus.customer }}</div>
                <div class="text-3xl font-bold">{{ focus.starts }}–{{ focus.ends }}</div>
                <div class="mb-3 text-xl">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
                <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                    <button type="button" class="h-16 border-2 border-yellow-300 text-lg" @click="doExtend(false)">
                        Keep owed
                    </button>
                    <button
                        type="button"
                        class="h-16 border-2 border-amber-300 text-lg text-amber-300"
                        @click="doExtend(true)"
                    >
                        Requote
                    </button>
                </div>
                <div v-else class="grid grid-cols-4 gap-2">
                    <button type="button" class="h-14 border border-yellow-800" @click="home = 'floor'">
                        Assign
                    </button>
                    <button type="button" class="h-14 border border-yellow-800" @click="pickup(shop, focus)">
                        Pickup
                    </button>
                    <button
                        type="button"
                        class="h-14 border border-yellow-800"
                        @click="markReturned(shop, focus, 'back')"
                    >
                        Return
                    </button>
                    <button type="button" class="h-14 border border-yellow-800" @click="pane = 'extend'">
                        Extend
                    </button>
                    <button type="button" class="h-14 border border-yellow-800" @click="takeCash(shop, focus)">
                        Cash
                    </button>
                    <button type="button" class="h-14 border border-yellow-800" @click="acceptWaiver(focus)">
                        Waiver
                    </button>
                    <button type="button" class="h-14 border border-yellow-800" @click="flash(focus.myrental)">
                        URL
                    </button>
                    <button type="button" class="h-14 border border-yellow-800" @click="doCancel(focus)">
                        Cancel
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>
