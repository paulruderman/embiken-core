<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import marketing from '@/routes/marketing';
import { localRouteUrl } from '@/lib/local-route-url';
</script>

<template>
    <div class="min-h-screen bg-[#f7f7f3] text-zinc-950">
        <header class="border-b border-zinc-200/80 bg-[#f7f7f3]/95">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-5 lg:px-10">
                <Link :href="localRouteUrl(marketing.home.localhost())" class="text-xl font-semibold tracking-tight">embiken</Link>
                <nav aria-label="Main navigation" class="flex flex-wrap items-center justify-end gap-x-5 gap-y-2 text-sm font-medium">
                    <Link :href="localRouteUrl(marketing.features.localhost())" class="text-zinc-600 transition hover:text-emerald-900">Product</Link>
                    <Link :href="localRouteUrl(marketing.pricing.localhost())" class="text-zinc-600 transition hover:text-emerald-900">Pricing</Link>
                    <Link :href="localRouteUrl(marketing.faq.localhost())" class="text-zinc-600 transition hover:text-emerald-900">FAQ</Link>
                    <Link :href="localRouteUrl(marketing.blog.localhost())" class="text-zinc-600 transition hover:text-emerald-900">Journal</Link>
                    <Link :href="localRouteUrl(marketing.contact.localhost())" class="rounded-full bg-emerald-900 px-4 py-2.5 text-white transition hover:bg-emerald-800">Talk to us</Link>
                </nav>
            </div>
        </header>

        <slot />

        <footer class="mt-20 border-t border-zinc-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto] lg:px-10">
                <div>
                    <Link :href="localRouteUrl(marketing.home.localhost())" class="text-lg font-semibold tracking-tight">embiken</Link>
                    <p class="mt-2 max-w-md text-sm leading-6 text-zinc-500">Early-access shop setup and straightforward pricing for independent bike rental businesses.</p>
                </div>
                <nav aria-label="Product" class="grid content-start gap-2 text-sm text-zinc-600">
                    <span class="mb-1 font-semibold text-zinc-900">Explore</span>
                    <Link :href="localRouteUrl(marketing.features.localhost())" class="hover:text-emerald-900">Product overview</Link>
                    <Link :href="localRouteUrl(marketing.pricing.localhost())" class="hover:text-emerald-900">Pricing</Link>
                    <Link :href="localRouteUrl(marketing.blog.localhost())" class="hover:text-emerald-900">Journal</Link>
                    <Link :href="localRouteUrl(marketing.faq.localhost())" class="hover:text-emerald-900">FAQ</Link>
                </nav>
                <nav aria-label="Company and policies" class="grid content-start gap-2 text-sm text-zinc-600">
                    <span class="mb-1 font-semibold text-zinc-900">Company</span>
                    <Link :href="localRouteUrl(marketing.contact.localhost())" class="hover:text-emerald-900">Contact</Link>
                    <Link :href="localRouteUrl(marketing.terms.localhost())" class="hover:text-emerald-900">Terms</Link>
                    <Link :href="localRouteUrl(marketing.privacy.localhost())" class="hover:text-emerald-900">Privacy</Link>
                    <span class="pt-2 text-xs text-zinc-400">© {{ new Date().getFullYear() }} Embiken</span>
                </nav>
            </div>
        </footer>
    </div>
</template>
