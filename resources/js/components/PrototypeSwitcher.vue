<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

type Variant = { key: string; name: string };

const props = defineProps<{
    variants: Variant[];
    current: string;
}>();

const emit = defineEmits<{
    select: [key: string];
}>();

function cycle(delta: number): void {
    const keys = props.variants.map((variant) => variant.key);
    const index = keys.indexOf(props.current);
    const next = keys[(index + delta + keys.length) % keys.length];

    if (next) {
        emit('select', next);
    }
}

function onKey(event: KeyboardEvent): void {
    const target = event.target;

    if (
        target instanceof HTMLElement &&
        (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable)
    ) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        cycle(-1);
    }

    if (event.key === 'ArrowRight') {
        cycle(1);
    }
}

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

const show = !import.meta.env.PROD;

const currentName = () =>
    props.variants.find((variant) => variant.key === props.current)?.name ?? '';
</script>

<template>
    <div
        v-if="show"
        class="fixed bottom-4 left-1/2 z-50 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black px-2 py-1 text-white shadow-lg"
    >
        <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-full text-lg"
            aria-label="Previous variant"
            @click="cycle(-1)"
        >
            ←
        </button>
        <div class="min-w-52 px-2 text-center text-sm font-medium">
            {{ current }} ({{ currentName() }})
        </div>
        <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-full text-lg"
            aria-label="Next variant"
            @click="cycle(1)"
        >
            →
        </button>
    </div>
</template>
