<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { home } from '@/routes/marketing';
import { pricing } from '@/routes/marketing';
import signupRoutes from '@/routes/marketing/signup';
import { localRouteUrl } from '@/lib/local-route-url';

interface Plan {
    slug: string;
    name: string;
    monthlyAmountCents: number | null;
    annualAmountCents: number | null;
    currency: string;
    processingRateBasisPoints: number | null;
    customPricing: boolean;
    features: string[];
}

const props = defineProps<{ plan: Plan; domainSuffix: string; contactEmail: string }>();
const form = useForm({
    plan: props.plan.slug,
    name: '',
    shop_slug: '',
    timezone: 'America/New_York',
    currency: 'usd',
    manager_name: '',
    manager_email: '',
});
const slugWasEdited = ref(false);
const shopSlugLabel = computed(
    () => `${form.shop_slug || 'your-shop'}.${props.domainSuffix}`,
);

function updateShopSlug(): void {
    if (!slugWasEdited.value) {
        form.shop_slug = form.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '')
            .slice(0, 60);
    }
}

function submit(): void {
    form.post(localRouteUrl(signupRoutes.store().url));
}
</script>

<template>
    <div class="min-h-screen bg-[#f7f7f3] text-zinc-950">
        <Head :title="`Start with ${plan.name}`" />
        <header
            class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6 lg:px-10"
        >
            <Link :href="localRouteUrl(home())" class="text-xl font-semibold tracking-tight"
                >embiken</Link
            >
            <span
                class="rounded-full bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-950"
                >Selected plan: {{ plan.name }}</span
            >
        </header>

        <main
            class="mx-auto grid max-w-6xl gap-10 px-6 pt-8 pb-20 lg:grid-cols-[1fr_0.8fr] lg:px-10"
        >
            <section v-if="plan.customPricing" class="max-w-xl">
                <p
                    class="text-sm font-semibold tracking-[0.18em] text-emerald-800 uppercase"
                >
                    Enterprise plan
                </p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em]">
                    A plan shaped around your shop
                </h1>
                <p class="mt-4 leading-7 text-zinc-600">
                    Enterprise terms are tailored to your operation. We’ll
                    discuss custom pricing and confirm whether the current
                    early-access setup fits your shop.
                </p>
                <div
                    class="mt-8 rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="text-lg font-semibold">What’s customized</h2>
                    <ul class="mt-4 grid gap-3 text-sm leading-6 text-zinc-600">
                        <li class="flex gap-2">
                            <span class="font-semibold text-emerald-800"
                                >✓</span
                            >
                            <span
                                >Custom subscription price and combined payment rate</span
                            >
                        </li>
                        <li class="flex gap-2">
                            <span class="font-semibold text-emerald-800">✓</span>
                            <span>Early-access fit confirmed before setup</span>
                        </li>
                    </ul>
                </div>
                <p class="mt-6 text-sm leading-6 text-zinc-500">
                    Tell us about your shop and we’ll discuss a custom plan.
                    <a :href="`mailto:${props.contactEmail}?subject=Embiken%20Enterprise%20inquiry`" class="font-semibold text-emerald-900 underline underline-offset-4">Email the Embiken team</a>.
                    No subscription payment is collected on this page.
                </p>
                <Link
                    :href="localRouteUrl(pricing())"
                    class="mt-6 inline-block text-sm font-medium text-zinc-600 underline decoration-zinc-300 underline-offset-4 hover:text-zinc-950"
                    >Back to plan comparison</Link
                >
            </section>

            <section v-else class="max-w-xl">
                <p
                    class="text-sm font-semibold tracking-[0.18em] text-emerald-800 uppercase"
                >
                    Your shop starts here
                </p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em]">
                    Set up your shop
                </h1>
                <p class="mt-4 leading-7 text-zinc-600">
                    We’ll create your shop and email the Manager an invitation
                    to set a password. Embiken subscription checkout is not
                    connected yet.
                </p>

                <form class="mt-8 grid gap-5" @submit.prevent="submit">
                    <label class="grid gap-2 text-sm font-medium"
                        >Shop name
                        <input
                            v-model="form.name"
                            required
                            maxlength="255"
                            class="rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-950 ring-emerald-700/20 outline-none focus:ring-4"
                            autocomplete="organization"
                            @input="updateShopSlug"
                        />
                        <span
                            v-if="form.errors.name"
                            class="text-sm font-normal text-red-700"
                            >{{ form.errors.name }}</span
                        >
                    </label>
                    <label class="grid gap-2 text-sm font-medium"
                        >Shop address
                        <div
                            class="flex items-center rounded-xl border border-zinc-300 bg-white focus-within:ring-4 focus-within:ring-emerald-700/20"
                        >
                            <input
                                v-model="form.shop_slug"
                                required
                                minlength="2"
                                maxlength="60"
                                pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                class="min-w-0 flex-1 rounded-l-xl bg-transparent px-4 py-3 outline-none"
                                autocomplete="off"
                                @input="slugWasEdited = true"
                            />
                            <span class="truncate px-4 text-sm text-zinc-500"
                                >.{{ domainSuffix }}</span
                            >
                        </div>
                        <span
                            v-if="form.errors.shop_slug"
                            class="text-sm font-normal text-red-700"
                            >{{ form.errors.shop_slug }}</span
                        >
                        <span v-else class="text-xs font-normal text-zinc-500"
                            >Your shop will be at {{ shopSlugLabel }}</span
                        >
                    </label>
                    <label class="grid gap-2 text-sm font-medium"
                        >Manager name
                        <input
                            v-model="form.manager_name"
                            required
                            maxlength="255"
                            class="rounded-xl border border-zinc-300 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-emerald-700/20"
                            autocomplete="name"
                        />
                        <span
                            v-if="form.errors.manager_name"
                            class="text-sm font-normal text-red-700"
                            >{{ form.errors.manager_name }}</span
                        >
                    </label>
                    <label class="grid gap-2 text-sm font-medium"
                        >Manager email
                        <input
                            v-model="form.manager_email"
                            required
                            type="email"
                            maxlength="255"
                            class="rounded-xl border border-zinc-300 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-emerald-700/20"
                            autocomplete="email"
                        />
                        <span
                            v-if="form.errors.manager_email"
                            class="text-sm font-normal text-red-700"
                            >{{ form.errors.manager_email }}</span
                        >
                    </label>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-2 rounded-full bg-emerald-800 px-6 py-4 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{
                            form.processing
                                ? 'Setting up…'
                                : 'Create shop workspace'
                        }}
                    </button>
                    <p v-if="form.errors.plan" class="text-sm text-red-700">
                        {{ form.errors.plan }}
                    </p>
                </form>
            </section>

            <aside
                class="h-fit rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-sm lg:sticky lg:top-8"
            >
                <p class="text-sm font-semibold text-emerald-800">
                    Plan summary
                </p>
                <h2 class="mt-3 text-2xl font-semibold">{{ plan.name }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">
                    {{
                        plan.customPricing
                            ? 'Subscription pricing is tailored to your operation.'
                            : plan.monthlyAmountCents === null
                              ? 'No subscription fee.'
                              : 'Subscription billing will be added later. No subscription charge will be collected during this signup.'
                    }}
                </p>
                <div class="mt-6 rounded-2xl bg-[#f7f7f3] p-4">
                    <p
                        class="text-xs font-medium tracking-wide text-zinc-500 uppercase"
                    >
                        Embiken payment rate
                    </p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">
                        {{
                            plan.processingRateBasisPoints === null
                                ? 'Custom'
                                : `${(plan.processingRateBasisPoints / 100).toFixed(plan.processingRateBasisPoints % 100 === 0 ? 0 : 2)}%`
                        }}
                    </p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">
                        Only on payments processed by Embiken.
                    </p>
                </div>
                <Link
                    :href="localRouteUrl(pricing())"
                    class="mt-6 inline-block text-sm font-medium text-zinc-600 underline decoration-zinc-300 underline-offset-4 hover:text-zinc-950"
                    >Change plan</Link
                >
            </aside>
        </main>
    </div>
</template>
