<script setup lang="ts">
import { useDayStore } from '@/stores/day';
import { getEnumLabel } from '@/lib/enums';

defineProps<{ feed: { name: string }; copy: Record<string, string> }>();
const day = useDayStore();
const time = (value: string): string =>
    new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
        timeZone: day.timezone,
    }).format(new Date(value));
</script>

<template>
    <div class="mx-auto max-w-7xl space-y-8">
        <header class="flex items-end justify-between gap-4">
            <h1 class="text-4xl font-semibold">{{ feed.name }}</h1>
            <p class="text-xl text-white/60">{{ day.scheduleDate }}</p>
        </header>
        <section class="space-y-4">
            <h2 class="text-2xl font-medium">
                {{ copy.bikes }}
                <span class="text-white/50">{{ day.bikeList.length }}</span>
            </h2>
            <p
                v-if="day.bikeList.length === 0"
                class="rounded-2xl bg-white/5 p-6 text-white/60"
            >
                {{ copy.no_bikes }}
            </p>
            <div v-else class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <article
                    v-for="bike in day.bikeList"
                    :key="bike.id"
                    class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10"
                >
                    <p class="text-3xl font-semibold">{{ bike.bid }}</p>
                    <p class="mt-1 text-lg">
                        {{ bike.model }} · {{ bike.variant }}
                    </p>
                    <p class="mt-4 text-sm text-white/60">
                        {{
                            getEnumLabel(
                                'bikeSituation',
                                bike.bike_situation_state,
                            )
                        }}
                    </p>
                </article>
            </div>
        </section>
        <section class="space-y-4">
            <h2 class="text-2xl font-medium">
                {{ copy.reservations }}
                <span class="text-white/50">{{
                    day.reservationList.length
                }}</span>
            </h2>
            <p
                v-if="day.reservationList.length === 0"
                class="rounded-2xl bg-white/5 p-6 text-white/60"
            >
                {{ copy.no_reservations }}
            </p>
            <div v-else class="space-y-3">
                <article
                    v-for="reservation in day.reservationList"
                    :key="reservation.id"
                    class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10"
                >
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xl font-semibold">
                            {{ time(reservation.starts_at) }} –
                            {{ time(reservation.ends_at) }}
                        </p>
                        <p
                            class="text-sm tracking-wide text-white/60 uppercase"
                        >
                            {{
                                getEnumLabel(
                                    'reservationStage',
                                    reservation.stage,
                                )
                            }}
                        </p>
                    </div>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li
                            v-for="line in reservation.lines"
                            :key="line.id"
                            class="rounded-full bg-black/20 px-3 py-1 text-sm"
                        >
                            {{
                                line.bike_id
                                    ? day.bikeById(line.bike_id)?.bid
                                    : copy.unassigned
                            }}
                            · {{ line.product_label }}
                        </li>
                    </ul>
                </article>
            </div>
        </section>
    </div>
</template>
