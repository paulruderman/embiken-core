<script setup lang="ts">
import MarketingLayout from '@/components/MarketingLayout.vue';
import { localRouteUrl } from '@/lib/local-route-url';
import { contact, pricing, signup } from '@/routes/marketing';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <MarketingLayout>
        <Head title="Bike rental shop software, in early access | Embiken">
            <meta
                name="description"
                content="Get started with Embiken: create a shop workspace, choose your plan, and prepare for a simpler way to run bike rentals."
                head-key="description"
            />
            <meta property="og:type" content="website" head-key="og:type" />
            <meta
                property="og:title"
                content="Bike rental shop software, in early access | Embiken"
                head-key="og:title"
            />
            <meta
                property="og:description"
                content="Create a shop workspace and explore clear early-access pricing for independent bike rental businesses."
                head-key="og:description"
            />
            <link rel="canonical" href="https://embiken.com/" head-key="canonical" />
        </Head>

        <main>
            <section class="relative isolate overflow-hidden bg-[#102d26] text-white">
                <div class="absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <div class="absolute -top-48 right-[8%] size-[38rem] rounded-full border border-white/10" />
                    <div class="absolute -top-24 right-[13%] size-[29rem] rounded-full border border-white/10" />
                    <div class="absolute top-8 right-[17%] size-[20rem] rounded-full border border-white/10" />
                    <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-emerald-300/10 to-transparent" />
                </div>

                <div class="mx-auto grid max-w-7xl items-center gap-10 px-6 pt-16 pb-20 sm:pt-20 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:pt-24 lg:pb-28">
                    <div class="max-w-2xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-emerald-100/20 bg-white/5 px-3.5 py-2 text-xs font-semibold tracking-[0.16em] text-emerald-100 uppercase">
                            <span class="size-2 rounded-full bg-lime-300" />
                            Embiken early access
                        </p>
                        <h1 class="mt-7 text-5xl leading-[1.04] font-semibold tracking-[-0.055em] sm:text-6xl lg:text-7xl">
                            More time for the ride.
                            <span class="mt-2 block text-emerald-200">Less time at the desk.</span>
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-emerald-50/75 sm:text-xl">
                            A fresh start for independent bike rental shops. Set up your shop workspace, explore simple plans, and help shape what comes next.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                :href="localRouteUrl(signup({ billingPlan: 'starter' }))"
                                class="inline-flex min-h-12 items-center justify-center rounded-full bg-lime-300 px-6 text-sm font-semibold text-emerald-950 shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-lime-200"
                            >
                                Create a free workspace
                                <span class="ml-2" aria-hidden="true">→</span>
                            </Link>
                            <Link
                                :href="localRouteUrl(pricing())"
                                class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/25 px-6 text-sm font-semibold text-white transition hover:bg-white/10"
                            >
                                Explore pricing
                            </Link>
                        </div>
                        <p class="mt-4 text-sm text-emerald-100/60">
                            Starter has no subscription fee. Workspace signup is available during early access.
                        </p>
                    </div>

                    <div class="relative mx-auto flex min-h-[22rem] w-full max-w-xl items-center justify-center sm:min-h-[28rem]" aria-hidden="true">
                        <div class="absolute size-[21rem] rounded-full bg-emerald-200/10 blur-3xl sm:size-[27rem]" />
                        <div class="relative w-full rounded-[2rem] border border-white/15 bg-white/[0.07] p-5 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-7">
                            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 items-center justify-center rounded-xl bg-lime-300 text-emerald-950">
                                        <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
                                            <circle cx="6" cy="17" r="3.5" stroke="currentColor" stroke-width="1.7" />
                                            <circle cx="18" cy="17" r="3.5" stroke="currentColor" stroke-width="1.7" />
                                            <path d="m6 17 4-8h3l5 8m-8-8 4 8h-5m1-8h4m1-3h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Your shop, your workspace</p>
                                        <p class="mt-0.5 text-xs text-white/55">A clear place to get started</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-lime-300/15 px-3 py-1 text-[11px] font-medium text-lime-200">Early access</span>
                            </div>

                            <div class="grid gap-3 py-5 sm:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-[#0b211c]/50 p-4">
                                    <p class="text-xs text-white/55">Choose a plan</p>
                                    <p class="mt-2 text-lg font-semibold">Start with Starter</p>
                                    <p class="mt-1 text-xs text-white/50">No subscription fee</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-[#0b211c]/50 p-4">
                                    <p class="text-xs text-white/55">Set up your shop</p>
                                    <p class="mt-2 text-lg font-semibold">One workspace</p>
                                    <p class="mt-1 text-xs text-white/50">Your own shop address</p>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-[#d5f285] p-5 text-emerald-950 sm:p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold tracking-[0.14em] uppercase">Made for bike rental shops</p>
                                        <p class="mt-2 max-w-xs text-xl leading-snug font-semibold tracking-tight sm:text-2xl">A thoughtful foundation for your next season.</p>
                                    </div>
                                    <svg viewBox="0 0 72 72" fill="none" class="mt-1 size-12 shrink-0 text-emerald-900/75 sm:size-14" aria-hidden="true">
                                        <circle cx="18" cy="48" r="13" stroke="currentColor" stroke-width="2" />
                                        <circle cx="54" cy="48" r="13" stroke="currentColor" stroke-width="2" />
                                        <path d="m18 48 15-27 12 27H18Zm15-27h10m-2-5h7m-1 32 7-11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-4 text-center text-[11px] text-white/45">Rental, fleet, and workshop tools are still in development.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-20">
                <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.18em] text-emerald-800 uppercase">A good place to begin</p>
                        <h2 class="mt-4 max-w-md text-3xl leading-tight font-semibold tracking-[-0.04em] sm:text-4xl">
                            Built with independent rental shops in mind.
                        </h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <article class="rounded-3xl border border-zinc-200 bg-white p-6 sm:p-7">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-lg font-semibold text-emerald-900">01</span>
                            <h3 class="mt-5 text-lg font-semibold">Create your workspace</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">Choose a plan and set up your shop’s first location, timezone, and currency.</p>
                        </article>
                        <article class="rounded-3xl border border-zinc-200 bg-white p-6 sm:p-7">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-lg font-semibold text-emerald-900">02</span>
                            <h3 class="mt-5 text-lg font-semibold">See the pricing clearly</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">Compare subscription plans and the single combined rate for payments processed through Embiken.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="flex flex-col gap-6 rounded-[2rem] bg-[#e9eee5] p-7 sm:p-10 lg:flex-row lg:items-center lg:justify-between lg:p-12">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold tracking-[0.18em] text-emerald-800 uppercase">Early access</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Start with the essentials.</h2>
                        <p class="mt-3 text-sm leading-6 text-zinc-600 sm:text-base">Create a workspace, take a look at the plans, and tell us what your shop needs as Embiken grows.</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link :href="localRouteUrl(signup({ billingPlan: 'starter' }))" class="inline-flex min-h-12 items-center justify-center rounded-full bg-emerald-900 px-6 text-sm font-semibold text-white transition hover:bg-emerald-800">Create a workspace</Link>
                        <Link :href="localRouteUrl(contact())" class="inline-flex min-h-12 items-center justify-center rounded-full border border-emerald-900/20 px-6 text-sm font-semibold text-emerald-950 transition hover:bg-white/60">Talk to us</Link>
                    </div>
                </div>
            </section>
        </main>
    </MarketingLayout>
</template>
