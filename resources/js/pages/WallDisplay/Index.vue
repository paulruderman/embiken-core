<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { useDayStore, type DaySnapshot } from '@/stores/day';
import WallDisplayRealtime from './WallDisplayRealtime.vue';
import TodayShopBoard from './Feeds/TodayShopBoard.vue';
import Standby from './Feeds/Standby.vue';

type Feed = {
    slug: string;
    name: string;
    component: string;
    data: Record<string, unknown>;
};
type DisplayState = {
    state: string;
    display: { id: number; name: string; dark_mode: boolean } | null;
    feed: Feed | null;
};
const props = defineProps<
    DisplayState & {
        dayStore: DaySnapshot | null;
        copy: Record<string, string>;
    }
>();
const day = useDayStore();
if (props.dayStore) day.hydrate(props.dayStore);
const live = ref<DisplayState>({
    state: props.state,
    display: props.display,
    feed: props.feed,
});
const realtimeReady = ref(false);
onMounted(() => {
    realtimeReady.value = true;
});
const moduleComponents = import.meta.glob<DefineComponent>(
    '../../../packages/*/resources/js/**/*.vue',
);
const feedComponent = computed(() => {
    if (live.value.feed?.component === 'core:today-shop-board')
        return TodayShopBoard;
    if (live.value.feed?.component === 'core:standby') return Standby;
    if (live.value.feed?.component) {
        const suffix = `/resources/js/${live.value.feed.component}.vue`;
        const key = Object.keys(moduleComponents).find((path) =>
            path.endsWith(suffix),
        );
        if (key && moduleComponents[key])
            return defineAsyncComponent(moduleComponents[key]);
    }
    return null;
});
const pageTitle = computed(
    () => live.value.display?.name ?? props.copy.page_title,
);
const applyFeedUpdate = (payload: { feed: Feed }): void => {
    if (live.value.feed?.slug === payload.feed.slug)
        live.value.feed = payload.feed;
};
const applyWeatherUpdate = (payload: Record<string, unknown>): void => {
    if (live.value.feed?.slug === 'weather') {
        live.value.feed.data = {
            ...live.value.feed.data,
            ...payload,
        };
    }
};
</script>

<template>
    <main
        :class="[
            'min-h-screen px-6 py-5 text-white',
            live.display?.dark_mode ? 'bg-zinc-950' : 'bg-slate-900',
        ]"
    >
        <Head :title="pageTitle" />
        <WallDisplayRealtime
            v-if="realtimeReady && live.display"
            :tenant-id="day.tenantId"
            :location-id="day.locationId"
            :display-id="live.display.id"
            @display-updated="live = $event"
            @feed-updated="applyFeedUpdate"
            @weather-updated="applyWeatherUpdate"
        />
        <section
            v-if="live.state !== 'ready'"
            class="flex min-h-[80vh] items-center justify-center text-center"
        >
            <h1 class="text-3xl font-semibold">
                {{
                    live.state === 'no_display'
                        ? copy.display_unassigned
                        : live.state === 'feed_unavailable'
                          ? copy.feed_unavailable
                          : copy.not_configured
                }}
            </h1>
        </section>
        <component
            v-else-if="feedComponent && live.feed"
            :is="feedComponent"
            :feed="live.feed"
            :copy="copy"
        />
        <section
            v-else
            class="flex min-h-[80vh] items-center justify-center text-center"
        >
            <h1 class="text-3xl font-semibold">{{ copy.feed_unavailable }}</h1>
        </section>
    </main>
</template>
