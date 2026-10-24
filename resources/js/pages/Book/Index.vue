<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';

const props = defineProps<{
    locale: 'en' | 'es' | 'fr';
    bookingNotices: Array<{
        message: string;
        tone: 'info' | 'success' | 'warning';
    }>;
}>();

const copy = {
    en: { title: 'Book', description: 'Online reservations for this shop.', language: 'Language' },
    es: { title: 'Reservar', description: 'Reservas en línea para esta tienda.', language: 'Idioma' },
    fr: { title: 'Réserver', description: 'Réservations en ligne pour cette boutique.', language: 'Langue' },
};

const text = copy[props.locale];
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-zinc-50 p-6 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100"
    >
        <Head :title="text.title" />
        <div class="absolute top-4 right-4">
            <LocaleSwitcher />
        </div>
        <main class="max-w-md text-center">
            <h1 class="text-2xl font-medium tracking-tight">{{ text.title }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ text.description }}
            </p>
            <div v-if="bookingNotices.length" class="mt-6 grid gap-3 text-left">
                <p
                    v-for="(notice, index) in bookingNotices"
                    :key="index"
                    class="rounded-xl border px-4 py-3 text-sm leading-6"
                    :class="{
                        'border-sky-200 bg-sky-50 text-sky-950 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100':
                            notice.tone === 'info',
                        'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100':
                            notice.tone === 'success',
                        'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100':
                            notice.tone === 'warning',
                    }"
                >
                    {{ notice.message }}
                </p>
            </div>
        </main>
    </div>
</template>
