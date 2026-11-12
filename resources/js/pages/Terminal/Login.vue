<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { login } from '@/routes/staff';

defineProps<{
    logoUrl: string | null;
}>();
</script>

<template>
    <div
        class="bg-brand-gray-50 dark:bg-brand-gray-950 text-brand-gray-900 dark:text-brand-gray-100 flex min-h-screen items-center justify-center p-6"
    >
        <Head title="Terminal sign in" />
        <div class="absolute top-4 right-4">
            <LocaleSwitcher />
        </div>
        <Form
            v-bind="login.form.post()"
            class="bg-brand-gray-100 dark:bg-brand-gray-900 w-full max-w-md space-y-4 rounded-3xl p-8"
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
                    <p
                        class="text-brand-gray-600 dark:text-brand-gray-400 text-sm"
                    >
                        Staff sign in
                    </p>
                </div>
            </div>
            <label class="block text-sm">
                Email
                <input
                    name="email"
                    type="email"
                    autocomplete="username"
                    class="border-brand-gray-400 bg-brand-gray-200 focus:border-brand-primary-600 dark:border-brand-gray-700 dark:bg-brand-gray-800 dark:focus:border-brand-primary-400 mt-1 h-14 w-full rounded-2xl border px-4 focus:outline-none"
                />
            </label>
            <p v-if="errors.email" class="text-sm text-rose-400">
                {{ errors.email }}
            </p>
            <label class="block text-sm">
                Password
                <input
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    class="border-brand-gray-400 bg-brand-gray-200 focus:border-brand-primary-600 dark:border-brand-gray-700 dark:bg-brand-gray-800 dark:focus:border-brand-primary-400 mt-1 h-14 w-full rounded-2xl border px-4 focus:outline-none"
                />
            </label>
            <button
                type="submit"
                class="bg-brand-primary-600 hover:bg-brand-primary-700 dark:bg-brand-primary-400 dark:text-brand-gray-950 dark:hover:bg-brand-primary-300 h-16 w-full rounded-2xl text-lg font-semibold text-white"
                :disabled="processing"
            >
                Sign in
            </button>
        </Form>
    </div>
</template>
