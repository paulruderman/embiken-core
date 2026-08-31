<script setup lang="ts">
/**
 * PROTOTYPE Variant A — Floor board always on. Ticket is a bottom sheet.
 * Screen count: 1 home + overlays. Board never leaves.
 */
import { computed, reactive, ref } from 'vue';
import {
    acceptWaiver,
    assignBike,
    bikeFor,
    extendReservation,
    cancelReservation,
    createShop,
    markReturned,
    money,
    pickup,
    reservationForBike,
    situationLabel,
    startWalkIn,
    takeCash,
    type Bike,
    type Reservation,
} from './mock';

const shop = reactive(createShop());
const focusId = ref<number | null>(null);
const picker = ref(false);
const extending = ref(false);
const toast = ref('');
const copied = ref(false);

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => {
    if (picker.value) {
        return 'floor › ticket › assign';
    }

    if (extending.value) {
        return 'floor › ticket › extend';
    }

    if (focus.value) {
        return 'floor › ticket';
    }

    return 'floor';
});

const groups: { situation: Bike['situation']; label: string }[] = [
    { situation: 'rented_out', label: 'Out' },
    { situation: 'staged', label: 'Staged' },
    { situation: 'prepping', label: 'Prepping' },
    { situation: 'back', label: 'Back' },
    { situation: 'home', label: 'Home' },
];

function tileClass(bike: Bike): string {
    if (!bike.in_service) {
        return 'bg-zinc-800 text-zinc-500';
    }

    return {
        home: 'bg-zinc-700 text-white',
        prepping: 'bg-amber-500 text-black',
        staged: 'bg-sky-500 text-black',
        rented_out: 'bg-rose-600 text-white',
        back: 'bg-orange-500 text-black',
    }[bike.situation];
}

function openBike(bike: Bike): void {
    const reservation = reservationForBike(shop, bike.id);

    if (reservation) {
        focusId.value = reservation.id;
        picker.value = false;
        extending.value = false;
        return;
    }

    toast.value = `${bike.bid} is ${situationLabel(bike.situation)} — no ticket`;
}

function flash(message: string): void {
    toast.value = message;
}

function walkIn(): void {
    const reservation = startWalkIn(shop);
    focusId.value = reservation.id;
    flash('Walk-in ticket. Collect contact + waiver.');
}

function doPickup(reservation: Reservation): void {
    pickup(shop, reservation);
    flash('Picked up → rented_out');
}

function doReturn(reservation: Reservation, to: 'home' | 'back'): void {
    markReturned(shop, reservation, to);
    flash(to === 'back' ? 'Returned to back' : 'Returned home');
}

function doCash(reservation: Reservation): void {
    takeCash(shop, reservation);
    flash('Cash recorded. owed = paid');
}

function doCancel(reservation: Reservation): void {
    const out = reservation.lines.some((line) => {
        const bike = bikeFor(shop, line.bike_id);

        return bike?.situation === 'rented_out';
    });

    if (out) {
        flash('Out bikes — confirm they are in the shop, then return. Not auto-returned.');
        return;
    }

    cancelReservation(shop, reservation);
    focusId.value = null;
    flash('Cancelled. Occupancy released.');
}

function pickBike(bike: Bike): void {
    if (!focus.value || !bike.in_service || bike.situation !== 'home') {
        return;
    }

    assignBike(shop, focus.value, bike.id);
    picker.value = false;
    flash(`Assigned ${bike.bid}`);
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (!reservation) {
        return;
    }

    extending.value = false;
    flash(
        requote
            ? `Now ${reservation.starts}–${reservation.ends}, owed ${money(reservation.owed)}`
            : `Now ${reservation.starts}–${reservation.ends}, owed unchanged`,
    );
}

function copyLink(reservation: Reservation): void {
    copied.value = true;
    flash(reservation.myrental);
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-950 pb-24 text-zinc-100">
        <header class="flex items-center gap-3 px-4 py-3">
            <div class="text-lg font-semibold tracking-tight">Floor</div>
            <div class="rounded-full bg-zinc-800 px-3 py-1 font-mono text-xs">{{ stack }}</div>
            <div class="ml-auto text-sm text-amber-300">{{ toast }}</div>
            <button
                type="button"
                class="h-16 min-w-32 rounded-2xl bg-emerald-500 px-5 text-lg font-semibold text-black"
                @click="walkIn"
            >
                Walk-in
            </button>
        </header>

        <div class="grid flex-1 gap-6 px-4 pb-4 lg:grid-cols-5">
            <section v-for="group in groups" :key="group.situation" class="min-h-40">
                <h2 class="mb-2 text-xs font-semibold tracking-widest text-zinc-500 uppercase">
                    {{ group.label }}
                </h2>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="bike in shop.bikes.filter((item) => item.situation === group.situation)"
                        :key="bike.id"
                        type="button"
                        class="flex h-20 flex-col items-start justify-center rounded-2xl px-3 text-left"
                        :class="tileClass(bike)"
                        @click="openBike(bike)"
                    >
                        <span class="text-2xl font-bold">{{ bike.bid }}</span>
                        <span class="text-xs opacity-80">{{ bike.model }}</span>
                    </button>
                </div>
            </section>
        </div>

        <div
            v-if="focus"
            class="fixed inset-x-0 bottom-16 z-20 max-h-[70vh] overflow-auto rounded-t-3xl bg-zinc-900 p-4 shadow-2xl"
        >
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <div class="text-2xl font-semibold">{{ focus.customer }}</div>
                    <div class="text-3xl font-bold tracking-tight">
                        {{ focus.starts }}–{{ focus.ends }}
                    </div>
                    <div class="text-2xl font-semibold text-amber-300">
                        owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}
                    </div>
                    <div class="text-sm text-zinc-400">{{ focus.stage }}</div>
                </div>
                <button type="button" class="h-14 w-14 rounded-2xl bg-zinc-800" @click="focusId = null">
                    ✕
                </button>
            </div>

            <div class="mb-3 flex flex-wrap gap-2 text-sm">
                <span
                    v-for="line in focus.lines"
                    :key="line.id"
                    class="rounded-full bg-zinc-800 px-3 py-2"
                >
                    {{ line.product }}
                    {{ bikeFor(shop, line.bike_id)?.bid ?? 'unassigned' }}
                </span>
            </div>

            <div v-if="extending" class="grid grid-cols-1 gap-2">
                <p class="text-lg">Push ends one hour. Requote also adds $20.</p>
                <button
                    type="button"
                    class="h-20 rounded-2xl bg-zinc-700 text-2xl"
                    @click="doExtend(false)"
                >
                    Keep owed
                </button>
                <button
                    type="button"
                    class="h-20 rounded-2xl bg-amber-500 text-2xl text-black"
                    @click="doExtend(true)"
                >
                    Requote
                </button>
            </div>

            <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <button type="button" class="h-16 rounded-2xl bg-zinc-800 text-lg" @click="picker = true">
                    Assign
                </button>
                <button type="button" class="h-16 rounded-2xl bg-sky-600 text-lg" @click="doPickup(focus)">
                    Pickup
                </button>
                <button
                    type="button"
                    class="h-16 rounded-2xl bg-orange-500 text-lg text-black"
                    @click="doReturn(focus, 'back')"
                >
                    Return
                </button>
                <button type="button" class="h-16 rounded-2xl bg-zinc-800 text-lg" @click="extending = true">
                    Extend
                </button>
                <button type="button" class="h-16 rounded-2xl bg-emerald-700 text-lg" @click="doCash(focus)">
                    Cash / other
                </button>
                <button type="button" class="h-16 rounded-2xl bg-zinc-800 text-lg" @click="acceptWaiver(focus)">
                    {{ focus.waiver ? 'Waiver ✓' : 'Waiver' }}
                </button>
                <button type="button" class="h-16 rounded-2xl bg-zinc-800 text-lg" @click="copyLink(focus)">
                    {{ copied ? 'Copied URL' : 'MyRental URL' }}
                </button>
                <button type="button" class="h-16 rounded-2xl bg-rose-900 text-lg" @click="doCancel(focus)">
                    Cancel
                </button>
            </div>
        </div>

        <div v-if="picker && focus" class="fixed inset-0 z-30 bg-black/70 p-4 pt-16">
            <div class="rounded-3xl bg-zinc-900 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Assign — home bikes</h2>
                    <button type="button" class="h-14 w-14 rounded-2xl bg-zinc-800" @click="picker = false">
                        ✕
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                        :key="bike.id"
                        type="button"
                        class="h-20 rounded-2xl bg-zinc-700 text-2xl font-bold"
                        @click="pickBike(bike)"
                    >
                        {{ bike.bid }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
