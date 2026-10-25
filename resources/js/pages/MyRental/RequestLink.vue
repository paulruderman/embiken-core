<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import RequestMyRentalLinkAction from '@/actions/Embiken/BikeRentals/Actions/RequestMyRentalLinkAction';

const form = useHttp({ email: '' });

function submit(): void {
    form.post(RequestMyRentalLinkAction.url(), {
        onSuccess: () => {
            form.email = '';
        },
    });
}
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-6 py-12">
        <Head title="Find your reservation" />
        <section class="w-full space-y-6">
            <div class="space-y-2">
                <h1 class="text-3xl font-semibold">Find your reservation</h1>
                <p class="text-zinc-600">Enter the email address used for your reservation. We’ll email a private link if there’s an eligible reservation for it.</p>
            </div>
            <form class="space-y-4" @submit.prevent="submit">
                <label class="block space-y-2">
                    <span>Email address</span>
                    <input v-model="form.email" type="email" autocomplete="email" required class="h-12 w-full rounded border border-zinc-300 px-3" />
                </label>
                <p v-if="form.errors.email" class="text-sm text-red-700">{{ form.errors.email }}</p>
                <button type="submit" :disabled="form.processing" class="h-12 rounded bg-zinc-900 px-5 font-medium text-white disabled:opacity-60">
                    {{ form.processing ? 'Sending…' : 'Email my link' }}
                </button>
                <p v-if="form.wasSuccessful" role="status" class="text-sm text-green-800">If an eligible reservation matches that email, its link will be sent shortly.</p>
            </form>
        </section>
    </main>
</template>
