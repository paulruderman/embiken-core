<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const languageLabel = computed(() => {
    const language = page.props.locale.split('-')[0];

    return (
        { en: 'Language', es: 'Idioma', fr: 'Langue' }[language] ?? 'Language'
    );
});
const activeLocale = computed(
    () =>
        page.props.supportedLocales.find(
            (locale) => locale.tag === page.props.locale,
        )?.value ?? 'en_US',
);

function changeLocale(locale: string): void {
    const url = new URL(page.url, 'http://localhost');

    url.searchParams.set('locale', locale);

    router.get(
        `${url.pathname}${url.search}${url.hash}`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}
</script>

<template>
    <label class="inline-flex items-center gap-2 text-sm">
        <span class="sr-only">{{ languageLabel }}</span>
        <select
            :value="activeLocale"
            :aria-label="languageLabel"
            class="min-h-11 rounded-md border border-current/20 bg-transparent px-3 py-2"
            @change="changeLocale(($event.target as HTMLSelectElement).value)"
        >
            <option
                v-for="locale in page.props.supportedLocales"
                :key="locale.value"
                :value="locale.value"
            >
                {{ locale.label }}
            </option>
        </select>
    </label>
</template>
