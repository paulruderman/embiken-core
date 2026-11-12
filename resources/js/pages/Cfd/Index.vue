<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, useHttp } from '@inertiajs/vue3';
import UpdateCustomerDisplayTicketAction from '@/actions/Embiken/BikeRentals/Actions/UpdateCustomerDisplayTicketAction';
import CfdRealtime from '@/pages/Cfd/CfdRealtime.vue';
import { useCfdTicketStore, type CfdTicket } from '@/stores/cfdTicket';
type Copy = {
    title: string;
    waiting: string;
    noTicket: string;
    rental: string;
    customer: string;
    name: string;
    email: string;
    phone: string;
    save: string;
    saving: string;
    waiverTitle: string;
    waiverAccept: string;
    saved: string;
    error: string;
    amountDue: string;
    paid: string;
    start: string;
    end: string;
};

const props = defineProps<{
    tenant_id: string;
    device_id: number;
    ticket: CfdTicket;
    copy: Copy;
}>();
const display = useCfdTicketStore();
const ticket = computed(() => display.ticket);
const contact = reactive({ name: '', email: '', phone: '' });
const accepted = ref(false);
const saving = ref(false);
const notice = ref('');
const http = useHttp<{}, { data?: { ticket: CfdTicket }; ticket: CfdTicket }>(
    {},
);
const clientReady = ref(false);
onMounted(() => {
    clientReady.value = true;
});
const currency = computed(() => ticket.value?.currency.toUpperCase() ?? 'USD');
const money = (minor: number): string =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency.value,
    }).format(minor / 100);

function hydrate(value: CfdTicket): void {
    display.hydrate(value);
    contact.name = value?.customer.name ?? '';
    contact.email = value?.customer.email ?? '';
    contact.phone = value?.customer.phone ?? '';
    accepted.value = Boolean(value?.waiver?.accepted_at);
    notice.value = '';
}

watch(() => props.ticket, hydrate, { immediate: true });

function save(acceptWaiver = accepted.value): void {
    if (!ticket.value || saving.value) return;
    saving.value = true;
    notice.value = '';
    http.transform(() => ({
        name: contact.name,
        email: contact.email,
        phone: contact.phone,
        waiver_accepted: acceptWaiver,
        waiver_version_id: ticket.value?.waiver?.version_id ?? null,
        waiver_locale: ticket.value?.waiver?.locale ?? null,
    })).post(UpdateCustomerDisplayTicketAction['/cfd/ticket'].url(), {
        onSuccess: (response) => {
            const data = (response.data ?? response) as { ticket: CfdTicket };
            hydrate(data.ticket);
            notice.value = props.copy.saved;
            saving.value = false;
        },
        onError: () => {
            accepted.value = Boolean(ticket.value?.waiver?.accepted_at);
            notice.value = props.copy.error;
            saving.value = false;
        },
        onHttpException: () => {
            notice.value = props.copy.error;
            saving.value = false;
        },
        onNetworkError: () => {
            notice.value = props.copy.error;
            saving.value = false;
        },
    });
}

function onTicket(value: Record<string, unknown> | null): void {
    hydrate(value as CfdTicket);
}
</script>

<template>
    <Head :title="copy.title" />
    <CfdRealtime
        v-if="clientReady"
        :tenant-id="tenant_id"
        :device-id="device_id"
        @ticket="onTicket"
    />
    <main class="min-h-screen bg-zinc-950 px-6 py-8 text-white sm:px-10">
        <div
            v-if="ticket"
            class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[1.2fr_0.8fr]"
        >
            <section class="space-y-6">
                <header>
                    <p
                        class="text-sm font-semibold tracking-[0.2em] text-sky-300 uppercase"
                    >
                        {{ copy.rental }}
                    </p>
                    <h1 class="mt-2 text-4xl font-bold">
                        {{ ticket.package?.name ?? copy.rental }}
                    </h1>
                </header>
                <div class="rounded-3xl bg-zinc-900 p-6">
                    <h2 class="text-2xl font-semibold">{{ copy.customer }}</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm"
                            >{{ copy.name
                            }}<input
                                v-model="contact.name"
                                autocomplete="name"
                                class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3 text-lg"
                        /></label>
                        <label class="grid gap-2 text-sm"
                            >{{ copy.email
                            }}<input
                                v-model="contact.email"
                                autocomplete="email"
                                type="email"
                                class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3 text-lg"
                        /></label>
                        <label class="grid gap-2 text-sm sm:col-span-2"
                            >{{ copy.phone
                            }}<input
                                v-model="contact.phone"
                                autocomplete="tel"
                                type="tel"
                                class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3 text-lg"
                        /></label>
                    </div>
                    <button
                        class="mt-5 rounded-xl bg-sky-500 px-6 py-3 font-semibold text-zinc-950 disabled:opacity-50"
                        :disabled="saving"
                        @click="save()"
                    >
                        {{ saving ? copy.saving : copy.save }}
                    </button>
                </div>
                <div v-if="ticket.waiver" class="rounded-3xl bg-zinc-900 p-6">
                    <h2 class="text-2xl font-semibold">
                        {{ copy.waiverTitle }}
                    </h2>
                    <div
                        class="mt-4 max-h-64 overflow-y-auto rounded-xl bg-zinc-800 p-4 leading-7 whitespace-pre-wrap"
                    >
                        {{ ticket.waiver.content }}
                    </div>
                    <label
                        class="mt-5 flex cursor-pointer items-start gap-3 text-lg"
                    >
                        <input
                            v-model="accepted"
                            type="checkbox"
                            class="mt-1 size-5 accent-sky-400"
                            :disabled="ticket.waiver.accepted_at !== null"
                            @change="save(accepted)"
                        />
                        <span>{{ copy.waiverAccept }}</span>
                    </label>
                </div>
                <p
                    v-if="notice"
                    class="rounded-xl bg-zinc-800 px-4 py-3"
                    role="status"
                >
                    {{ notice }}
                </p>
            </section>
            <aside class="space-y-6">
                <div class="rounded-3xl bg-zinc-900 p-6">
                    <h2 class="text-2xl font-semibold">{{ copy.rental }}</h2>
                    <ul class="mt-4 divide-y divide-zinc-800">
                        <li
                            v-for="line in ticket.lines"
                            :key="line.id"
                            class="flex justify-between gap-4 py-4 text-lg"
                        >
                            <span>{{ line.product_label }}</span
                            ><span
                                v-if="line.bid"
                                class="font-mono text-sky-300"
                                >{{ line.bid }}</span
                            >
                        </li>
                    </ul>
                    <div
                        class="mt-4 grid gap-2 border-t border-zinc-700 pt-4 text-zinc-300"
                    >
                        <p>
                            {{ copy.start }}:
                            {{ new Date(ticket.starts_at).toLocaleString() }}
                        </p>
                        <p>
                            {{ copy.end }}:
                            {{ new Date(ticket.ends_at).toLocaleString() }}
                        </p>
                        <p>
                            {{ copy.amountDue }}:
                            {{ money(ticket.owed_minor_units) }}
                        </p>
                        <p>
                            {{ copy.paid }}:
                            {{ money(ticket.paid_minor_units) }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>
        <section
            v-else
            class="mx-auto flex min-h-[70vh] max-w-2xl flex-col items-center justify-center text-center"
        >
            <p
                class="text-sm font-semibold tracking-[0.2em] text-sky-300 uppercase"
            >
                {{ copy.title }}
            </p>
            <h1 class="mt-5 text-4xl font-bold">{{ copy.waiting }}</h1>
            <p class="mt-3 text-zinc-400">{{ copy.noTicket }}</p>
        </section>
    </main>
</template>
