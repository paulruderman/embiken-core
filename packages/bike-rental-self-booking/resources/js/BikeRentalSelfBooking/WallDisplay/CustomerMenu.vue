<script setup lang="ts">
import { formatMoney } from '@/lib/money';

type Feed = {
    data: {
        currency: string;
        packages: {
            id: number;
            name: string;
            description: string | null;
            tagline: string | null;
            blurb: string | null;
            meter_label: string;
            meter_unit: string | null;
            variants: { id: number; label: string; rate_minor_units: number }[];
        }[];
        hours: {
            weekday: string;
            opens_at: string;
            closes_at: string;
            closes_next_day: boolean;
        }[];
        copy: Record<string, string>;
    };
};
defineProps<{ feed: Feed }>();
</script>

<template>
    <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.5fr_1fr]">
        <section class="space-y-5">
            <h1 class="text-4xl font-semibold">{{ feed.data.copy.offers }}</h1>
            <p
                v-if="feed.data.packages.length === 0"
                class="rounded-2xl bg-white/5 p-6 text-white/60"
            >
                {{ feed.data.copy.no_offers }}
            </p>
            <article
                v-for="item in feed.data.packages"
                :key="item.id"
                class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"
            >
                <h2 class="text-2xl font-semibold">{{ item.name }}</h2>
                <p v-if="item.tagline" class="mt-1 text-lg text-white/70">
                    {{ item.tagline }}
                </p>
                <p v-if="item.description" class="mt-3 text-white/70">
                    {{ item.description }}
                </p>
                <p v-if="item.blurb" class="mt-3 text-sm text-white/60">
                    {{ item.blurb }}
                </p>
                <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="variant in item.variants"
                        :key="variant.id"
                        class="flex justify-between gap-3 rounded-xl bg-black/20 px-4 py-3"
                    >
                        <span>{{ variant.label }}</span
                        ><span class="font-medium"
                            >{{
                                formatMoney(
                                    variant.rate_minor_units,
                                    feed.data.currency,
                                )
                            }}
                            <small class="text-white/60">{{
                                item.meter_unit
                                    ? `${feed.data.copy.per} ${item.meter_unit}`
                                    : item.meter_label
                            }}</small></span
                        >
                    </li>
                </ul>
            </article>
        </section>
        <section class="space-y-5">
            <h2 class="text-3xl font-semibold">{{ feed.data.copy.hours }}</h2>
            <p
                v-if="feed.data.hours.length === 0"
                class="rounded-2xl bg-white/5 p-6 text-white/60"
            >
                {{ feed.data.copy.no_hours }}
            </p>
            <div
                v-else
                class="divide-y divide-white/10 rounded-2xl bg-white/5 px-5"
            >
                <div
                    v-for="(row, index) in feed.data.hours"
                    :key="`${row.weekday}-${index}`"
                    class="flex justify-between gap-4 py-4"
                >
                    <span>{{ row.weekday }}</span
                    ><span
                        >{{ row.opens_at }} – {{ row.closes_at
                        }}<sup v-if="row.closes_next_day">+1</sup></span
                    >
                </div>
            </div>
        </section>
    </div>
</template>
