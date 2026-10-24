<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { signup } from '@/routes/marketing';
import { localRouteUrl } from '@/lib/local-route-url';
import MarketingLayout from '@/components/MarketingLayout.vue';

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

interface CmsPage {
    title: string;
    excerpt: string | null;
    contentHtml: string;
    seoTitle: string;
    seoDescription: string | null;
    canonicalUrl: string;
}

const props = defineProps<{ plans: Plan[]; page: CmsPage }>();
const locale = usePage().props.locale;

const annual = ref(false);
const selectedPlan = ref<Plan | null>(
    props.plans.find((plan) => plan.slug === 'standard') ??
        props.plans.find((plan) => !plan.customPricing) ??
        null,
);
const intervalLabel = computed(() =>
    annual.value ? 'billed yearly' : 'billed monthly',
);

function money(cents: number | null, currency: string): string {
    if (cents === null) return 'Custom';

    return `${currency.toUpperCase()}\u00a0${new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(cents / 100)}`;
}

function monthlyDisplayAmount(plan: Plan): number | null {
    return annual.value && plan.annualAmountCents !== null
        ? plan.annualAmountCents / 12
        : plan.monthlyAmountCents;
}

function selectPlan(plan: Plan): void {
    selectedPlan.value = plan;
}

function percentage(basisPoints: number | null): string {
    return basisPoints === null
        ? 'Custom'
        : `${(basisPoints / 100).toFixed(basisPoints % 100 === 0 ? 0 : 2)}%`;
}

function planDetails(plan: Plan): string[] {
    if (plan.customPricing)
        return ['Discuss custom terms and current availability'];
    const rate = `${percentage(plan.processingRateBasisPoints)} combined rate when Embiken processes payment`;
    if (plan.slug === 'starter')
        return ['No subscription fee', 'Create a shop workspace', rate];

    return ['Subscription billing is not active yet', rate];
}
</script>

<template>
    <MarketingLayout>
        <Head :title="props.page.seoTitle">
            <meta
                v-if="props.page.seoDescription"
                name="description"
                :content="props.page.seoDescription"
                head-key="description"
            />
            <meta property="og:type" content="website" head-key="og:type" />
            <meta
                property="og:title"
                :content="props.page.seoTitle"
                head-key="og:title"
            />
            <meta
                v-if="props.page.seoDescription"
                property="og:description"
                :content="props.page.seoDescription"
                head-key="og:description"
            />
            <link
                rel="canonical"
                :href="props.page.canonicalUrl"
                head-key="canonical"
            />
        </Head>
        <main class="mx-auto max-w-7xl px-6 pt-12 pb-20 lg:px-10 lg:pt-16">
            <div class="mx-auto max-w-3xl text-center">
                <p
                    class="text-sm font-semibold tracking-[0.18em] text-emerald-800 uppercase"
                >
                    Straightforward pricing
                </p>
                <h1
                    class="mt-4 text-4xl font-semibold tracking-[-0.04em] sm:text-6xl"
                >
                    {{ props.page.title }}
                </h1>
                <p
                    class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-zinc-600"
                >
                    {{ props.page.excerpt }}
                </p>
                <div
                    class="mt-7 inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white p-1.5 text-sm shadow-sm"
                >
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 transition"
                        :class="
                            annual
                                ? 'text-zinc-500 hover:text-zinc-900'
                                : 'bg-zinc-950 font-medium text-white'
                        "
                        @click="annual = false"
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 transition"
                        :class="
                            annual
                                ? 'bg-zinc-950 font-medium text-white'
                                : 'text-zinc-500 hover:text-zinc-900'
                        "
                        @click="annual = true"
                    >
                        Yearly <span class="ml-1 text-lime-300">Save</span>
                    </button>
                </div>
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="plan in props.plans"
                    :key="plan.slug"
                    class="flex cursor-pointer flex-col rounded-[1.75rem] border p-6 shadow-sm transition"
                    :class="[
                        plan.slug === 'standard'
                            ? 'border-emerald-300 bg-emerald-50/70 shadow-emerald-950/10'
                            : 'border-zinc-200 bg-white',
                        selectedPlan?.slug === plan.slug
                            ? 'ring-2 ring-emerald-900 ring-offset-2 ring-offset-[#f7f7f3]'
                            : 'hover:-translate-y-0.5 hover:shadow-md',
                    ]"
                    @click="selectPlan(plan)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-h-20">
                            <h2 class="text-xl font-semibold">
                                {{ plan.name }}
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">
                                {{
                                    plan.slug === 'starter'
                                        ? 'Start a shop workspace during early access.'
                                        : plan.slug === 'standard'
                                          ? 'A lower rate as your operation grows.'
                                          : plan.slug === 'pro'
                                            ? 'For larger operations to discuss with us.'
                                            : 'For complex operations.'
                                }}
                            </p>
                        </div>
                        <span
                            v-if="plan.slug === 'standard'"
                            class="rounded-full bg-lime-200 px-3 py-1 text-xs font-semibold text-emerald-950"
                            >★ Popular</span
                        >
                    </div>

                    <div class="mt-7 min-h-20">
                        <p class="text-3xl font-semibold tracking-tight">
                            {{
                                plan.customPricing
                                    ? 'Custom'
                                    : plan.monthlyAmountCents === null
                                      ? 'Free'
                                      : money(
                                            monthlyDisplayAmount(plan),
                                            plan.currency,
                                        )
                            }}<span
                                v-if="
                                    !plan.customPricing &&
                                    plan.monthlyAmountCents !== null
                                "
                                class="text-sm font-normal text-zinc-500"
                            >
                                / month</span
                            >
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{
                                plan.customPricing
                                    ? 'Plan details after selection'
                                    : plan.monthlyAmountCents === null
                                      ? 'No subscription fee'
                                      : annual &&
                                          plan.annualAmountCents !== null
                                        ? `${money(plan.annualAmountCents, plan.currency)} billed yearly`
                                        : intervalLabel
                            }}
                        </p>
                    </div>

                    <div class="mt-4 min-h-32 rounded-2xl bg-[#f7f7f3] p-4">
                        <p
                            class="text-xs font-medium tracking-wide text-zinc-500 uppercase"
                        >
                            Embiken payment rate
                        </p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">
                            {{
                                plan.customPricing
                                    ? 'Custom'
                                    : percentage(plan.processingRateBasisPoints)
                            }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-zinc-500">
                            {{
                                plan.customPricing
                                    ? 'Select Enterprise to review its tailored setup.'
                                    : 'One combined rate for payments processed through Embiken.'
                            }}
                        </p>
                    </div>

                    <ul class="mt-6 flex-1 space-y-3 text-sm text-zinc-600">
                        <li
                            v-for="feature in planDetails(plan)"
                            :key="feature"
                            class="flex gap-2"
                        >
                            <span class="font-semibold text-emerald-800">✓</span
                            ><span>{{ feature }}</span>
                        </li>
                        <li v-if="plan.customPricing" class="flex gap-2">
                            <span class="font-semibold text-emerald-800"
                                >✓</span
                            >
                            <span
                                >View Enterprise details on the next step</span
                            >
                        </li>
                        <li
                            v-if="
                                plan.slug !== 'starter' && !plan.customPricing
                            "
                            class="flex gap-2"
                        >
                            <span class="font-semibold text-emerald-800">✓</span
                            ><span
                                >Plan capabilities and limits coming soon</span
                            >
                        </li>
                    </ul>

                    <button
                        type="button"
                        class="mt-8 rounded-full px-5 py-3.5 text-center text-sm font-semibold transition"
                        :class="
                            selectedPlan?.slug === plan.slug
                                ? 'bg-emerald-800 text-white hover:bg-emerald-700'
                                : 'border border-zinc-300 text-zinc-900 hover:border-zinc-500'
                        "
                        :aria-pressed="selectedPlan?.slug === plan.slug"
                        @click="selectPlan(plan)"
                    >
                        {{
                            selectedPlan?.slug === plan.slug
                                ? 'Selected'
                                : `Choose ${plan.name}`
                        }}
                    </button>
                </article>
            </div>

            <div class="mt-8 flex justify-center">
                <Link
                    v-if="selectedPlan && !selectedPlan.customPricing"
                    :href="localRouteUrl(signup(selectedPlan.slug))"
                    class="rounded-full bg-emerald-800 px-7 py-3.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                >
                    Continue with {{ selectedPlan.name }}
                </Link>
                <Link
                    v-else-if="selectedPlan"
                    :href="localRouteUrl(signup(selectedPlan.slug))"
                    class="rounded-full bg-emerald-800 px-7 py-3.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
                >
                    Explore {{ selectedPlan.name }}
                </Link>
                <button
                    v-else
                    type="button"
                    disabled
                    class="cursor-not-allowed rounded-full bg-zinc-200 px-7 py-3.5 text-center text-sm font-semibold text-zinc-500"
                >
                    Choose a plan to continue
                </button>
            </div>

            <article
                class="mx-auto mt-10 max-w-3xl rounded-3xl border border-zinc-200 bg-white p-6 text-sm leading-7 text-zinc-600 sm:p-8"
                v-html="props.page.contentHtml"
            />
        </main>
    </MarketingLayout>
</template>
