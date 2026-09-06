<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import GetRadarFramesAction from '@/actions/Embiken/Weather/Actions/GetRadarFramesAction';

defineProps<{
    latitude?: number | null;
    longitude?: number | null;
}>();

type RadarFrame = { time: number; timeIso: string | null; path: string | null };

const frames = ref<RadarFrame[]>([]);
const source = ref('rainviewer');
const index = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

const http = useHttp();

const current = computed(() => frames.value[index.value] ?? null);

const imageUrl = computed(() => {
    const frame = current.value;
    if (frame === null || frame.path === null) {
        return null;
    }

    return `https://tilecache.rainviewer.com${frame.path}/256/5/9/12/2/1_1.png`;
});

onMounted(() => {
    http.get(GetRadarFramesAction.url(), {
        onSuccess: (response) => {
            const payload = (response as { data?: { source?: string; frames?: RadarFrame[] } }).data
                ?? (response as { source?: string; frames?: RadarFrame[] });
            source.value = payload.source ?? 'rainviewer';
            frames.value = payload.frames ?? [];
            index.value = Math.max(frames.value.length - 1, 0);
        },
    });
    timer = setInterval(() => {
        if (frames.value.length === 0) {
            return;
        }
        index.value = (index.value + 1) % frames.value.length;
    }, 700);
});

onUnmounted(() => {
    if (timer !== null) {
        clearInterval(timer);
    }
});
</script>

<template>
    <div class="overflow-hidden rounded-xl bg-black/50 ring-1 ring-white/15">
        <div class="flex items-center justify-between px-3 py-2 text-xs text-white/60">
            <span>Radar · {{ source }}</span>
            <span v-if="current">{{ current.timeIso }}</span>
        </div>
        <img
            v-if="imageUrl"
            :src="imageUrl"
            alt=""
            class="aspect-video w-full object-cover"
        />
        <div
            v-else
            class="flex aspect-video items-center justify-center text-sm text-white/50"
        >
            {{ frames.length === 0 ? 'Loading radar…' : 'NOAA frames ready' }}
        </div>
    </div>
</template>
