<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import TerminalAssistantTurnAction from '@/actions/Embiken/BikeRentals/Actions/TerminalAssistantTurnAction';

type ChatMessage = { role: 'user' | 'assistant'; content: string };
type AssistantResponse = { message: string };
type AssistantUi = {
    assistant_open: string;
    assistant_title: string;
    assistant_close: string;
    assistant_input: string;
    assistant_send: string;
    assistant_thinking: string;
    assistant_intro: string;
    assistant_privacy: string;
    assistant_unavailable: string;
    assistant_clear: string;
};

const props = defineProps<{ ui: AssistantUi }>();
const isOpen = ref(false);
const input = ref('');
const error = ref('');
const messages = ref<ChatMessage[]>([]);
const conversation = ref<ChatMessage[]>([]);
const scrollContainer = ref<HTMLElement | null>(null);
const request = useHttp<{ messages: ChatMessage[] }, AssistantResponse>({
    messages: [],
});

watch(
    () => messages.value.length,
    async () => {
        await nextTick();
        scrollContainer.value?.scrollTo({
            top: scrollContainer.value.scrollHeight,
            behavior: 'smooth',
        });
    },
);

function send(): void {
    const content = input.value.trim();

    if (content === '' || request.processing) {
        return;
    }

    const userMessage: ChatMessage = { role: 'user', content };
    messages.value.push(userMessage);
    conversation.value.push(userMessage);
    input.value = '';
    error.value = '';

    request
        .transform(() => ({ messages: conversation.value }))
        .post(TerminalAssistantTurnAction.url(), {
            onSuccess: (response) => {
                const assistantMessage: ChatMessage = {
                    role: 'assistant',
                    content: response.message,
                };
                messages.value.push(assistantMessage);
                conversation.value.push(assistantMessage);
            },
            onHttpException: () => {
                error.value = props.ui.assistant_unavailable;
            },
            onNetworkError: () => {
                error.value = props.ui.assistant_unavailable;
            },
        });
}

function clearConversation(): void {
    messages.value = [];
    conversation.value = [];
    error.value = '';
    input.value = '';
}
</script>

<template>
    <div class="fixed right-4 bottom-4 z-40">
        <button
            v-if="!isOpen"
            type="button"
            class="bg-brand-primary-600 hover:bg-brand-primary-700 dark:bg-brand-primary-400 dark:text-brand-gray-950 min-h-16 rounded-full px-6 text-lg font-bold text-white shadow-xl"
            @click="isOpen = true"
        >
            {{ ui.assistant_open }}
        </button>

        <section
            v-else
            class="border-brand-gray-300 bg-brand-gray-50 dark:border-brand-gray-700 dark:bg-brand-gray-950 flex h-[min(78vh,44rem)] w-[min(94vw,30rem)] flex-col overflow-hidden rounded-2xl border shadow-2xl"
            :aria-label="ui.assistant_title"
        >
            <header
                class="border-brand-gray-300 bg-brand-gray-100 dark:border-brand-gray-800 dark:bg-brand-gray-900 flex min-h-16 items-center gap-3 border-b px-4"
            >
                <h2 class="flex-1 text-lg font-bold">
                    {{ ui.assistant_title }}
                </h2>
                <button
                    type="button"
                    class="text-brand-gray-700 hover:bg-brand-gray-200 dark:text-brand-gray-200 dark:hover:bg-brand-gray-800 min-h-12 rounded-xl px-3 text-sm font-semibold"
                    @click="clearConversation"
                >
                    {{ ui.assistant_clear }}
                </button>
                <button
                    type="button"
                    class="bg-brand-gray-200 dark:bg-brand-gray-800 min-h-12 min-w-12 rounded-xl text-xl font-bold"
                    :aria-label="ui.assistant_close"
                    @click="isOpen = false"
                >
                    ×
                </button>
            </header>

            <ol
                ref="scrollContainer"
                class="flex flex-1 flex-col gap-3 overflow-y-auto p-4"
                aria-live="polite"
                :aria-label="ui.assistant_title"
            >
                <li
                    v-if="messages.length === 0"
                    class="bg-brand-gray-100 text-brand-gray-700 dark:bg-brand-gray-900 dark:text-brand-gray-300 rounded-xl p-4 text-sm"
                >
                    {{ ui.assistant_intro }}
                </li>
                <li
                    v-for="(message, index) in messages"
                    :key="index"
                    class="max-w-[92%] rounded-2xl px-4 py-3 text-base break-words whitespace-pre-wrap"
                    :class="
                        message.role === 'user'
                            ? 'bg-brand-primary-600 dark:bg-brand-primary-400 dark:text-brand-gray-950 self-end text-white'
                            : 'bg-brand-gray-200 text-brand-gray-950 dark:bg-brand-gray-800 dark:text-brand-gray-50 self-start'
                    "
                >
                    {{ message.content }}
                </li>
                <li
                    v-if="request.processing"
                    class="bg-brand-gray-200 dark:bg-brand-gray-800 self-start rounded-2xl px-4 py-3 text-base"
                >
                    {{ ui.assistant_thinking }}
                </li>
                <li
                    v-if="error"
                    class="rounded-xl border border-red-400 bg-red-50 p-3 text-sm font-semibold text-red-900 dark:bg-red-950 dark:text-red-100"
                    role="alert"
                >
                    {{ error }}
                </li>
            </ol>

            <div
                class="border-brand-gray-300 dark:border-brand-gray-800 border-t p-3"
            >
                <p
                    class="text-brand-gray-600 dark:text-brand-gray-400 mb-2 text-xs"
                >
                    {{ ui.assistant_privacy }}
                </p>
                <form class="flex items-end gap-2" @submit.prevent="send">
                    <label class="sr-only" for="terminal-assistant-input">
                        {{ ui.assistant_input }}
                    </label>
                    <textarea
                        id="terminal-assistant-input"
                        v-model="input"
                        class="border-brand-gray-400 text-brand-gray-950 placeholder:text-brand-gray-500 focus-visible:outline-brand-primary-600 dark:border-brand-gray-700 dark:bg-brand-gray-900 dark:text-brand-gray-50 max-h-32 min-h-14 flex-1 resize-y rounded-xl border bg-white p-3 text-base focus-visible:outline-2 focus-visible:outline-offset-2"
                        :placeholder="ui.assistant_input"
                        maxlength="2000"
                        rows="2"
                        @keydown.enter.exact.prevent="send"
                    />
                    <button
                        type="submit"
                        class="bg-brand-primary-600 dark:bg-brand-primary-400 dark:text-brand-gray-950 min-h-14 rounded-xl px-4 text-base font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="request.processing || input.trim() === ''"
                    >
                        {{ ui.assistant_send }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</template>
