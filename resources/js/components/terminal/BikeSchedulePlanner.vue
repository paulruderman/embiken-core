<script setup lang="ts">
import { computed } from 'vue';
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

const scheduleHours = computed(() => {
    const start = Date.parse(day.scheduleStartsAt);
    const end = Date.parse(day.scheduleEndsAt);
    const hours: { timestamp: number; label: string }[] = [];

    for (let timestamp = start; timestamp < end; timestamp += 60 * 60 * 1000) {
        hours.push({
            timestamp,
            label: props.formatTime(new Date(timestamp).toISOString()),
        });
    }

    return hours;
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
    const scheduleStart = Date.parse(day.scheduleStartsAt);
    const scheduleEnd = Date.parse(day.scheduleEndsAt);
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
    const dayStart = Date.parse(day.scheduleStartsAt);
    const dayEnd = Date.parse(day.scheduleEndsAt);
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
        Date.parse(reservation.starts_at) < Date.parse(day.scheduleEndsAt) &&
        Date.parse(reservation.ends_at) >= Date.parse(day.scheduleStartsAt)
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
            class="flex flex-wrap items-center gap-4 rounded-2xl border border-zinc-800 bg-zinc-900 p-4 sm:p-5"
        >
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold">{{ ui.planner_title }}</h2>
                <p class="mt-1 text-sm text-zinc-400">
                    {{ ui.planner_description }}
                </p>
            </div>
            <div v-if="!assignment" class="flex items-center gap-2">
                <Link
                    :href="scheduleUrl(-1)"
                    class="flex min-h-14 items-center rounded-xl bg-zinc-800 px-4 text-base font-bold"
                >
                    {{ ui.planner_previous_day }}
                </Link>
                <Link
                    v-if="!isViewingToday"
                    :href="terminal.url()"
                    class="flex min-h-14 items-center rounded-xl bg-emerald-400 px-4 text-base font-bold text-zinc-950"
                >
                    {{ ui.planner_today }}
                </Link>
                <Link
                    :href="scheduleUrl(1)"
                    class="flex min-h-14 items-center rounded-xl bg-zinc-800 px-4 text-base font-bold"
                >
                    {{ ui.planner_next_day }}
                </Link>
            </div>
        </section>

        <div
            class="flex flex-wrap items-center gap-4 text-sm text-zinc-300"
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
            class="rounded-xl border border-emerald-500/50 bg-emerald-950/60 p-4 text-base font-semibold text-emerald-100"
        >
            {{
                ui.planner_assigning_to.replace(':rider', assignment.riderLabel)
            }}
        </p>

        <div
            class="flex-1 overflow-x-auto rounded-2xl border border-zinc-800 bg-zinc-900"
        >
            <div class="min-w-[1200px]">
                <div
                    class="grid grid-cols-[10rem_9rem_10rem_minmax(0,1fr)] border-b border-zinc-800 bg-zinc-900"
                >
                    <div
                        class="sticky left-0 z-20 flex min-h-8 items-center border-r border-zinc-800 bg-zinc-900 px-3 text-xs font-bold tracking-wide text-zinc-400 uppercase"
                    >
                        {{ ui.planner_bike }}
                    </div>
                    <div
                        class="flex min-h-8 items-center border-r border-zinc-800 bg-zinc-900 px-3 text-xs font-bold tracking-wide text-zinc-400 uppercase"
                    >
                        {{ ui.planner_model_size }}
                    </div>
                    <div
                        class="flex min-h-8 items-center border-r border-zinc-800 bg-zinc-900 px-3 text-xs font-bold tracking-wide text-zinc-400 uppercase"
                    >
                        {{ ui.planner_situation }}
                    </div>
                    <div
                        class="grid min-h-8"
                        :style="{
                            gridTemplateColumns: `repeat(${scheduleHours.length}, minmax(3rem, 1fr))`,
                        }"
                    >
                        <div
                            v-for="hour in scheduleHours"
                            :key="hour.timestamp"
                            class="flex items-center border-l border-zinc-800 px-2 text-xs font-semibold text-zinc-400"
                        >
                            {{ hour.label }}
                        </div>
                    </div>
                </div>

                <div v-if="plannerBoard.length === 0" class="p-8 text-zinc-400">
                    {{
                        assignment
                            ? ui.planner_no_matching_bikes
                            : ui.planner_no_bikes
                    }}
                </div>

                <div
                    v-for="{ bike, lines } in plannerBoard"
                    :key="bike.id"
                    class="grid grid-cols-[10rem_9rem_10rem_minmax(0,1fr)] border-b border-zinc-800 last:border-b-0"
                    :class="
                        assignment?.selectedBikeId === bike.id
                            ? 'bg-emerald-950/40'
                            : ''
                    "
                >
                    <div
                        class="sticky left-0 z-10 flex min-h-8 min-w-0 items-center overflow-hidden border-r border-zinc-800 bg-zinc-900 px-3"
                        :title="plannerBikeBidTooltip(bike)"
                    >
                        <button
                            v-if="assignment"
                            type="button"
                            class="min-h-14 w-full truncate rounded-lg px-2 text-left font-mono text-base font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-300"
                            :class="
                                isBikeSelectable(bike.id)
                                    ? bikeHasConflict(bike.id)
                                        ? 'bg-amber-300 text-zinc-950 hover:bg-amber-200'
                                        : 'bg-emerald-400 text-zinc-950 hover:bg-emerald-300'
                                    : assignment.selectedBikeId === bike.id
                                      ? 'bg-emerald-700 text-white'
                                      : 'cursor-not-allowed bg-zinc-800 text-zinc-500'
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
                        class="flex min-h-8 min-w-0 items-center overflow-hidden border-r border-zinc-800 bg-zinc-900 px-3"
                    >
                        <span
                            class="block w-full truncate text-xs whitespace-nowrap text-zinc-300"
                            :title="plannerBikeModelSizeTooltip(bike)"
                        >
                            {{ plannerBikeModelSize(bike) }}
                        </span>
                    </div>
                    <div
                        class="flex min-h-8 min-w-0 items-center overflow-hidden border-r border-zinc-800 bg-zinc-900 px-3"
                    >
                        <div class="flex min-w-0 flex-col">
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-zinc-300"
                                :title="plannerBikeSituation(bike)"
                            >
                                {{ plannerBikeSituation(bike) }}
                            </span>
                            <span
                                v-if="assignment"
                                class="block w-full truncate text-xs font-semibold whitespace-nowrap"
                                :class="
                                    bikeHasConflict(bike.id)
                                        ? 'text-amber-200'
                                        : 'text-emerald-200'
                                "
                            >
                                {{ assignment.statusByBikeId[bike.id] }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="relative border-l border-zinc-800 bg-zinc-950/40"
                        :style="{
                            height: plannerLaneHeight(lines),
                            backgroundImage:
                                'linear-gradient(to right, rgba(63, 63, 70, 0.45) 1px, transparent 1px)',
                            backgroundSize: `${100 / Math.max(scheduleHours.length, 1)}% 100%`,
                        }"
                    >
                        <template
                            v-for="(item, index) in lines"
                            :key="item.line.id"
                        >
                            <div
                                v-if="
                                    item.buffer_minutes > 0 &&
                                    Date.parse(item.reservation.ends_at) <
                                        Date.parse(day.scheduleEndsAt) &&
                                    Date.parse(item.reservation.ends_at) +
                                        item.buffer_minutes * 60000 >=
                                        Date.parse(day.scheduleStartsAt)
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
                                    'hover:ring-2 hover:ring-white/70 focus:ring-2 focus:ring-white',
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
                        class="sticky left-0 z-10 bg-zinc-800 px-4 py-1 text-sm font-bold text-amber-100"
                    >
                        {{ ui.planner_unassigned_lines }}
                    </div>
                    <div
                        v-for="(item, index) in unassignedLines"
                        :key="`unassigned-${item.line.id}`"
                        class="grid grid-cols-[10rem_9rem_10rem_minmax(0,1fr)] border-t border-zinc-800"
                    >
                        <button
                            type="button"
                            class="sticky left-0 z-10 flex min-h-8 items-center overflow-hidden border-r border-zinc-800 bg-zinc-900 px-3 text-left"
                            :disabled="assignment !== undefined"
                            @click="selectReservation(item.reservation)"
                        >
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-zinc-500"
                                aria-hidden="true"
                                >—</span
                            >
                        </button>
                        <button
                            type="button"
                            class="flex min-h-8 min-w-0 items-center overflow-hidden border-r border-zinc-800 bg-zinc-900 px-3 text-left"
                            :title="item.line.product_label"
                            :disabled="assignment !== undefined"
                            @click="selectReservation(item.reservation)"
                        >
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-zinc-300"
                            >
                                {{ item.line.product_label }}
                            </span>
                        </button>
                        <div
                            class="flex min-h-8 min-w-0 items-center overflow-hidden border-r border-zinc-800 bg-zinc-900 px-3"
                        >
                            <span
                                class="block w-full truncate text-xs whitespace-nowrap text-zinc-500"
                            >
                                {{ ui.planner_unassigned }}
                            </span>
                        </div>
                        <div
                            class="relative min-h-10 border-l border-zinc-800 bg-zinc-950/40"
                            :style="{
                                backgroundImage:
                                    'linear-gradient(to right, rgba(63, 63, 70, 0.45) 1px, transparent 1px)',
                                backgroundSize: `${100 / Math.max(scheduleHours.length, 1)}% 100%`,
                            }"
                        >
                            <div
                                v-if="
                                    item.buffer_minutes > 0 &&
                                    Date.parse(item.reservation.ends_at) <
                                        Date.parse(day.scheduleEndsAt) &&
                                    Date.parse(item.reservation.ends_at) +
                                        item.buffer_minutes * 60000 >=
                                        Date.parse(day.scheduleStartsAt)
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
                                    'hover:ring-2 hover:ring-white/70 focus:ring-2 focus:ring-white',
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
