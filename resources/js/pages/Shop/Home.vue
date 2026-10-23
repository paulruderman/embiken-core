<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { book } from '@/routes';

interface LayoutData {
    eyebrow?: string;
    headline: string;
    description: string;
    button_label: string;
    image_url?: string | null;
    image_position?: 'left' | 'right';
    story_quote?: string;
    story_byline?: string;
}

defineProps<{
    shopName: string;
    logoUrl: string | null;
    primaryColor: string;
    layout: 'hero' | 'split' | 'story';
    layoutData: LayoutData;
    theme: 'light' | 'warm' | 'dark';
    contentHtml: string;
    isConfigured: boolean;
}>();
</script>

<template>
    <div
        class="min-h-screen"
        :class="{
            'bg-white text-zinc-950': theme === 'light',
            'bg-[#faf5ec] text-stone-950': theme === 'warm',
            'bg-zinc-950 text-white': theme === 'dark',
        }"
    >
        <Head :title="`${shopName} | Bike rentals`" />

        <header
            class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10"
        >
            <div class="flex items-center gap-3 font-semibold tracking-tight">
                <img
                    v-if="logoUrl"
                    :src="logoUrl"
                    :alt="shopName"
                    class="max-h-10 max-w-40 object-contain"
                />
                <span v-else>{{ shopName }}</span>
            </div>
            <Link
                :href="book.url()"
                :style="{ backgroundColor: primaryColor }"
                            class="inline-flex min-h-11 items-center justify-center rounded-full bg-emerald-800 px-5 text-sm font-semibold text-white transition hover:brightness-110"
            >
                Book a bike
            </Link>
        </header>

        <main>
            <section
                class="mx-auto grid max-w-7xl items-center gap-10 px-6 py-12 sm:py-20 lg:grid-cols-2 lg:px-10 lg:py-24"
                :class="{ 'text-white': theme === 'dark' }"
            >
                <div
                    :class="{
                        'lg:order-2':
                            layout === 'split' &&
                            layoutData.image_position === 'left',
                    }"
                >
                    <p
                        v-if="layoutData.eyebrow"
                        class="text-sm font-semibold tracking-wide uppercase"
                        :class="theme === 'dark' ? 'text-emerald-300' : 'text-emerald-700'"
                    >
                        {{ layoutData.eyebrow }}
                    </p>
                    <p
                        v-if="!isConfigured"
                        class="mb-4 text-sm font-medium"
                        :class="theme === 'dark' ? 'text-zinc-400' : 'text-zinc-500'"
                    >
                        This shop is setting up its home page.
                    </p>
                    <h1
                        class="mt-3 max-w-3xl text-4xl leading-tight font-semibold tracking-[-0.04em] sm:text-6xl"
                    >
                        {{ layoutData.headline }}
                    </h1>
                    <p
                        v-if="layoutData.description"
                        class="mt-6 max-w-2xl text-lg leading-8"
                        :class="theme === 'dark' ? 'text-zinc-300' : 'text-zinc-600'"
                    >
                        {{ layoutData.description }}
                    </p>
                    <p
                        v-if="layout === 'story' && layoutData.story_quote"
                        class="mt-7 max-w-2xl border-l-2 border-emerald-700 pl-5 text-lg leading-8 italic"
                    >
                        “{{ layoutData.story_quote }}”
                        <span
                            v-if="layoutData.story_byline"
                            class="mt-2 block text-sm not-italic"
                            :class="theme === 'dark' ? 'text-zinc-400' : 'text-zinc-500'"
                        >
                            {{ layoutData.story_byline }}
                        </span>
                    </p>
                    <div class="mt-8">
                        <Link
                            :href="book.url()"
                            :style="{ backgroundColor: primaryColor }"
                            class="inline-flex min-h-12 items-center justify-center rounded-full bg-emerald-800 px-6 text-sm font-semibold text-white transition hover:brightness-110"
                        >
                            {{ layoutData.button_label || 'Book a bike' }}
                            <span class="ml-2" aria-hidden="true">→</span>
                        </Link>
                    </div>
                </div>

                <div
                    v-if="layoutData.image_url"
                    class="overflow-hidden rounded-[2rem] bg-zinc-200 shadow-xl shadow-black/10"
                    :class="{
                        'lg:order-1':
                            layout === 'split' &&
                            layoutData.image_position === 'left',
                    }"
                >
                    <img
                        :src="layoutData.image_url"
                        :alt="shopName"
                        class="aspect-[4/3] w-full object-cover"
                    />
                </div>
                <div
                    v-else-if="layout === 'hero'"
                    class="hidden min-h-72 rounded-[2rem] lg:block"
                    :class="theme === 'dark' ? 'bg-emerald-900' : 'bg-emerald-50'"
                    aria-hidden="true"
                />
            </section>

            <section
                v-if="contentHtml"
                class="mx-auto max-w-4xl px-6 pb-20 lg:px-10"
            >
                <div
                    class="prose max-w-none"
                    :class="theme === 'dark' ? 'prose-invert' : 'prose-zinc'"
                    v-html="contentHtml"
                />
            </section>
        </main>

        <footer
            class="border-t px-6 py-6 text-center text-sm"
            :class="theme === 'dark' ? 'border-white/10 text-zinc-400' : 'border-black/10 text-zinc-500'"
        >
            {{ shopName }}
        </footer>
    </div>
</template>
