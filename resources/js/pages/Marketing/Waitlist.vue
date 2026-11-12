<script setup lang="ts">
import MarketingLayout from '@/components/MarketingLayout.vue';
import { localRouteUrl } from '@/lib/local-route-url';
import marketing from '@/routes/marketing';
import JoinEarlyAccessWaitlistAction from '@/actions/App/Actions/Platform/JoinEarlyAccessWaitlistAction';
import { Head, Link, useHttp } from '@inertiajs/vue3';

const props = defineProps<{
    ui: Record<string, string>;
}>();

const form = useHttp({
    email: '',
    name: '',
    shop_name: '',
    message: '',
});

function submit(): void {
    form.post(JoinEarlyAccessWaitlistAction.url(), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <MarketingLayout>
        <Head :title="props.ui.title" />
        <main class="mx-auto max-w-3xl px-6 py-16 lg:px-10 lg:py-24">
            <section class="rounded-3xl border border-zinc-200 bg-white p-7 shadow-sm sm:p-10">
                <template v-if="form.wasSuccessful">
                    <p class="text-sm font-semibold tracking-[0.16em] text-emerald-800 uppercase">{{ props.ui.brand }}</p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ props.ui.success_title }}</h1>
                    <p class="mt-4 leading-7 text-zinc-600">{{ props.ui.success_body }}</p>
                    <Link :href="localRouteUrl(marketing.home.localhost())" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-emerald-900 px-6 text-sm font-semibold text-white transition hover:bg-emerald-800">
                        {{ props.ui.back_home }}
                    </Link>
                </template>
                <template v-else>
                    <p class="text-sm font-semibold tracking-[0.16em] text-emerald-800 uppercase">{{ props.ui.brand }}</p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ props.ui.title }}</h1>
                    <p class="mt-4 leading-7 text-zinc-600">{{ props.ui.intro }}</p>

                    <form class="mt-8 space-y-6" @submit.prevent="submit">
                        <div>
                            <label for="email" class="block text-sm font-medium text-zinc-900">{{ props.ui.email }}</label>
                            <input id="email" v-model="form.email" type="email" name="email" required autocomplete="email" maxlength="255" class="mt-2 min-h-12 w-full rounded-xl border border-zinc-300 bg-white px-4 text-base outline-none transition focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15" />
                            <p v-if="form.errors.email" class="mt-2 text-sm text-red-700">{{ form.errors.email }}</p>
                        </div>

                        <details class="group rounded-2xl border border-zinc-200 px-4 py-3">
                            <summary class="cursor-pointer list-none text-sm font-medium text-zinc-700 marker:hidden">
                                <span>{{ props.ui.optional_details }}</span>
                                <span class="float-right text-zinc-400 transition group-open:rotate-45" aria-hidden="true">+</span>
                            </summary>
                            <div class="mt-4 space-y-4 pb-1">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-zinc-700">{{ props.ui.name }}</label>
                                    <input id="name" v-model="form.name" type="text" name="name" autocomplete="name" maxlength="255" class="mt-2 min-h-11 w-full rounded-xl border border-zinc-300 px-3 text-base outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15" />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-700">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label for="shop_name" class="block text-sm font-medium text-zinc-700">{{ props.ui.shop_name }}</label>
                                    <input id="shop_name" v-model="form.shop_name" type="text" name="shop_name" maxlength="255" class="mt-2 min-h-11 w-full rounded-xl border border-zinc-300 px-3 text-base outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15" />
                                    <p v-if="form.errors.shop_name" class="mt-1 text-sm text-red-700">{{ form.errors.shop_name }}</p>
                                </div>
                                <div>
                                    <label for="message" class="block text-sm font-medium text-zinc-700">{{ props.ui.message }}</label>
                                    <textarea id="message" v-model="form.message" name="message" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 text-base outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15" />
                                    <p v-if="form.errors.message" class="mt-1 text-sm text-red-700">{{ form.errors.message }}</p>
                                </div>
                            </div>
                        </details>

                        <div>
                            <button type="submit" :disabled="form.processing" class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-emerald-900 px-6 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60 sm:w-auto">
                                {{ form.processing ? props.ui.submitting : props.ui.submit }}
                            </button>
                            <p class="mt-4 max-w-xl text-xs leading-5 text-zinc-500">{{ props.ui.privacy }}</p>
                        </div>
                    </form>
                </template>
            </section>
        </main>
    </MarketingLayout>
</template>
