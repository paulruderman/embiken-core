<script setup lang="ts">
/**
 * PROTOTYPE Variant A — Floor board always on. Ticket is a bottom sheet.
 * Screen count: 1 home + overlays. Board never leaves.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    useShopFloor,
    bikeCaption,
    bikeFor,
    money,
    reservationForBike,
    situationLabel,
    type Bike,
    type Reservation,
} from './mock';

const shop = useShopFloor();
const desk = useTerminalDesk();
const focusId = ref<number | null>(null);
const extending = ref(false);
const toast = ref('');
const copied = ref(false);

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

const stack = computed(() => {
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
        extending.value = false;
        return;
    }

    toast.value = `${bike.bid} is ${situationLabel(bike.situation)} — no ticket`;
}

function flash(message: string): void {
    toast.value = message;
}

function walkIn(): void {
    desk.startWalkIn((id) => {
        focusId.value = id;
        flash('Walk-in ticket. Collect contact + waiver.');
    });
}

function doPickup(reservation: Reservation): void {
    desk.pickup(reservation);
    flash('Picked up → rented_out');
}

function doReturn(reservation: Reservation, to: 'home' | 'back'): void {
    desk.markReturned(reservation);
    flash(to === 'back' ? 'Returned to back' : 'Returned home');
}

function doCash(reservation: Reservation): void {
    desk.takeCash(reservation);
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

    desk.cancelReservation(reservation);
    focusId.value = null;
    flash('Cancelled. Occupancy released.');
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
                        <span class="text-xs opacity-80">{{ bikeCaption(shop, bike) }}</span>
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

            <PrototypePartyLines class="mb-3" :shop="shop" :reservation="focus" />

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
                <button type="button" class="h-16 rounded-2xl bg-zinc-800 text-lg" @click="desk.acceptWaiver(focus)">
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
    </div>
</template>
