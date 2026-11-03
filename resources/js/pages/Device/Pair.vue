<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { store as requestPairingCode } from '@/routes/devices/pairing-codes';
import { complete as completePairing } from '@/routes/devices/pairing';
import { cfd, terminal, wallDisplay } from '@/routes';

const props = defineProps<{
    surface: 'terminal' | 'customer_display' | 'wall_display';
    copy: {
        title: string;
        ready: string;
        instruction: string;
        starting: string;
        retrying: string;
        start_error: string;
    };
}>();
const code = ref('');
const error = ref('');
const paired = ref(false);
let pollTimer: ReturnType<typeof setTimeout> | undefined;
let stopped = false;

function csrfToken(): string {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

async function postJson(url: string, data: Record<string, string>): Promise<Response> {
    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(data),
    });
}

async function poll(): Promise<void> {
    if (stopped || code.value === '') return;

    try {
        const response = await postJson(completePairing.url(), { code: code.value });
        if (response.ok) {
            const payload = await response.json() as { paired: boolean };
            if (payload.paired) {
                paired.value = true;
                const destination = props.surface === 'terminal'
                    ? terminal.url()
                    : props.surface === 'customer_display'
                        ? cfd.url()
                        : wallDisplay.url();
                window.location.assign(destination);
                return;
            }
        } else if (response.status !== 422) {
            throw new Error('Could not check pairing status.');
        }
    } catch {
        error.value = props.copy.retrying;
    }

    pollTimer = setTimeout(poll, 2000);
}

onMounted(async () => {
    try {
        const response = await postJson(requestPairingCode.url(), { surface: props.surface });
        if (!response.ok) throw new Error('Could not start pairing.');
        const payload = await response.json() as { pairing_code: string };
        code.value = payload.pairing_code;
        void poll();
    } catch {
        error.value = props.copy.start_error;
    }
});

onUnmounted(() => {
    stopped = true;
    if (pollTimer !== undefined) clearTimeout(pollTimer);
});
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-zinc-950 px-6 text-white">
        <Head :title="copy.title" />
        <section class="w-full max-w-xl space-y-6 text-center">
            <h1 class="text-3xl font-semibold">{{ copy.title }}</h1>
            <p v-if="paired" role="status">{{ copy.ready }}</p>
            <template v-else>
                <p class="text-zinc-300">{{ copy.instruction }}</p>
                <p class="font-mono text-5xl font-bold tracking-[0.25em]">{{ code || copy.starting }}</p>
                <p v-if="error" role="alert" class="text-amber-300">{{ error }}</p>
            </template>
        </section>
    </main>
</template>
