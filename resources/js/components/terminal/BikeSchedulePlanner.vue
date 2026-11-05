<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { getEnumLabel, getStatusBadgeClasses } from '@/lib/enums';
import {
    useDayStore,
    type DayBike,
    type DayLine,
    type DayReservation,
} from '@/stores/day';
import { ReservationStage } from '@/types/domain';
import { terminal } from '@/routes';

type PlannerLine = {
    reservation: DayReservation;
    line: DayLine;
    buffer_minutes: number;
};

type PlannerUi = {
    reservations_view: string;
    planner_title: string;
    planner_description: string;
    planner_previous_day: string;
    planner_next_day: string;
    planner_today: string;
    planner_bike: string;
    planner_model_size: string;
    planner_situation: string;
    planner_turnaround_buffer: string;
    planner_unassigned_lines: string;
    planner_unassigned: string;
    planner_no_bikes: string;
    planner_no_matching_bikes: string;
    planner_out_of_service: string;
    planner_assigning_to: string;
    planner_select_bike: string;
    planner_resize_column: string;
};

type BikeAssignment = {
    riderLabel: string;
    matchingBikeIds: number[];
    selectableBikeIds: number[];
    conflictingBikeIds: number[];
    selectedBikeId: number | null;
    heightRangeByBikeId: Record<number, string>;
    statusByBikeId: Record<number, string>;
};

const props = defineProps<{
    ui: PlannerUi;
    formatTime: (value: string) => string;
    assignment?: BikeAssignment;
}>();

const emit = defineEmits<{
    reservationSelected: [reservation: DayReservation];
    bikeSelected: [bikeId: number];
}>();

const day = useDayStore();
const nowTimestamp = ref(0);
const plannerClockReady = ref(false);
const plannerColumnWidths = ref<[number, number, number]>([160, 144, 160]);
const activeColumnResize = ref<{
    index: 0 | 1 | 2;
    startX: number;
    startWidth: number;
} | null>(null);
let clockInterval: number | undefined;

const plannerGridTemplate = computed(
    () =>
        `${plannerColumnWidths.value[0]}px ${plannerColumnWidths.value[1]}px ${plannerColumnWidths.value[2]}px minmax(900px, 1fr)`,
);

function plannerColumnStyle(index: 0 | 1 | 2): Record<string, string> {
    const left = plannerColumnWidths.value
        .slice(0, index)
        .reduce((width, columnWidth) => width + columnWidth, 0);

    return {
        width: `${plannerColumnWidths.value[index]}px`,
        left: `${left}px`,
    };
}

function beginPlannerColumnResize(event: PointerEvent, index: 0 | 1 | 2): void {
    if (!(event.currentTarget instanceof HTMLElement)) {
        return;
    }

    event.preventDefault();
    activeColumnResize.value = {
        index,
        startX: event.clientX,
        startWidth: plannerColumnWidths.value[index],
    };
    event.currentTarget.setPointerCapture(event.pointerId);
}

function updatePlannerColumnResize(event: PointerEvent): void {
    if (activeColumnResize.value === null) {
        return;
    }

    const { index, startX, startWidth } = activeColumnResize.value;
    const minimumWidth = index === 0 ? 112 : 120;
    const maximumWidth = index === 2 ? 360 : 320;

    plannerColumnWidths.value[index] = Math.min(
        Math.max(startWidth + event.clientX - startX, minimumWidth),
        maximumWidth,
    );
}

function endPlannerColumnResize(event: PointerEvent): void {
    if (
        event.currentTarget instanceof HTMLElement &&
        event.currentTarget.hasPointerCapture(event.pointerId)
    ) {
        event.currentTarget.releasePointerCapture(event.pointerId);
    }

    activeColumnResize.value = null;
}

function resizePlannerColumnWithKeyboard(
    event: KeyboardEvent,
    index: 0 | 1 | 2,
): void {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
    }

    event.preventDefault();
    const direction = event.key === 'ArrowRight' ? 1 : -1;
    const minimumWidth = index === 0 ? 112 : 120;
    const maximumWidth = index === 2 ? 360 : 320;

    plannerColumnWidths.value[index] = Math.min(
        Math.max(
            plannerColumnWidths.value[index] + direction * 16,
            minimumWidth,
        ),
        maximumWidth,
    );
}

onMounted(() => {
    nowTimestamp.value = Date.now();
    plannerClockReady.value = true;
    clockInterval = window.setInterval(() => {
        nowTimestamp.value = Date.now();
    }, 60_000);
});

onUnmounted(() => {
    if (clockInterval !== undefined) {
        window.clearInterval(clockInterval);
    }
});

const scheduleHours = computed(() => {
    const start = plannerWindow.value.startsAt;
    const end = plannerWindow.value.endsAt;
    const hours: { timestamp: number; label: string }[] = [];

    for (let timestamp = start; timestamp < end; timestamp += 60 * 60 * 1000) {
        hours.push({
            timestamp,
            label: props.formatTime(new Date(timestamp).toISOString()),
        });
    }

    return hours;
});

const scheduleColumnTemplate = computed(() => {
    const hour = 60 * 60 * 1000;

    return scheduleHours.value
        .map((slot) => {
            const slotDuration =
                Math.min(slot.timestamp + hour, plannerWindow.value.endsAt) -
                slot.timestamp;

            return `${slotDuration / hour}fr`;
        })
        .join(' ');
});

const plannerWindow = computed(() => {
    const calendarStart = Date.parse(day.scheduleStartsAt);
    const calendarEnd = Date.parse(day.scheduleEndsAt);
    const businessStart =
        day.businessHours.length > 0
            ? Math.min(
                  ...day.businessHours.map((interval) =>
                      Date.parse(interval.starts_at),
                  ),
              )
            : calendarStart;
    const businessClose =
        day.businessHours.length > 0
            ? Math.max(
                  ...day.businessHours.map((interval) =>
                      Date.parse(interval.ends_at),
                  ),
              )
            : (day.businessHoursBounds
                  ? Date.parse(day.businessHoursBounds.ends_at)
                  : calendarEnd);
    const businessWindowStart =
        day.businessHours.length > 0
            ? businessStart
            : (day.businessHoursBounds
                  ? Date.parse(day.businessHoursBounds.starts_at)
                  : calendarStart);
    const hour = 60 * 60 * 1000;
    const endsAt =
        day.businessHours.length > 0 || day.businessHoursBounds !== null
            ? businessClose + hour
            : calendarEnd;
    let startsAt = Math.floor(businessWindowStart / hour) * hour;

    if (props.assignment !== undefined && isViewingToday.value) {
        const currentHour = Math.floor(nowTimestamp.value / hour) * hour;
        startsAt = Math.max(startsAt, currentHour);

        if (startsAt >= endsAt) {
            startsAt = Math.max(
                Math.floor(businessWindowStart / hour) * hour,
                endsAt - hour,
            );
        }
    }

    return { startsAt, endsAt };
});

const businessHighlightStyles = computed(() => {
    if (day.businessHours.length === 0) {
        return [];
    }

    const now = nowTimestamp.value;
    const isToday = isViewingToday.value;
    const today = new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        timeZone: day.timezone,
    }).format(new Date());

    if (day.scheduleDate < today) {
        return [];
    }

    if (isToday && !plannerClockReady.value) {
        return [];
    }

    const duration = plannerWindow.value.endsAt - plannerWindow.value.startsAt;

    return day.businessHours.flatMap((interval) => {
        const intervalStart = Date.parse(interval.starts_at);
        const intervalEnd = Date.parse(interval.ends_at);
        const start = Math.max(
            isToday ? Math.max(now, intervalStart) : intervalStart,
            plannerWindow.value.startsAt,
        );
        const end = Math.min(intervalEnd, plannerWindow.value.endsAt);

        if (end <= start) {
            return [];
        }

        return [
            {
                left: `${((start - plannerWindow.value.startsAt) / duration) * 100}%`,
                width: `${((end - start) / duration) * 100}%`,
            },
        ];
    });
});

const closedHourStyles = computed(() => {
    const duration = plannerWindow.value.endsAt - plannerWindow.value.startsAt;
    const openIntervals = businessHighlightStyles.value
        .map((highlight) => {
            const left = Number.parseFloat(highlight.left) / 100;
            const width = Number.parseFloat(highlight.width) / 100;

            return {
                start: left * duration,
                end: (left + width) * duration,
            };
        })
        .sort((left, right) => left.start - right.start)
        .reduce<{ start: number; end: number }[]>((merged, interval) => {
            const previous = merged.at(-1);

            if (previous && interval.start <= previous.end) {
                previous.end = Math.max(previous.end, interval.end);
            } else {
                merged.push(interval);
            }

            return merged;
        }, []);
    const closedIntervals: { start: number; end: number }[] = [];
    let cursor = 0;

    for (const interval of openIntervals) {
        if (interval.start > cursor) {
            closedIntervals.push({ start: cursor, end: interval.start });
        }

        cursor = Math.max(cursor, interval.end);
    }

    if (cursor < duration) {
        closedIntervals.push({ start: cursor, end: duration });
    }

    return closedIntervals.map((interval) => ({
        left: `${(interval.start / duration) * 100}%`,
        width: `${((interval.end - interval.start) / duration) * 100}%`,
    }));
});

const currentTimeMarkerStyle = computed(() => {
    const startsAt = plannerWindow.value.startsAt;
    const endsAt = plannerWindow.value.endsAt;
    const now = nowTimestamp.value;

    if (
        !plannerClockReady.value ||
        !isViewingToday.value ||
        now < startsAt ||
        now >= endsAt
    ) {
        return undefined;
    }

    return {
        left: `${((now - startsAt) / (endsAt - startsAt)) * 100}%`,
    };
});

const plannerBoard = computed(() =>
    day.bikeList
        .filter(
            (bike) =>
                props.assignment === undefined ||
                props.assignment.matchingBikeIds.includes(bike.id),
        )
        .map((bike) => ({ bike, lines: plannerLinesForBike(bike) })),
);

const unassignedLines = computed(unassignedPlannerLines);

const isViewingToday = computed(() => {
    const today = new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        timeZone: day.timezone,
    }).format(new Date());

    return day.scheduleDate === today;
});

function dateWithOffset(date: string, offsetDays: number): string {
    const [year, month, dayOfMonth] = date.split('-').map(Number);
    const shifted = new Date(
        Date.UTC(year, month - 1, dayOfMonth + offsetDays),
    );

    return shifted.toISOString().slice(0, 10);
}

function scheduleUrl(offsetDays: number): string {
    return terminal.url({
        query: { date: dateWithOffset(day.scheduleDate, offsetDays) },
    });
}

function plannerLinesForBike(bike: DayBike): PlannerLine[] {
    return day.reservationList
        .filter(
            (reservation) => reservation.stage !== ReservationStage.Cancelled,
        )
        .flatMap((reservation) =>
            reservation.lines
                .filter((line) => line.bike_id === bike.id)
                .map((line): PlannerLine => ({
                    reservation,
                    line,
                    buffer_minutes: Math.max(
                        day.minimumTurnaroundBufferMinutes,
                        bike.model_padding_minutes,
                    ),
                })),
        )
        .filter(({ reservation, buffer_minutes }) =>
            reservationOverlapsSchedule(reservation, buffer_minutes),
        )
        .sort((left, right) =>
            left.reservation.starts_at.localeCompare(
                right.reservation.starts_at,
            ),
        );
}

function unassignedPlannerLines(): PlannerLine[] {
    return day.reservationList
        .filter(
            (reservation) => reservation.stage !== ReservationStage.Cancelled,
        )
        .flatMap((reservation) =>
            reservation.lines
                .filter((line) => line.bike_id === null)
                .map((line): PlannerLine => ({
                    reservation,
                    line,
                    buffer_minutes: Math.max(
                        day.minimumTurnaroundBufferMinutes,
                        line.model_padding_minutes,
                    ),
                })),
        )
        .filter(({ reservation, buffer_minutes }) =>
            reservationOverlapsSchedule(reservation, buffer_minutes),
        )
        .sort((left, right) =>
            left.reservation.starts_at.localeCompare(
                right.reservation.starts_at,
            ),
        );
}

function reservationOverlapsSchedule(
    reservation: DayReservation,
    bufferMinutes: number,
): boolean {
    const scheduleStart = plannerWindow.value.startsAt;
    const scheduleEnd = plannerWindow.value.endsAt;
    const reservationStart = Date.parse(reservation.starts_at);
    const occupiedUntil =
        Date.parse(reservation.ends_at) + bufferMinutes * 60_000;

    return reservationStart < scheduleEnd && occupiedUntil >= scheduleStart;
}

function plannerSegmentStyle(
    startsAt: string,
    endsAt: string,
    laneIndex: number,
): Record<string, string> {
    const dayStart = plannerWindow.value.startsAt;
    const dayEnd = plannerWindow.value.endsAt;
    const duration = Math.max(dayEnd - dayStart, 1);
    const segmentStart = Math.max(Date.parse(startsAt), dayStart);
    const segmentEnd = Math.min(Math.max(Date.parse(endsAt), dayStart), dayEnd);
    const leftPercent = ((segmentStart - dayStart) / duration) * 100;
    const widthPercent = Math.max(
        ((segmentEnd - segmentStart) / duration) * 100,
        0.35,
    );

    return {
        left: `${leftPercent}%`,
        width: `${widthPercent}%`,
        top: `${4 + laneIndex * 26}px`,
    };
}

function plannerBufferStyle(
    item: PlannerLine,
    laneIndex: number,
): Record<string, string> {
    return plannerSegmentStyle(
        item.reservation.ends_at,
        new Date(
            Date.parse(item.reservation.ends_at) + item.buffer_minutes * 60_000,
        ).toISOString(),
        laneIndex,
    );
}

function plannerLaneHeight(lines: PlannerLine[]): string {
    return `${Math.max(props.assignment === undefined ? 36 : 56, lines.length * 26 + 8)}px`;
}

function isBikeSelectable(bikeId: number): boolean {
    return props.assignment?.selectableBikeIds.includes(bikeId) ?? false;
}

function bikeHasConflict(bikeId: number): boolean {
    return props.assignment?.conflictingBikeIds.includes(bikeId) ?? false;
}

function selectBike(bikeId: number): void {
    if (isBikeSelectable(bikeId)) {
        emit('bikeSelected', bikeId);
    }
}

function reservationRentalOverlapsSchedule(
    reservation: DayReservation,
): boolean {
    return (
        Date.parse(reservation.starts_at) < plannerWindow.value.endsAt &&
        Date.parse(reservation.ends_at) >= plannerWindow.value.startsAt
    );
}

function plannerBikeModelSize(bike: DayBike): string {
    return `${bike.model} · ${bike.variant}`;
}

function plannerBikeBidTooltip(bike: DayBike): string {
    const heightRange = props.assignment?.heightRangeByBikeId[bike.id];

    return heightRange === undefined
        ? bike.bid
        : `${bike.bid} · ${heightRange}`;
}

function plannerBikeModelSizeTooltip(bike: DayBike): string {
    const modelSize = plannerBikeModelSize(bike);
    const heightRange = props.assignment?.heightRangeByBikeId[bike.id];

    return heightRange === undefined
        ? modelSize
        : `${modelSize} · ${heightRange}`;
}

function plannerBikeSituation(bike: DayBike): string {
    const occupyingReservation =
        bike.bike_situation_reservation_id === null
            ? undefined
            : day.reservationById(bike.bike_situation_reservation_id);
    const details = [
        getEnumLabel('bikeSituation', bike.bike_situation_state),
        bike.bike_situation_state !== 'home' &&
        occupyingReservation !== undefined
            ? `#${occupyingReservation.id} · ${occupyingReservation.customer.name}`
            : undefined,
    ].filter(
        (detail): detail is string => detail !== undefined && detail !== '',
    );

    if (!bike.in_service) {
        details.push(props.ui.planner_out_of_service);
    }

    return details.join(' · ');
}

function selectReservation(reservation: DayReservation): void {
    if (props.assignment !== undefined) {
        return;
    }

    emit('reservationSelected', reservation);
}
</script>

<template>
    <section class="flex flex-1 flex-col gap-4 p-4 sm:p-6">
        <section
            class="flex flex-wrap items-center gap-4 rounded-2xl border border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 p-4 sm:p-5"
        >
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold">{{ ui.planner_title }}</h2>
                <p class="mt-1 text-sm text-brand-gray-600 dark:text-brand-gray-400">
                    {{ ui.planner_description }}
                </p>
            </div>
            <div v-if="!assignment" class="flex items-center gap-2">
                <Link
                    :href="scheduleUrl(-1)"
                    class="flex min-h-14 items-center rounded-xl bg-brand-gray-200 dark:bg-brand-gray-800 px-4 text-base font-bold"
                >
                    {{ ui.planner_previous_day }}
                </Link>
                <Link
                    v-if="!isViewingToday"
                    :href="terminal.url()"
                    class="flex min-h-14 items-center rounded-xl bg-brand-primary-600 px-4 text-base font-bold text-white hover:bg-brand-primary-700 dark:bg-brand-primary-400 dark:text-brand-gray-950 dark:hover:bg-brand-primary-300"
                >
                    {{ ui.planner_today }}
                </Link>
                <Link
                    :href="scheduleUrl(1)"
                    class="flex min-h-14 items-center rounded-xl bg-brand-gray-200 dark:bg-brand-gray-800 px-4 text-base font-bold"
                >
                    {{ ui.planner_next_day }}
                </Link>
            </div>
        </section>

        <div
            class="flex flex-wrap items-center gap-4 text-sm text-brand-gray-700 dark:text-brand-gray-300"
            aria-label="Planner legend"
        >
            <span class="flex items-center gap-2">
                <span class="size-4 rounded bg-indigo-500"></span>
                {{ ui.reservations_view }}
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="size-4 rounded border border-amber-300 bg-amber-300/25"
                ></span>
                {{ ui.planner_turnaround_buffer }}
            </span>
        </div>

        <p
            v-if="assignment"
            class="rounded-xl border border-brand-primary-300 bg-brand-primary-50 p-4 text-base font-semibold text-brand-primary-950 dark:border-brand-primary-500/50 dark:bg-brand-primary-950/60 dark:text-brand-primary-100"
        >
            {{
                ui.planner_assigning_to.replace(':rider', assignment.riderLabel)
            }}
        </p>

        <div
            class="flex-1 overflow-x-auto rounded-2xl border border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900"
        >
            <div class="min-w-[1200px]">
                <div
                    class="grid border-b border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900"
                    :style="{ gridTemplateColumns: plannerGridTemplate }"
                >
                    <div
                        class="sticky z-30 flex min-h-8 items-center border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3 text-xs font-bold tracking-wide text-brand-gray-600 dark:text-brand-gray-400 uppercase"
                        :style="plannerColumnStyle(0)"
                    >
                        {{ ui.planner_bike }}
                        <div
                            role="separator"
                            aria-orientation="vertical"
                            tabindex="0"
                            class="focus-visible:outline-inset absolute top-0 right-0 z-40 h-full w-2 cursor-col-resize touch-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-primary-600 dark:focus-visible:outline-brand-primary-400"
                            :aria-label="
                                ui.planner_resize_column.replace(
                                    ':column',
                                    ui.planner_bike,
                                )
                            "
                            :aria-valuemin="112"
                            :aria-valuemax="320"
                            :aria-valuenow="plannerColumnWidths[0]"
                            @pointerdown="beginPlannerColumnResize($event, 0)"
                            @pointermove="updatePlannerColumnResize"
                            @pointerup="endPlannerColumnResize"
                            @pointercancel="endPlannerColumnResize"
                            @keydown="
                                resizePlannerColumnWithKeyboard($event, 0)
                            "
                        ></div>
                    </div>
                    <div
                        class="sticky z-30 flex min-h-8 items-center border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3 text-xs font-bold tracking-wide text-brand-gray-600 dark:text-brand-gray-400 uppercase"
                        :style="plannerColumnStyle(1)"
                    >
                        {{ ui.planner_model_size }}
                        <div
                            role="separator"
                            aria-orientation="vertical"
                            tabindex="0"
                            class="focus-visible:outline-inset absolute top-0 right-0 z-40 h-full w-2 cursor-col-resize touch-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-primary-600 dark:focus-visible:outline-brand-primary-400"
                            :aria-label="
                                ui.planner_resize_column.replace(
                                    ':column',
                                    ui.planner_model_size,
                                )
                            "
                            :aria-valuemin="120"
                            :aria-valuemax="320"
                            :aria-valuenow="plannerColumnWidths[1]"
                            @pointerdown="beginPlannerColumnResize($event, 1)"
                            @pointermove="updatePlannerColumnResize"
                            @pointerup="endPlannerColumnResize"
                            @pointercancel="endPlannerColumnResize"
                            @keydown="
                                resizePlannerColumnWithKeyboard($event, 1)
                            "
                        ></div>
                    </div>
                    <div
                        class="sticky z-30 flex min-h-8 items-center border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3 text-xs font-bold tracking-wide text-brand-gray-600 dark:text-brand-gray-400 uppercase"
                        :style="plannerColumnStyle(2)"
                    >
                        {{ ui.planner_situation }}
                        <div
                            role="separator"
                            aria-orientation="vertical"
                            tabindex="0"
                            class="focus-visible:outline-inset absolute top-0 right-0 z-40 h-full w-2 cursor-col-resize touch-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-primary-600 dark:focus-visible:outline-brand-primary-400"
                            :aria-label="
                                ui.planner_resize_column.replace(
                                    ':column',
                                    ui.planner_situation,
                                )
                            "
                            :aria-valuemin="120"
                            :aria-valuemax="360"
                            :aria-valuenow="plannerColumnWidths[2]"
                            @pointerdown="beginPlannerColumnResize($event, 2)"
                            @pointermove="updatePlannerColumnResize"
                            @pointerup="endPlannerColumnResize"
                            @pointercancel="endPlannerColumnResize"
                            @keydown="
                                resizePlannerColumnWithKeyboard($event, 2)
                            "
                        ></div>
                    </div>
                    <div
                        class="relative grid min-h-8"
                        :style="{
                            gridTemplateColumns: scheduleColumnTemplate,
                        }"
                    >
                        <div
                            v-for="(closedHours, index) in closedHourStyles"
                            :key="`header-closed-hours-${index}`"
                            class="pointer-events-none absolute inset-y-0 z-0 bg-brand-gray-950/10 dark:bg-black/20"
                            :style="closedHours"
                        ></div>
                        <div
                            v-if="currentTimeMarkerStyle"
                            class="pointer-events-none absolute inset-y-0 z-20 w-0.5 bg-brand-primary-600 dark:bg-brand-primary-400"
                            :style="currentTimeMarkerStyle"
                        ></div>
                        <div
                            v-for="hour in scheduleHours"
                            :key="hour.timestamp"
                            class="relative z-10 flex items-center border-l border-brand-gray-300 dark:border-brand-gray-800 px-2 text-xs font-semibold text-brand-gray-600 dark:text-brand-gray-400"
                        >
                            {{ hour.label }}
                        </div>
                    </div>
                </div>

                <div v-if="plannerBoard.length === 0" class="p-8 text-brand-gray-600 dark:text-brand-gray-400">
                    {{
                        assignment
                            ? ui.planner_no_matching_bikes
                            : ui.planner_no_bikes
                    }}
                </div>

                <div
                    v-for="{ bike, lines } in plannerBoard"
                    :key="bike.id"
                    class="grid border-b border-brand-gray-300 dark:border-brand-gray-800 last:border-b-0"
                    :class="
                        assignment?.selectedBikeId === bike.id
                            ? 'bg-brand-primary-100 dark:bg-brand-primary-950/40'
                            : ''
                    "
                    :style="{ gridTemplateColumns: plannerGridTemplate }"
                >
                    <div
                        class="sticky z-30 flex min-h-8 min-w-0 items-center overflow-hidden border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3"
                        :style="plannerColumnStyle(0)"
                        :title="plannerBikeBidTooltip(bike)"
                    >
                        <button
                            v-if="assignment"
                            type="button"
                            class="min-h-14 w-full truncate rounded-lg px-2 text-left font-mono text-base font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-primary-600 dark:focus-visible:outline-brand-primary-400"
                            :class="
                                isBikeSelectable(bike.id)
                                    ? bikeHasConflict(bike.id)
                                        ? 'bg-amber-300 text-brand-gray-950 hover:bg-amber-200'
                                        : 'bg-emerald-400 text-brand-gray-950 hover:bg-emerald-300'
                                    : assignment.selectedBikeId === bike.id
                                      ? 'bg-brand-primary-700 text-white dark:bg-brand-primary-400 dark:text-brand-gray-950'
                                      : 'cursor-not-allowed bg-brand-gray-200 dark:bg-brand-gray-800 text-brand-gray-600 dark:text-brand-gray-500'
                            "
                            :disabled="!isBikeSelectable(bike.id)"
                            :aria-pressed="
                                assignment.selectedBikeId === bike.id
                            "
                            :aria-label="`${ui.planner_select_bike} ${bike.bid}`"
                            :title="plannerBikeBidTooltip(bike)"
                            @click="selectBike(bike.id)"
                        >
                            {{ bike.bid }}
                        </button>
                        <span
                            v-else
                            class="block w-full truncate font-mono text-base font-bold whitespace-nowrap"
                            :title="bike.bid"
                            >{{ bike.bid }}</span
                        >
                    </div>
                    <div
                        class="sticky z-30 flex min-h-8 min-w-0 items-center overflow-hidden border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3"
                        :style="plannerColumnStyle(1)"
                    >
                        <span
                            class="block w-full truncate text-xs whitespace-nowrap text-brand-gray-700 dark:text-brand-gray-300"
                            :title="plannerBikeModelSizeTooltip(bike)"
                        >
                            {{ plannerBikeModelSize(bike) }}
                        </span>
                    </div>
                    <div
                        class="sticky z-30 flex min-h-8 min-w-0 items-center overflow-hidden border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3"
                        :style="plannerColumnStyle(2)"
                    >
                        <div class="flex min-w-0 flex-col">
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-brand-gray-700 dark:text-brand-gray-300"
                                :title="plannerBikeSituation(bike)"
                            >
                                {{ plannerBikeSituation(bike) }}
                            </span>
                            <span
                                v-if="assignment"
                                class="block w-full truncate text-xs font-semibold whitespace-nowrap"
                                :class="
                                    bikeHasConflict(bike.id)
                                        ? 'text-amber-800 dark:text-amber-200'
                                        : 'text-emerald-800 dark:text-emerald-200'
                                "
                            >
                                {{ assignment.statusByBikeId[bike.id] }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="relative isolate bg-brand-gray-100/40 dark:bg-brand-gray-950/40"
                        :style="{ height: plannerLaneHeight(lines) }"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 z-0 grid"
                            :style="{
                                gridTemplateColumns: scheduleColumnTemplate,
                            }"
                        >
                            <div
                                v-for="hour in scheduleHours"
                                :key="`bike-hour-${hour.timestamp}`"
                                class="border-l border-brand-gray-300 dark:border-brand-gray-800"
                            ></div>
                        </div>
                        <div
                            v-for="(closedHours, index) in closedHourStyles"
                            :key="`lane-closed-hours-${index}`"
                            class="pointer-events-none absolute inset-y-0 z-0 bg-brand-gray-950/10 dark:bg-black/20"
                            :style="closedHours"
                        ></div>
                        <div
                            v-if="currentTimeMarkerStyle"
                            class="pointer-events-none absolute inset-y-0 z-20 w-0.5 bg-brand-primary-600 dark:bg-brand-primary-400"
                            :style="currentTimeMarkerStyle"
                        ></div>
                        <template
                            v-for="(item, index) in lines"
                            :key="item.line.id"
                        >
                            <div
                                v-if="
                                    item.buffer_minutes > 0 &&
                                    Date.parse(item.reservation.ends_at) <
                                        plannerWindow.endsAt &&
                                    Date.parse(item.reservation.ends_at) +
                                        item.buffer_minutes * 60000 >=
                                        plannerWindow.startsAt
                                "
                                class="absolute z-0 rounded-r border-y border-r border-amber-300/50 bg-amber-300/20"
                                :style="plannerBufferStyle(item, index)"
                                :title="`${ui.planner_turnaround_buffer} · ${item.buffer_minutes} min`"
                            ></div>
                            <button
                                v-if="
                                    reservationRentalOverlapsSchedule(
                                        item.reservation,
                                    )
                                "
                                type="button"
                                class="absolute z-10 flex min-h-6 items-center overflow-hidden rounded px-2 text-left text-xs font-bold"
                                :class="[
                                    getStatusBadgeClasses(
                                        'reservationStage',
                                        item.reservation.stage,
                                    ),
                                    'hover:ring-2 hover:ring-brand-gray-950/70 focus:ring-2 focus:ring-brand-gray-950 dark:hover:ring-white/70 dark:focus:ring-white',
                                ]"
                                :style="
                                    plannerSegmentStyle(
                                        item.reservation.starts_at,
                                        item.reservation.ends_at,
                                        index,
                                    )
                                "
                                :title="`${item.reservation.customer.name} · ${props.formatTime(item.reservation.starts_at)}–${props.formatTime(item.reservation.ends_at)} · ${item.line.product_label}`"
                                :disabled="assignment !== undefined"
                                @click="selectReservation(item.reservation)"
                            >
                                <span class="truncate"
                                    >{{ item.reservation.customer.name }} ·
                                    {{
                                        props.formatTime(
                                            item.reservation.starts_at,
                                        )
                                    }}–{{
                                        props.formatTime(
                                            item.reservation.ends_at,
                                        )
                                    }}</span
                                >
                            </button>
                        </template>
                    </div>
                </div>

                <section
                    v-if="unassignedLines.length > 0"
                    class="border-t-2 border-dashed border-amber-500/60"
                >
                    <div
                        class="sticky left-0 z-10 bg-brand-gray-200 dark:bg-brand-gray-800 px-4 py-1 text-sm font-bold text-amber-800 dark:text-amber-100"
                    >
                        {{ ui.planner_unassigned_lines }}
                    </div>
                    <div
                        v-for="(item, index) in unassignedLines"
                        :key="`unassigned-${item.line.id}`"
                        class="grid border-t border-brand-gray-300 dark:border-brand-gray-800"
                        :style="{ gridTemplateColumns: plannerGridTemplate }"
                    >
                        <button
                            type="button"
                            class="sticky z-30 flex min-h-8 items-center overflow-hidden border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3 text-left"
                            :style="plannerColumnStyle(0)"
                            :disabled="assignment !== undefined"
                            @click="selectReservation(item.reservation)"
                        >
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-brand-gray-600 dark:text-brand-gray-500"
                                aria-hidden="true"
                                >—</span
                            >
                        </button>
                        <button
                            type="button"
                            class="sticky z-30 flex min-h-8 min-w-0 items-center overflow-hidden border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3 text-left"
                            :style="plannerColumnStyle(1)"
                            :title="item.line.product_label"
                            :disabled="assignment !== undefined"
                            @click="selectReservation(item.reservation)"
                        >
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-brand-gray-700 dark:text-brand-gray-300"
                            >
                                {{ item.line.product_label }}
                            </span>
                        </button>
                        <div
                            class="sticky z-30 flex min-h-8 min-w-0 items-center overflow-hidden border-r border-brand-gray-300 dark:border-brand-gray-800 bg-brand-gray-100 dark:bg-brand-gray-900 px-3"
                            :style="plannerColumnStyle(2)"
                        >
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-brand-gray-600 dark:text-brand-gray-500"
                            >
                                {{ ui.planner_unassigned }}
                            </span>
                        </div>
                        <div class="relative isolate min-h-10 bg-brand-gray-100/40 dark:bg-brand-gray-950/40">
                            <div
                                class="pointer-events-none absolute inset-0 z-0 grid"
                                :style="{
                                    gridTemplateColumns: scheduleColumnTemplate,
                                }"
                            >
                                <div
                                    v-for="hour in scheduleHours"
                                    :key="`unassigned-hour-${hour.timestamp}`"
                                    class="border-l border-brand-gray-300 dark:border-brand-gray-800"
                                ></div>
                            </div>
                            <div
                                v-for="(closedHours, closedHoursIndex) in closedHourStyles"
                                :key="`unassigned-closed-hours-${closedHoursIndex}`"
                                class="pointer-events-none absolute inset-y-0 z-0 bg-brand-gray-950/10 dark:bg-black/20"
                                :style="closedHours"
                            ></div>
                            <div
                                v-if="currentTimeMarkerStyle"
                                class="pointer-events-none absolute inset-y-0 z-20 w-0.5 bg-brand-primary-600 dark:bg-brand-primary-400"
                                :style="currentTimeMarkerStyle"
                            ></div>
                            <div
                                v-if="
                                    item.buffer_minutes > 0 &&
                                    Date.parse(item.reservation.ends_at) <
                                        plannerWindow.endsAt &&
                                    Date.parse(item.reservation.ends_at) +
                                        item.buffer_minutes * 60000 >=
                                        plannerWindow.startsAt
                                "
                                class="absolute z-0 h-8 rounded-r border-y border-r border-amber-300/50 bg-amber-300/20"
                                :style="plannerBufferStyle(item, index)"
                                :title="`${ui.planner_turnaround_buffer} · ${item.buffer_minutes} min`"
                            ></div>
                            <button
                                v-if="
                                    reservationRentalOverlapsSchedule(
                                        item.reservation,
                                    )
                                "
                                type="button"
                                class="absolute z-10 flex h-8 items-center overflow-hidden rounded px-2 text-left text-xs font-bold"
                                :class="[
                                    getStatusBadgeClasses(
                                        'reservationStage',
                                        item.reservation.stage,
                                    ),
                                    'hover:ring-2 hover:ring-brand-gray-950/70 focus:ring-2 focus:ring-brand-gray-950 dark:hover:ring-white/70 dark:focus:ring-white',
                                ]"
                                :style="
                                    plannerSegmentStyle(
                                        item.reservation.starts_at,
                                        item.reservation.ends_at,
                                        0,
                                    )
                                "
                                :title="`${item.reservation.customer.name} · ${item.line.product_label}`"
                                :disabled="assignment !== undefined"
                                @click="selectReservation(item.reservation)"
                            >
                                <span class="truncate"
                                    >{{ item.reservation.customer.name }} ·
                                    {{
                                        props.formatTime(
                                            item.reservation.starts_at,
                                        )
                                    }}–{{
                                        props.formatTime(
                                            item.reservation.ends_at,
                                        )
                                    }}</span
                                >
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>
</template>
