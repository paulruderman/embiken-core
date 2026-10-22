<script setup lang="ts">
/**
 * PROTOTYPE — party as a rack: lines are pegs; another rider is an empty peg, not a swap.
 */
import { computed, ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import { BikeReservationStatus } from '@/types/domain';
import {
    addBikeCandidates,
    addLineVariants,
    bikeCaption,
    bikeProduct,
    candidateBikes,
    partyLines,
    situationLabel,
    type Line,
    type Reservation,
    type Shop,
} from './mock';

const props = defineProps<{
    shop: Shop;
    reservation: Reservation;
}>();

const desk = useTerminalDesk();
const picking = ref<{ line: Line; mode: 'assign' | 'swap' } | null>(null);
const grow = ref<'bid' | 'size' | null>(null);
const drawer = ref<number | null>(null);
const dropping = ref<number | null>(null);

const rows = computed(() => partyLines(props.shop, props.reservation));
const canDropLine = computed(() => rows.value.length > 1);

function begin(line: Line, mode: 'assign' | 'swap'): void {
    grow.value = null;
    drawer.value = null;
    dropping.value = null;
    picking.value = { line, mode };
}

function choose(bikeId: number): void {
    const current = picking.value;

    if (!current) {
        return;
    }

    if (current.mode === 'assign') {
        desk.assignBike(current.line, bikeId);
    } else {
        desk.swapAsset(current.line, bikeId);
    }

    picking.value = null;
}

function openGrow(mode: 'bid' | 'size'): void {
    picking.value = null;
    drawer.value = null;
    grow.value = grow.value === mode ? null : mode;
}

function pickNewBike(bikeId: number): void {
    desk.allocateLine(props.reservation, 0, bikeId);
    grow.value = null;
}

function pickNewSize(productId: number): void {
    desk.allocateLine(props.reservation, productId);
    grow.value = null;
}

function toggleDrawer(lineId: number): void {
    picking.value = null;
    grow.value = null;
    dropping.value = null;
    drawer.value = drawer.value === lineId ? null : lineId;
}

function unassign(line: Line): void {
    desk.unassignBike(line);
    drawer.value = null;
}

function confirmDrop(line: Line): void {
    if (dropping.value !== line.id) {
        dropping.value = line.id;

        return;
    }

    desk.releaseLine(line);
    dropping.value = null;
    drawer.value = null;
}
</script>

<template>
    <div class="space-y-2">
        <ul class="max-h-52 space-y-2 overflow-y-auto text-sm">
            <li
                v-for="row in rows"
                :key="row.line.id"
                class="rounded-xl bg-black/25 p-2"
            >
                <div class="flex items-center gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-bold">
                            {{ row.line.product }} ·
                            {{ row.bike?.bid ?? 'unassigned' }}
                        </div>
                        <div v-if="!row.bike" class="opacity-80">
                            No bike on this line
                        </div>
                        <div v-else-if="row.onThisTicket" class="opacity-80">
                            {{ situationLabel(row.bike.situation) }} · this
                            ticket
                        </div>
                        <div v-else-if="row.occupying" class="text-amber-400">
                            {{ situationLabel(row.bike.situation) }} ·
                            {{ row.occupying.customer }} — not this ticket
                        </div>
                        <div
                            v-else-if="row.bike.situation !== 'home'"
                            class="text-amber-400"
                        >
                            {{ situationLabel(row.bike.situation) }} · no ticket
                        </div>
                        <div v-else class="opacity-80">
                            {{ situationLabel(row.bike.situation) }}
                        </div>
                    </div>
                    <button
                        v-if="
                            !row.bike &&
                            row.line.status === BikeReservationStatus.Assigned
                        "
                        type="button"
                        class="h-14 shrink-0 rounded-xl bg-amber-400 px-3 font-bold text-zinc-950"
                        @click="begin(row.line, 'assign')"
                    >
                        Assign
                    </button>
                    <button
                        v-if="row.line.status !== BikeReservationStatus.In"
                        type="button"
                        class="h-14 shrink-0 rounded-xl bg-zinc-700 px-3 font-bold text-white"
                        @click="toggleDrawer(row.line.id)"
                    >
                        Change
                    </button>
                </div>
                <div
                    v-if="drawer === row.line.id"
                    class="mt-2 grid grid-cols-3 gap-2 border-t border-white/10 pt-2"
                >
                    <button
                        v-if="
                            row.line.status ===
                                BikeReservationStatus.Assigned &&
                            row.line.bike_id !== null
                        "
                        type="button"
                        class="min-h-16 rounded-xl bg-sky-800 font-bold"
                        @click="unassign(row.line)"
                    >
                        Unassign
                    </button>
                    <button
                        type="button"
                        class="min-h-16 rounded-xl bg-violet-600 font-bold"
                        @click="begin(row.line, 'swap')"
                    >
                        Swap
                    </button>
                    <button
                        v-if="
                            canDropLine &&
                            row.line.status === BikeReservationStatus.Assigned
                        "
                        type="button"
                        class="min-h-16 rounded-xl font-bold"
                        :class="
                            dropping === row.line.id
                                ? 'bg-red-600'
                                : 'bg-zinc-800'
                        "
                        @click="confirmDrop(row.line)"
                    >
                        {{ dropping === row.line.id ? 'Drop it' : 'Drop line' }}
                    </button>
                    <div
                        v-else-if="!canDropLine"
                        class="min-h-16 rounded-xl bg-black/30 px-2 py-3 text-center text-xs opacity-60"
                    >
                        Last line — cancel the ticket
                    </div>
                </div>
                <div
                    v-if="picking && picking.line.id === row.line.id"
                    class="mt-2 flex flex-wrap gap-2 border-t border-white/10 pt-2"
                >
                    <button
                        v-for="bike in candidateBikes(
                            shop,
                            reservation,
                            picking.line,
                            picking.mode,
                        )"
                        :key="bike.id"
                        type="button"
                        class="min-h-20 min-w-28 rounded-xl bg-black/40 px-3 py-2 text-left"
                        @click="choose(bike.id)"
                    >
                        <span class="block font-bold">{{ bike.bid }}</span>
                        <span class="block text-sm font-semibold">{{
                            bikeProduct(bike)
                        }}</span>
                        <span class="block text-xs opacity-70">{{
                            bikeCaption(shop, bike)
                        }}</span>
                    </button>
                    <button
                        type="button"
                        class="min-h-20 rounded-xl px-3 opacity-70"
                        @click="picking = null"
                    >
                        Cancel
                    </button>
                </div>
            </li>
        </ul>
        <div class="rounded-xl border-2 border-dashed border-white/20 p-2">
            <div
                class="mb-2 text-xs font-bold tracking-wide uppercase opacity-70"
            >
                Another rider
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    class="min-h-16 rounded-xl font-bold"
                    :class="
                        grow === 'bid'
                            ? 'bg-amber-400 text-zinc-950'
                            : 'bg-black/40'
                    "
                    @click="openGrow('bid')"
                >
                    Add bike
                </button>
                <button
                    type="button"
                    class="min-h-16 rounded-xl font-bold"
                    :class="
                        grow === 'size'
                            ? 'bg-amber-400 text-zinc-950'
                            : 'bg-black/40'
                    "
                    @click="openGrow('size')"
                >
                    Add size
                </button>
            </div>
            <div v-if="grow === 'bid'" class="mt-2 flex flex-wrap gap-2">
                <button
                    v-for="bike in addBikeCandidates(shop, reservation)"
                    :key="bike.id"
                    type="button"
                    class="min-h-20 min-w-28 rounded-xl bg-black/40 px-3 py-2 text-left"
                    @click="pickNewBike(bike.id)"
                >
                    <span class="block font-bold">{{ bike.bid }}</span>
                    <span class="block text-sm font-semibold">{{
                        bikeProduct(bike)
                    }}</span>
                    <span class="block text-xs opacity-70">{{
                        bikeCaption(shop, bike)
                    }}</span>
                </button>
                <p
                    v-if="addBikeCandidates(shop, reservation).length === 0"
                    class="opacity-60"
                >
                    No home bikes left.
                </p>
            </div>
            <div v-if="grow === 'size'" class="mt-2 flex flex-wrap gap-2">
                <button
                    v-for="chip in addLineVariants(shop)"
                    :key="chip.product_id"
                    type="button"
                    class="min-h-20 min-w-28 rounded-xl bg-black/40 px-3 py-2 text-left font-bold"
                    @click="pickNewSize(chip.product_id)"
                >
                    {{ chip.label }}
                </button>
            </div>
        </div>
    </div>
</template>
