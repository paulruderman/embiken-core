<script setup lang="ts">
/**
 * PROTOTYPE Variant T — Radio log. A live tape of shop events; the ticket is whatever the last line named.
 * Not a map of space or time. A transcript.
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

const shop = reactive(createShop());
const focusId = ref<number | null>(101);
const pane = ref<'ticket' | 'assign' | 'extend'>('ticket');
const toast = ref('');
const tape = ref<string[]>([
    '08:02 Leo Park Cargo E1 went Out',
    '09:00 Priya Shah Kids D2 Out',
    '10:00 Maya Chen Turbo B1 Out — second line still unassigned',
    '10:40 Priya due — D2 now Back',
    '11:30 Sam Ortiz Escape C2 prepping',
    '12:05 Walk-in Turbo B2 staged, unpaid',
    '13:00 Nguyen party C1 + D1 staged, waiver open, $72 due',
]);

const focus = computed(() =>
    shop.reservations.find((reservation) => reservation.id === focusId.value),
);

function push(line: string): void {
    tape.value = [...tape.value, line];
}

function walkIn(): void {
    const reservation = startWalkIn(shop);
    focusId.value = reservation.id;
    push(`now Walk-in ${reservation.id} opened`);
}

function doExtend(requote: boolean): void {
    if (focusId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, focusId.value, requote);

    if (reservation) {
        pane.value = 'ticket';
        push(`extend ${reservation.customer} → ${reservation.ends} owed ${money(reservation.owed)}`);
    }
}

function pickBike(bike: Bike): void {
    if (focus.value) {
        assignBike(shop, focus.value, bike.id);
        pane.value = 'ticket';
        push(`assign ${bike.bid} → ${focus.value.customer}`);
    }
}

function doCancel(reservation: Reservation): void {
    if (reservation.lines.some((line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out')) {
        toast.value = 'Return Out bikes first';
        return;
    }

    cancelReservation(shop, reservation);
    push(`cancel ${reservation.customer}`);
    focusId.value = null;
}

function doPickup(reservation: Reservation): void {
    pickup(shop, reservation);
    push(`pickup ${reservation.customer}`);
}

function doReturn(reservation: Reservation): void {
    markReturned(shop, reservation, 'back');
    push(`return ${reservation.customer} → back`);
}
</script>

<template>
    <div class="flex min-h-screen bg-black pb-24 font-mono text-lime-400">
        <section class="flex w-1/2 flex-col border-r border-lime-900 p-3">
            <h1 class="mb-2 text-sm tracking-widest uppercase">Tape</h1>
            <button
                v-for="(line, index) in tape"
                :key="index"
                type="button"
                class="mb-1 w-full rounded px-2 py-2 text-left text-sm hover:bg-lime-950"
                @click="toast = line"
            >
                {{ line }}
            </button>
            <div class="mt-auto flex gap-2">
                <button type="button" class="h-14 flex-1 border border-lime-500" @click="walkIn">WALK-IN</button>
            </div>
        </section>
        <section class="flex min-w-0 flex-1 flex-col p-3">
            <div class="text-xs text-lime-700">{{ toast }}</div>
            <div class="mb-2 flex flex-wrap gap-1">
                <button
                    v-for="reservation in openReservations(shop)"
                    :key="reservation.id"
                    type="button"
                    class="rounded border px-2 py-1 text-xs"
                    :class="focusId === reservation.id ? 'border-lime-300' : 'border-lime-900'"
                    @click="focusId = reservation.id"
                >
                    {{ reservation.customer.split(' ')[0] }}
                </button>
            </div>
            <div v-if="focus">
                <div class="text-2xl">{{ focus.customer }}</div>
                <div class="text-3xl">{{ focus.starts }}–{{ focus.ends }}</div>
                <div class="text-xl text-amber-300">owed {{ money(focus.owed) }} / paid {{ money(focus.paid) }}</div>
                <div class="my-2">
                    <div v-for="line in focus.lines" :key="line.id">
                        LINE {{ line.id }} {{ line.product }} {{ bikeFor(shop, line.bike_id)?.bid ?? 'NULL' }}
                    </div>
                </div>
                <div v-if="pane === 'extend'" class="grid grid-cols-2 gap-2">
                    <button type="button" class="h-14 border border-lime-500" @click="doExtend(false)">KEEP</button>
                    <button type="button" class="h-14 border border-amber-400 text-amber-300" @click="doExtend(true)">
                        REQUOTE
                    </button>
                </div>
                <div v-else-if="pane === 'assign'" class="flex flex-wrap gap-2">
                    <button
                        v-for="bike in shop.bikes.filter((item) => item.situation === 'home' && item.in_service)"
                        :key="bike.id"
                        type="button"
                        class="h-14 w-14 border border-lime-500"
                        @click="pickBike(bike)"
                    >
                        {{ bike.bid }}
                    </button>
                </div>
                <div v-else class="grid grid-cols-4 gap-2">
                    <button type="button" class="h-14 border border-lime-800" @click="pane = 'assign'">ASN</button>
                    <button type="button" class="h-14 border border-lime-800" @click="doPickup(focus)">OUT</button>
                    <button type="button" class="h-14 border border-lime-800" @click="doReturn(focus)">IN</button>
                    <button type="button" class="h-14 border border-lime-800" @click="pane = 'extend'">EXT</button>
                    <button type="button" class="h-14 border border-lime-800" @click="takeCash(shop, focus)">CASH</button>
                    <button type="button" class="h-14 border border-lime-800" @click="acceptWaiver(focus)">WVR</button>
                    <button type="button" class="h-14 border border-lime-800" @click="toast = focus.myrental">URL</button>
                    <button type="button" class="h-14 border border-lime-800" @click="doCancel(focus)">CAN</button>
                </div>
            </div>
        </section>
    </div>
</template>
