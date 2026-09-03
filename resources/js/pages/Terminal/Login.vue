<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/routes/staff/login';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-zinc-950 p-6 text-zinc-100">
        <Head title="Terminal sign in" />
        <Form
            v-bind="store.form()"
            class="w-full max-w-md space-y-4 rounded-3xl bg-zinc-900 p-8"
            #default="{ errors, processing }"
        >
            <h1 class="text-2xl font-semibold">Terminal</h1>
            <p class="text-sm text-zinc-400">Staff sign in</p>
            <label class="block text-sm">
                Email
                <input
                    name="email"
                    type="email"
                    autocomplete="username"
                    class="mt-1 h-14 w-full rounded-2xl bg-zinc-800 px-4"
                />
            </label>
            <p v-if="errors.email" class="text-sm text-rose-400">{{ errors.email }}</p>
            <label class="block text-sm">
                Password
                <input
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    class="mt-1 h-14 w-full rounded-2xl bg-zinc-800 px-4"
                />
            </label>
            <button
                type="submit"
                class="h-16 w-full rounded-2xl bg-emerald-500 text-lg font-semibold text-black"
                :disabled="processing"
            >
                Sign in
            </button>
        </Form>
    </div>
</template>
