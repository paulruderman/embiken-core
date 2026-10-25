<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    reservation: {
        id: number;
        stage: string;
        starts_at: string;
        ends_at: string;
        owed: number;
        paid: number;
        currency: string;
        customer: { name: string };
        location: { name: string };
        lines: { id: number; product: string; bid: string | null; status: string }[];
        access_expires_at: string;
    };
}>();
</script>

<template>
    <main class="mx-auto min-h-screen max-w-2xl space-y-8 px-6 py-12">
        <Head title="My reservation" />
        <header class="space-y-2">
            <p class="text-sm text-zinc-600">{{ reservation.location.name }}</p>
            <h1 class="text-3xl font-semibold">Reservation #{{ reservation.id }}</h1>
            <p>For {{ reservation.customer.name }}</p>
            <p>{{ new Date(reservation.starts_at).toLocaleString() }} – {{ new Date(reservation.ends_at).toLocaleString() }}</p>
        </header>
        <section class="space-y-3">
            <h2 class="text-xl font-medium">Bikes</h2>
            <ul class="divide-y divide-zinc-200">
                <li v-for="line in reservation.lines" :key="line.id" class="flex justify-between py-3">
                    <span>{{ line.product }}<span v-if="line.bid"> · {{ line.bid }}</span></span>
                    <span>{{ line.status }}</span>
                </li>
            </ul>
        </section>
        <p class="text-sm text-zinc-600">This private link is valid until {{ new Date(reservation.access_expires_at).toLocaleString() }}.</p>
    </main>
</template>
