<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes/marketing';
import { localRouteUrl } from '@/lib/local-route-url';

defineProps<{ plan: string; domain: string; email: string }>();
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#f7f7f3] px-6 py-12 text-zinc-950"
    >
        <Head title="Signup received" />
        <main
            class="w-full max-w-xl rounded-[2rem] border border-zinc-200 bg-white p-8 shadow-sm sm:p-10"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-xl font-semibold text-emerald-900"
            >
                ✓
            </div>
            <p
                class="mt-7 text-sm font-semibold tracking-[0.18em] text-emerald-800 uppercase"
            >
                Signup received
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Your {{ plan }} shop is being set up.
            </h1>
            <p class="mt-4 leading-7 text-zinc-600">
                We created
                <strong class="font-semibold text-zinc-900">{{ domain }}</strong
                >. The Manager invitation will be sent to
                <strong class="font-semibold text-zinc-900">{{ email }}</strong
                >.
            </p>
            <div
                class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950"
            >
                <strong>This is a checkout placeholder.</strong> No subscription
                payment has been collected. Embiken billing checkout is not
                connected yet.
            </div>
            <Link
                :href="localRouteUrl(home())"
                class="mt-8 inline-flex rounded-full bg-emerald-800 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                >Return to Embiken</Link
            >
        </main>
    </div>
</template>
