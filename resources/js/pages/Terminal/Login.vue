<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { store } from '@/routes/staff/login';

defineProps<{
    logoUrl: string | null;
}>();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-brand-gray-50 dark:bg-brand-gray-950 p-6 text-brand-gray-900 dark:text-brand-gray-100">
        <Head title="Terminal sign in" />
        <div class="absolute top-4 right-4">
            <LocaleSwitcher />
        </div>
        <Form
            v-bind="store.form()"
            class="w-full max-w-md space-y-4 rounded-3xl bg-brand-gray-100 dark:bg-brand-gray-900 p-8"
            #default="{ errors, processing }"
        >
            <div class="flex items-center gap-4">
                <img
                    v-if="logoUrl"
                    :src="logoUrl"
                    alt=""
                    class="h-12 w-auto max-w-44 object-contain"
                />
                <div>
                    <h1 class="text-2xl font-semibold">Terminal</h1>
                    <p class="text-sm text-brand-gray-600 dark:text-brand-gray-400">Staff sign in</p>
                </div>
            </div>
            <label class="block text-sm">
                Email
                <input
                    name="email"
                    type="email"
                    autocomplete="username"
                    class="mt-1 h-14 w-full rounded-2xl bg-brand-gray-200 dark:bg-brand-gray-800 px-4"
                />
            </label>
            <p v-if="errors.email" class="text-sm text-rose-400">{{ errors.email }}</p>
            <label class="block text-sm">
                Password
                <input
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    class="mt-1 h-14 w-full rounded-2xl bg-brand-gray-200 dark:bg-brand-gray-800 px-4"
                />
            </label>
            <button
                type="submit"
                class="h-16 w-full rounded-2xl bg-emerald-500 text-lg font-semibold text-brand-gray-950"
                :disabled="processing"
            >
                Sign in
            </button>
        </Form>
    </div>
</template>
