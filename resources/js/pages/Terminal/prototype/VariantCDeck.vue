<script setup lang="ts">
/**
 * PROTOTYPE Variant C — Verb deck. Function keys always on. No page stack.
 * Context (ticket or none) fills the center; bikes are inspect-only on the right.
 */
import { computed, reactive, ref } from 'vue';
import PrototypePartyLines from './PrototypePartyLines.vue';
import {
    acceptWaiver,
    bikeCaption,
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
    type Reservation,
} from './mock';

type Verb =
    | 'walkin'
    | 'pickup'
    | 'return'
    | 'extend'
    | 'cash'
    | 'waiver'
    | 'url'
    | 'cancel'
    | 'none';

const shop = reactive(createShop());
const selectedId = ref<number | null>(101);
const verb = ref<Verb>('none');
const toast = ref('Select a ticket, then a verb.');

const selected = computed(() =>
    shop.reservations.find((reservation) => reservation.id === selectedId.value),
);

const stack = computed(() => `deck · ${verb.value} · ticket ${selectedId.value ?? 'none'}`);

function flash(message: string): void {
    toast.value = message;
}

function selectTicket(reservation: Reservation): void {
    selectedId.value = reservation.id;
    verb.value = 'none';
}

function run(next: Verb): void {
    verb.value = next;
    const reservation = selected.value;

    if (next === 'walkin') {
        const created = startWalkIn(shop);
        selectedId.value = created.id;
        flash('Walk-in started. Waiver + cash still open.');
        return;
    }

    if (!reservation) {
        flash('No ticket selected.');
        return;
    }

    if (next === 'pickup') {
        pickup(shop, reservation);
        flash('Pickup → rented_out');
        return;
    }

    if (next === 'return') {
        markReturned(shop, reservation, 'back');
        flash('Return → back');
        return;
    }

    if (next === 'cash') {
        takeCash(shop, reservation);
        flash('Cash / other recorded');
        return;
    }

    if (next === 'waiver') {
        acceptWaiver(reservation);
        flash('Waiver stamped');
        return;
    }

    if (next === 'url') {
        flash(reservation.myrental);
        return;
    }

    if (next === 'extend') {
        return;
    }

    if (next === 'cancel') {
        const out = reservation.lines.some(
            (line) => bikeFor(shop, line.bike_id)?.situation === 'rented_out',
        );

        if (out) {
            flash('Out bikes — return first. Not auto-returned.');
            return;
        }

        cancelReservation(shop, reservation);
        selectedId.value = null;
        flash('Cancelled');
    }
}

function doExtend(requote: boolean): void {
    if (selectedId.value === null) {
        return;
    }

    const reservation = extendReservation(shop, selectedId.value, requote);

    if (!reservation) {
        return;
    }

    verb.value = 'none';
    flash(`Now ${reservation.starts}–${reservation.ends}, owed ${money(reservation.owed)}`);
}

const verbs: { id: Verb; label: string }[] = [
    { id: 'walkin', label: 'Walk-in' },
    { id: 'pickup', label: 'Pickup' },
    { id: 'return', label: 'Return' },
    { id: 'extend', label: 'Extend' },
    { id: 'cash', label: 'Cash' },
    { id: 'waiver', label: 'Waiver' },
    { id: 'url', label: 'URL' },
    { id: 'cancel', label: 'Cancel' },
];
</script>

<template>
    <div class="flex min-h-screen bg-black pb-24 text-lime-300">
        <nav class="flex w-40 flex-col gap-2 p-2">
            <button
                v-for="item in verbs"
                :key="item.id"
                type="button"
                class="h-16 rounded-lg border-2 text-lg font-bold"
                :class="
                    verb === item.id
                        ? 'border-lime-300 bg-lime-300 text-black'
                        : 'border-lime-700 bg-zinc-950'
                "
                @click="run(item.id)"
            >
                {{ item.label }}
            </button>
        </nav>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center gap-3 border-b border-lime-900 px-3 py-2">
                <span class="font-mono text-xs">{{ stack }}</span>
                <span class="ml-auto text-sm text-amber-300">{{ toast }}</span>
            </header>

            <div class="flex flex-1 overflow-hidden">
                <section class="w-72 overflow-auto border-r border-lime-900 p-2">
                    <h2 class="mb-2 text-xs tracking-widest uppercase">Tickets</h2>
                    <button
                        v-for="reservation in openReservations(shop)"
                        :key="reservation.id"
                        type="button"
                        class="mb-2 w-full rounded-lg border px-3 py-3 text-left"
                        :class="
                            selectedId === reservation.id
                                ? 'border-lime-300 bg-lime-950'
                                : 'border-lime-900'
                        "
                        @click="selectTicket(reservation)"
                    >
                        <div class="text-lg font-semibold">{{ reservation.customer }}</div>
                        <div class="text-xs text-lime-600">
                            {{ reservation.starts }}–{{ reservation.ends }}
                            ·
                            {{
                                reservation.lines
                                    .map((line) => bikeFor(shop, line.bike_id)?.bid ?? '—')
                                    .join(' ')
                            }}
                        </div>
                        <div
                            v-if="reservation.owed !== reservation.paid"
                            class="mt-1 text-sm text-amber-300"
                        >
                            DUE {{ money(reservation.owed - reservation.paid) }}
                        </div>
                    </button>
                </section>

                <section class="flex-1 p-4">
                    <div v-if="!selected" class="text-lime-700">No ticket. Walk-in or tap a row.</div>
                    <div v-else class="space-y-3">
                        <h1 class="text-3xl font-bold text-lime-200">{{ selected.customer }}</h1>
                        <p class="text-4xl font-bold text-lime-100">
                            {{ selected.starts }}–{{ selected.ends }}
                        </p>
                        <p class="text-2xl text-amber-300">
                            owed {{ money(selected.owed) }} / paid {{ money(selected.paid) }}
                        </p>
                        <p>{{ selected.stage }}</p>
                        <p>Waiver {{ selected.waiver ? 'yes' : 'NO' }}</p>
                        <PrototypePartyLines :shop="shop" :reservation="selected" />

                        <div v-if="verb === 'extend'" class="flex gap-2 pt-4">
                            <button
                                type="button"
                                class="h-16 flex-1 border-2 border-lime-300 text-lg"
                                @click="doExtend(false)"
                            >
                                Keep owed
                            </button>
                            <button
                                type="button"
                                class="h-16 flex-1 border-2 border-amber-300 text-lg text-amber-300"
                                @click="doExtend(true)"
                            >
                                Requote
                            </button>
                        </div>
                    </div>
                </section>

                <aside class="w-56 overflow-auto border-l border-lime-900 p-2">
                    <h2 class="mb-2 text-xs tracking-widest uppercase">Bikes</h2>
                    <button
                        v-for="bike in shop.bikes"
                        :key="bike.id"
                        type="button"
                        class="mb-1 flex h-14 w-full items-center justify-between rounded border border-lime-900 px-2"
                        @click="flash(`${bike.bid} ${bikeCaption(shop, bike)}`)"
                    >
                        <span class="text-xl font-bold">{{ bike.bid }}</span>
                        <span class="truncate text-xs">{{ bikeCaption(shop, bike) }}</span>
                    </button>
                </aside>
            </div>
        </div>
    </div>
</template>
