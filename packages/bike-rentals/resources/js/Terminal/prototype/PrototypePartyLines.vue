<script setup lang="ts">
/**
 * PROTOTYPE — party lines with Assign/Swap on the row. Slot is the row, not a second list.
 */
import { ref } from 'vue';
import { useTerminalDesk } from '@/composables/useTerminalDesk';
import {
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

function begin(line: Line, mode: 'assign' | 'swap'): void {
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
</script>

<template>
    <ul class="max-h-52 space-y-2 overflow-y-auto text-sm">
        <li
            v-for="row in partyLines(shop, reservation)"
            :key="row.line.id"
            class="rounded-xl bg-black/25 p-2"
        >
            <div class="flex items-center gap-2">
                <div class="min-w-0 flex-1">
                    <div class="font-bold">
                        {{ row.line.product }} · {{ row.bike?.bid ?? 'unassigned' }}
                    </div>
                    <div v-if="!row.bike" class="opacity-80">No bike on this line</div>
                    <div v-else-if="row.onThisTicket" class="opacity-80">
                        {{ situationLabel(row.bike.situation) }} · this ticket
                    </div>
                    <div v-else-if="row.occupying" class="text-amber-400">
                        {{ situationLabel(row.bike.situation) }} · {{ row.occupying.customer }} — not this ticket
                    </div>
                    <div v-else-if="row.bike.situation !== 'home'" class="text-amber-400">
                        {{ situationLabel(row.bike.situation) }} · no ticket
                    </div>
                    <div v-else class="opacity-80">{{ situationLabel(row.bike.situation) }}</div>
                </div>
                <button
                    v-if="!row.bike"
                    type="button"
                    class="h-14 shrink-0 rounded-xl bg-amber-400 px-3 font-bold text-zinc-950"
                    @click="begin(row.line, 'assign')"
                >
                    Assign
                </button>
                <button
                    v-else
                    type="button"
                    class="h-14 shrink-0 rounded-xl bg-violet-600 px-3 font-bold text-white"
                    @click="begin(row.line, 'swap')"
                >
                    Swap
                </button>
            </div>
            <div
                v-if="picking && picking.line.id === row.line.id"
                class="mt-2 flex flex-wrap gap-2 border-t border-white/10 pt-2"
            >
                <button
                    v-for="bike in candidateBikes(shop, reservation, picking.line, picking.mode)"
                    :key="bike.id"
                    type="button"
                    class="min-h-20 min-w-28 rounded-xl bg-black/40 px-3 py-2 text-left"
                    @click="choose(bike.id)"
                >
                    <span class="block font-bold">{{ bike.bid }}</span>
                    <span class="block text-sm font-semibold">{{ bikeProduct(bike) }}</span>
                    <span class="block text-xs opacity-70">{{ bikeCaption(shop, bike) }}</span>
                </button>
                <button type="button" class="min-h-20 rounded-xl px-3 opacity-70" @click="picking = null">Cancel</button>
            </div>
        </li>
    </ul>
</template>
