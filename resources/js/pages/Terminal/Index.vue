<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import BrowseTerminalBikeCandidatesAction from '@/actions/Embiken/BikeRentals/Actions/BrowseTerminalBikeCandidatesAction';
import CreateWalkInReservationAction from '@/actions/Embiken/BikeRentals/Actions/CreateWalkInReservationAction';
import PickupAction from '@/actions/Embiken/BikeRentals/Actions/PickupAction';
import TerminalLayout from '@/layouts/TerminalLayout.vue';
import { getEnumLabel, getStatusBadgeClasses } from '@/lib/enums';
import {
    currencyExponent,
    currencyLabel,
    formatMoney,
    parseMoneyInput,
} from '@/lib/money';
import {
    useDayStore,
    type DayBike,
    type DayLine,
    type DayPatch,
    type DayReservation,
    type DaySnapshot,
    type BroadcastLogEntry,
} from '@/stores/day';
import {
    BikeReservationStatus,
    BikeSituation,
    ReservationStage,
} from '@/types/domain';
import { useHttp } from '@inertiajs/vue3';

defineOptions({ layout: TerminalLayout });

type DayPatchResponse = DayPatch & { data?: DayPatch };
type WalkInForm = {
    name: string;
    email: string;
    phone: string;
    bike_ids: number[];
    rider_names: (string | null)[];
    rider_heights_cm: (number | null)[];
    allow_availability_override: boolean;
    confirm_availability_override: boolean;
    owed_minor_units: number;
    starts_at_local: string;
    ends_at_local: string;
};
type BikeCandidate = {
    id: number;
    bid: string;
    model: string;
    variant: string;
    category_id: number;
    category: string;
    in_service: boolean;
    bike_situation_state: DayBike['bike_situation_state'];
    bike_situation_reservation_id: number | null;
    min_ideal_rider_height: number | null;
    max_ideal_rider_height: number | null;
    conflict_reason: string | null;
};
type BikeCandidateResponse = {
    candidates: BikeCandidate[];
    categories: { id: number; name: string }[];
};
type WalkInRider = {
    rider_name: string;
    rider_height_cm: string;
    category_id: number | null;
    bike_id: number | null;
    show_all_sizes: boolean;
};
type PickupForm = { bike_id?: number };
type TerminalUi = {
    event_log: string;
    event_log_title: string;
    event_log_description: string;
    event_log_clear: string;
    event_log_close: string;
    event_log_waiting: string;
    event_summary_generic: string;
    event_summary_bike_created: string;
    event_summary_bike_updated: string;
    event_summary_bike_deleted: string;
    event_summary_reservation_created: string;
    event_summary_reservation_updated: string;
    event_summary_reservation_deleted: string;
    event_summary_id: string;
    event_log_raw_json: string;
    bike_category: string;
    rider_height_cm: string;
    recommended_fit: string;
    show_all_sizes: string;
    hide_all_sizes: string;
    show_unavailable_bikes: string;
    unavailable_override_warning: string;
    confirm_availability_override: string;
    no_available_candidates: string;
    loading_candidates: string;
    rider: string;
    add_rider: string;
    remove_rider: string;
    candidate_parked: string;
    candidate_blocking_service: string;
    candidate_cancelled_out: string;
    candidate_currently_out: string;
    candidate_reservation_conflict: string;
    candidate_variant_sold_out: string;
    candidate_available: string;
    candidate_outside_height: string;
    candidate_no_reservation_owner: string;
    candidate_reservation_owner: string;
    bike: string;
    all_categories: string;
    choose_bike: string;
    choose_bike_for_each_rider: string;
    availability_buffer_note: string;
    availability_refresh_error: string;
    cm: string;
};

const props = defineProps<DaySnapshot>();
const page = usePage();
const locale = page.props.locale;
const terminalUi = page.props.terminal_ui as TerminalUi;
const day = useDayStore();
const search = ref('');
const eventLogOpen = ref(false);
const selectedReservationId = ref<number | null>(null);
const walkInOpen = ref(false);
const pendingLineId = ref<number | null>(null);
const pickupError = ref('');
const pickupBikeByLineId = ref<Record<number, number>>({});
const pickupRequest = useHttp<PickupForm, DayPatchResponse>({});
const walkInOwed = ref('');
const walkInError = ref('');
const clock = ref(Date.now());
let readinessClockInterval: ReturnType<typeof setInterval> | undefined;
const walkInRequest = useHttp<WalkInForm, DayPatchResponse>({
    name: '',
    email: '',
    phone: '',
    bike_ids: [],
    rider_names: [],
    rider_heights_cm: [],
    allow_availability_override: false,
    confirm_availability_override: false,
    owed_minor_units: 0,
    starts_at_local: '',
    ends_at_local: '',
});
const bikeCandidateHttp = useHttp({});
const bikeCandidateResults = ref<BikeCandidateResponse>({
    candidates: [],
    categories: [],
});
const bikeCandidatesLoading = ref(false);
const bikeCandidatesError = ref('');
const showUnavailableBikes = ref(false);
const availabilityOverrideConfirmed = ref(false);
const walkInRiders = ref<WalkInRider[]>([newWalkInRider()]);
let bikeCandidateRequestSequence = 0;

const selectedReservation = computed(() =>
    selectedReservationId.value === null
        ? undefined
        : day.reservationById(selectedReservationId.value),
);

const visibleReservations = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();

    return day.reservationList
        .filter((reservation) => {
            if (query === '') {
                return true;
            }

            const searchable = [
                reservation.customer.name,
                ...reservation.lines.flatMap((line) => {
                    const bike = line.bike_id
                        ? day.bikeById(line.bike_id)
                        : undefined;

                    return [line.product_label, bike?.bid ?? ''];
                }),
            ];

            return searchable.some((value) =>
                value.toLocaleLowerCase().includes(query),
            );
        })
        .sort((left, right) => left.starts_at.localeCompare(right.starts_at));
});

onMounted(() => {
    readinessClockInterval = setInterval(() => {
        clock.value = Date.now();
    }, 30_000);
});

onUnmounted(() => {
    if (readinessClockInterval !== undefined) {
        clearInterval(readinessClockInterval);
    }
});

watch(
    [
        walkInOpen,
        () => walkInRequest.starts_at_local,
        () => walkInRequest.ends_at_local,
        showUnavailableBikes,
    ],
    () => {
        refreshBikeCandidates();
    },
);

const todayLabel = computed(() =>
    new Intl.DateTimeFormat(locale, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        timeZone: day.timezone,
    }).format(new Date()),
);

function formatTime(value: string): string {
    return new Intl.DateTimeFormat(locale, {
        hour: 'numeric',
        minute: '2-digit',
        timeZone: day.timezone,
    }).format(new Date(value));
}

function balanceMessage(reservation: DayReservation): string {
    const difference =
        reservation.owed_minor_units - reservation.paid_minor_units;

    if (difference > 0) {
        return `Balance due ${formatMoney(difference, reservation.currency, locale)}`;
    }

    return `Refund or credit ${formatMoney(Math.abs(difference), reservation.currency, locale)}`;
}

function bikeFor(line: DayLine): DayBike | undefined {
    return line.bike_id === null ? undefined : day.bikeById(line.bike_id);
}

function displayBikeForLine(line: DayLine): DayBike | undefined {
    const assignedBike = bikeFor(line);

    if (assignedBike !== undefined) {
        return assignedBike;
    }

    const selectedBikeId = pickupBikeByLineId.value[line.id];

    return selectedBikeId === undefined
        ? undefined
        : day.bikeById(selectedBikeId);
}

function occupyingReservation(
    bike: DayBike,
    contextReservationId?: number,
): DayReservation | undefined {
    return bike.bike_situation_state === BikeSituation.Home ||
        bike.bike_situation_reservation_id === null ||
        bike.bike_situation_reservation_id === contextReservationId
        ? undefined
        : day.reservationById(bike.bike_situation_reservation_id);
}

function occupyingReservationLabel(
    bike: DayBike,
    contextReservationId?: number,
): string | undefined {
    const reservation = occupyingReservation(bike, contextReservationId);

    return reservation === undefined
        ? undefined
        : `#${reservation.id} · ${reservation.customer.name}`;
}

function readinessIssues(reservation: DayReservation, line: DayLine): string[] {
    if (
        reservation.stage === ReservationStage.Cancelled ||
        line.status !== BikeReservationStatus.Assigned
    ) {
        return [];
    }

    return line.readiness_issues;
}

function readinessIssuesForLine(
    reservation: DayReservation,
    line: DayLine,
): string[] {
    if (
        line.bike_id === null &&
        pickupBikeByLineId.value[line.id] !== undefined
    ) {
        return [];
    }

    return readinessIssues(reservation, line);
}

function reservationNeedsAttention(reservation: DayReservation): boolean {
    return (
        (isWithinReadinessWindow(reservation) ||
            isBeingPrepared(reservation)) &&
        reservation.lines.some(
            (line) => readinessIssuesForLine(reservation, line).length > 0,
        )
    );
}

function isBeingPrepared(reservation: DayReservation): boolean {
    return reservation.lines.some((line) => {
        const bike = bikeFor(line);

        return (
            bike?.bike_situation_reservation_id === reservation.id &&
            (bike.bike_situation_state === BikeSituation.Prepping ||
                bike.bike_situation_state === BikeSituation.Staged)
        );
    });
}

function isWithinReadinessWindow(reservation: DayReservation): boolean {
    const windowMinutes = day.reservationReadinessWindowMinutes;
    const startsAt = Date.parse(reservation.starts_at);

    return (
        windowMinutes > 0 &&
        reservation.stage !== ReservationStage.Cancelled &&
        startsAt >= clock.value &&
        startsAt <= clock.value + windowMinutes * 60_000
    );
}

function readinessLabel(issue: string): string {
    return getEnumLabel('bikeReadinessIssue', issue);
}

function canPickUp(reservation: DayReservation, line: DayLine): boolean {
    if (
        reservation.stage === ReservationStage.Cancelled ||
        line.status !== BikeReservationStatus.Assigned
    ) {
        return false;
    }

    const selectedBikeId = line.bike_id ?? pickupBikeByLineId.value[line.id];
    const bike =
        selectedBikeId === undefined ? undefined : day.bikeById(selectedBikeId);
    const bikeIsReadyForThisReservation =
        line.bike_id !== null
            ? readinessIssues(reservation, line).length === 0
            : bike?.in_service === true &&
              (bike.bike_situation_state === BikeSituation.Home ||
                  ((bike.bike_situation_state === BikeSituation.Prepping ||
                      bike.bike_situation_state === BikeSituation.Staged) &&
                      bike.bike_situation_reservation_id === reservation.id));

    return Boolean(
        bike?.variant_id === line.product_id && bikeIsReadyForThisReservation,
    );
}

function openReservation(reservation: DayReservation): void {
    selectedReservationId.value = reservation.id;
    pickupError.value = '';
}

function backToQueue(): void {
    selectedReservationId.value = null;
    walkInOpen.value = false;
    pickupError.value = '';
    walkInError.value = '';
}

function pickUpLine(line: DayLine): void {
    const reservation = selectedReservation.value;
    const bikeId = line.bike_id ?? pickupBikeByLineId.value[line.id];

    if (
        !reservation ||
        !canPickUp(reservation, line) ||
        pendingLineId.value !== null
    ) {
        return;
    }

    pendingLineId.value = line.id;
    pickupError.value = '';

    pickupRequest
        .transform(() => (bikeId === undefined ? {} : { bike_id: bikeId }))
        .post(PickupAction.url({ line: line.id }), {
            onSuccess: (response) => {
                day.applyPatch(response.data ?? response);
                delete pickupBikeByLineId.value[line.id];
            },
            onError: (errors) => {
                pickupError.value = Object.values(errors).join(' ');
            },
            onHttpException: () => {
                pickupError.value = 'Pickup could not be recorded. Try again.';
            },
            onNetworkError: () => {
                pickupError.value =
                    'Connection lost. Check the bike status before trying again.';
            },
            onFinish: () => {
                pendingLineId.value = null;
            },
        });
}

function errorMessage(errors: object): string {
    return Object.values(errors)
        .flatMap((value) => (Array.isArray(value) ? value : [value]))
        .filter((value): value is string => typeof value === 'string')
        .join(' ');
}

function startWalkIn(): void {
    walkInError.value = '';
    const startsAt = new Date();
    const endsAt = new Date(startsAt.getTime() + 2 * 60 * 60 * 1000);
    walkInRequest.starts_at_local = dateTimeInTimezone(startsAt, day.timezone);
    walkInRequest.ends_at_local = dateTimeInTimezone(endsAt, day.timezone);
    walkInRiders.value = [newWalkInRider()];
    showUnavailableBikes.value = false;
    availabilityOverrideConfirmed.value = false;
    walkInOpen.value = true;
}

function newWalkInRider(): WalkInRider {
    return {
        rider_name: '',
        rider_height_cm: '',
        category_id: null,
        bike_id: null,
        show_all_sizes: false,
    };
}

function refreshBikeCandidates(): void {
    if (
        !walkInOpen.value ||
        walkInRequest.starts_at_local === '' ||
        walkInRequest.ends_at_local === ''
    ) {
        return;
    }

    bikeCandidatesLoading.value = true;
    bikeCandidatesError.value = '';
    const requestSequence = ++bikeCandidateRequestSequence;

    bikeCandidateHttp.get(
        BrowseTerminalBikeCandidatesAction.url({
            query: {
                starts_at_local: walkInRequest.starts_at_local,
                ends_at_local: walkInRequest.ends_at_local,
                include_unavailable: showUnavailableBikes.value ? 1 : 0,
            },
        }),
        {
            onSuccess: (response) => {
                if (requestSequence !== bikeCandidateRequestSequence) {
                    return;
                }

                const candidateResponse = response as {
                    data?: BikeCandidateResponse;
                    candidates?: BikeCandidateResponse['candidates'];
                    categories?: BikeCandidateResponse['categories'];
                };
                bikeCandidateResults.value = candidateResponse.data ?? {
                    candidates: candidateResponse.candidates ?? [],
                    categories: candidateResponse.categories ?? [],
                };
            },
            onError: () => {
                if (requestSequence !== bikeCandidateRequestSequence) {
                    return;
                }

                bikeCandidatesError.value = terminalUi.availability_refresh_error;
            },
            onFinish: () => {
                if (requestSequence === bikeCandidateRequestSequence) {
                    bikeCandidatesLoading.value = false;
                }
            },
        },
    );
}

function riderHeight(rider: WalkInRider): number | null {
    const height = Number.parseInt(rider.rider_height_cm, 10);

    return Number.isFinite(height) ? height : null;
}

function candidateFitsHeight(
    candidate: BikeCandidate,
    rider: WalkInRider,
): boolean {
    const height = riderHeight(rider);

    return (
        height === null ||
        ((candidate.min_ideal_rider_height === null ||
            height >= candidate.min_ideal_rider_height) &&
            (candidate.max_ideal_rider_height === null ||
                height <= candidate.max_ideal_rider_height))
    );
}

function candidatesForRider(index: number): BikeCandidate[] {
    const rider = walkInRiders.value[index];
    const selectedByOtherRiders = new Set(
        walkInRiders.value
            .filter((_, riderIndex) => riderIndex !== index)
            .map((otherRider) => otherRider.bike_id)
            .filter((bikeId): bikeId is number => bikeId !== null),
    );

    return bikeCandidateResults.value.candidates
        .filter((candidate) =>
            rider.category_id === null ||
            candidate.category_id === rider.category_id,
        )
        .filter((candidate) => !selectedByOtherRiders.has(candidate.id))
        .filter((candidate) =>
            rider.show_all_sizes || candidateFitsHeight(candidate, rider),
        )
        .sort((left, right) => {
            const leftRecommended = candidateFitsHeight(left, rider) ? 0 : 1;
            const rightRecommended = candidateFitsHeight(right, rider) ? 0 : 1;

            return leftRecommended - rightRecommended || left.bid.localeCompare(right.bid);
        });
}

function candidateConflictLabel(reason: string | null): string {
    if (reason === null) {
        return terminalUi.candidate_available;
    }

    const labels: Record<string, string> = {
        parked: terminalUi.candidate_parked,
        blocking_service: terminalUi.candidate_blocking_service,
        cancelled_out: terminalUi.candidate_cancelled_out,
        currently_out: terminalUi.candidate_currently_out,
        reservation_conflict: terminalUi.candidate_reservation_conflict,
        variant_sold_out: terminalUi.candidate_variant_sold_out,
    };

    return labels[reason] ?? reason;
}

function candidateOptionLabel(
    candidate: BikeCandidate,
    rider: WalkInRider,
): string {
    const availability = candidateConflictLabel(candidate.conflict_reason);
    const fit = candidateFitsHeight(candidate, rider)
        ? ''
        : ` · ${terminalUi.candidate_outside_height}`;

    return `${candidate.bid} · ${candidate.model} · ${candidate.variant} · ${availability}${fit}`;
}

function hasSelectedUnavailableBike(): boolean {
    return walkInRiders.value.some((rider) =>
        bikeCandidateResults.value.candidates.some(
            (candidate) =>
                candidate.id === rider.bike_id &&
                candidate.conflict_reason !== null,
        ),
    );
}

function candidateOwningReservationLabel(bikeId: number): string {
    const candidate = bikeCandidateResults.value.candidates.find(
        (bikeCandidate) => bikeCandidate.id === bikeId,
    );

    if (
        candidate === undefined ||
        candidate.bike_situation_state === BikeSituation.Home
    ) {
        return '';
    }

    const owner = candidate.bike_situation_reservation_id === null
        ? undefined
        : day.reservationById(candidate.bike_situation_reservation_id);

    return owner === undefined
        ? terminalUi.candidate_no_reservation_owner
        : terminalUi.candidate_reservation_owner.replace(':name', owner.customer.name);
}

function addWalkInRider(): void {
    walkInRiders.value.push(newWalkInRider());
}

function removeWalkInRider(index: number): void {
    if (walkInRiders.value.length <= 1) {
        return;
    }

    walkInRiders.value.splice(index, 1);
}

function dateTimeInTimezone(value: Date, timezone: string): string {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-CA', {
            timeZone: timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(value)
            .map(({ type, value: part }) => [type, part]),
    );

    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
}

function submitWalkIn(): void {
    const selectedRiders = walkInRiders.value.filter(
        (rider): rider is WalkInRider & { bike_id: number } =>
            rider.bike_id !== null,
    );
    const selectedBikes = selectedRiders.map((rider) => rider.bike_id);
    const hasUnavailableSelection = hasSelectedUnavailableBike();

    if (selectedRiders.length !== walkInRiders.value.length) {
        walkInError.value = terminalUi.choose_bike_for_each_rider;
        return;
    }

    if (hasUnavailableSelection && !availabilityOverrideConfirmed.value) {
        walkInError.value = terminalUi.confirm_availability_override;
        return;
    }

    let owedMinorUnits: number;
    try {
        owedMinorUnits = parseMoneyInput(
            walkInOwed.value,
            day.currency,
            locale,
        );
    } catch (error) {
        walkInError.value =
            error instanceof Error ? error.message : 'Enter a valid amount.';
        return;
    }

    walkInRequest
        .transform((data) => ({
            ...data,
            bike_ids: selectedBikes,
            rider_names: selectedRiders.map((rider) =>
                rider.rider_name.trim() === '' ? null : rider.rider_name.trim(),
            ),
            rider_heights_cm: selectedRiders.map((rider) =>
                riderHeight(rider),
            ),
            allow_availability_override: hasUnavailableSelection,
            confirm_availability_override: hasUnavailableSelection && availabilityOverrideConfirmed.value,
            owed_minor_units: owedMinorUnits,
        }))
        .post(CreateWalkInReservationAction.url(), {
            onSuccess: (response) => {
                const patch = response.data ?? response;
                day.applyPatch(patch);

                if (
                    props.bike_assignment_policy === 'pickup_only' &&
                    patch.reservation
                ) {
                    patch.reservation.lines.forEach((line, index) => {
                        const bikeId = selectedBikes[index];

                        if (bikeId !== undefined) {
                            pickupBikeByLineId.value[line.id] = bikeId;
                        }
                    });
                }

                if (patch.reservation) {
                    selectedReservationId.value = patch.reservation.id;
                }

                walkInOpen.value = false;
                walkInOwed.value = '';
                walkInRequest.name = '';
                walkInRequest.email = '';
                walkInRequest.phone = '';
                walkInRequest.bike_ids = [];
                walkInRequest.rider_names = [];
                walkInRequest.rider_heights_cm = [];
                walkInRequest.allow_availability_override = false;
                walkInRequest.confirm_availability_override = false;
                walkInRequest.starts_at_local = '';
                walkInRequest.ends_at_local = '';
                walkInRiders.value = [newWalkInRider()];
                bikeCandidateResults.value = { candidates: [], categories: [] };
                showUnavailableBikes.value = false;
                availabilityOverrideConfirmed.value = false;
                walkInError.value = '';
            },
            onError: (errors) => {
                walkInError.value = errorMessage(errors);
            },
            onHttpException: () => {
                walkInError.value = 'Walk-in could not be created. Try again.';
            },
            onNetworkError: () => {
                walkInError.value =
                    'Connection lost. Check the reservation list before trying again.';
            },
        });
}

function bikesForLine(line: DayLine): DayBike[] {
    return day.bikeList
        .filter(
            (bike) => bike.in_service && bike.variant_id === line.product_id,
        )
        .sort((left, right) => left.bid.localeCompare(right.bid));
}

function eventSummary(entry: BroadcastLogEntry): string {
    const eventName = entry.event.split(/[\\.:]/).pop() ?? entry.event;
    const summaries: Record<string, string> = {
        BikeCreated: terminalUi.event_summary_bike_created,
        BikeUpdated: terminalUi.event_summary_bike_updated,
        BikeDeleted: terminalUi.event_summary_bike_deleted,
        ReservationCreated: terminalUi.event_summary_reservation_created,
        ReservationUpdated: terminalUi.event_summary_reservation_updated,
        ReservationDeleted: terminalUi.event_summary_reservation_deleted,
    };
    const summary = summaries[eventName];

    if (summary === undefined) {
        return `${terminalUi.event_summary_generic} · ${entry.event}`;
    }

    const payload = entry.payload;

    if (typeof payload === 'object' && payload !== null && 'id' in payload) {
        const id = payload.id;

        if (typeof id === 'string' || typeof id === 'number') {
            return `${summary} · ${terminalUi.event_summary_id} ${id}`;
        }
    }

    return summary;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-950 text-zinc-100">
        <Head title="Terminal" />

        <header
            class="flex min-h-20 items-center gap-4 border-b border-zinc-800 bg-zinc-900 px-4 py-3 sm:px-6"
        >
            <button
                v-if="selectedReservation || walkInOpen"
                type="button"
                class="min-h-14 min-w-24 rounded-xl bg-zinc-700 px-4 text-lg font-semibold"
                @click="backToQueue"
            >
                Back
            </button>
            <div>
                <h1 class="text-2xl font-bold">
                    {{
                        selectedReservation
                            ? 'Reservation'
                            : walkInOpen
                              ? 'New walk-in'
                              : 'Terminal'
                    }}
                </h1>
                <p class="text-sm text-zinc-400">
                    {{
                        selectedReservation || walkInOpen
                            ? todayLabel
                            : `Today · ${todayLabel}`
                    }}
                </p>
            </div>
            <button
                type="button"
                class="ml-auto min-h-14 rounded-xl border border-zinc-600 bg-zinc-800 px-5 text-lg font-bold text-white"
                @click="eventLogOpen = true"
            >
                {{ terminalUi.event_log }} · {{ day.broadcastLog.length }}
            </button>
            <button
                v-if="!selectedReservation && !walkInOpen"
                type="button"
                class="min-h-14 rounded-xl bg-emerald-400 px-5 text-lg font-bold text-zinc-950"
                @click="startWalkIn"
            >
                New walk-in
            </button>
            <div
                v-if="!selectedReservation && !walkInOpen"
                class="hidden text-sm text-zinc-400 sm:block"
            >
                {{ visibleReservations.length }} reservations
            </div>
        </header>

        <main
            v-if="walkInOpen"
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-4 p-4 sm:p-6"
        >
            <form class="flex flex-col gap-4" @submit.prevent="submitWalkIn">
                <section
                    class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5"
                >
                    <h2 class="text-xl font-bold">Customer contact</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label
                            class="flex flex-col gap-2 text-sm font-semibold sm:col-span-2"
                        >
                            Name
                            <input
                                v-model="walkInRequest.name"
                                required
                                autocomplete="name"
                                class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-lg text-white focus:border-emerald-400 focus:outline-none"
                            />
                        </label>
                        <label
                            class="flex flex-col gap-2 text-sm font-semibold"
                        >
                            Email
                            <input
                                v-model="walkInRequest.email"
                                required
                                type="email"
                                autocomplete="email"
                                class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-lg text-white focus:border-emerald-400 focus:outline-none"
                            />
                        </label>
                        <label
                            class="flex flex-col gap-2 text-sm font-semibold"
                        >
                            Phone
                            <input
                                v-model="walkInRequest.phone"
                                required
                                type="tel"
                                autocomplete="tel"
                                class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-lg text-white focus:border-emerald-400 focus:outline-none"
                            />
                        </label>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5"
                >
                    <h2 class="text-xl font-bold">Rental interval</h2>
                    <p class="mt-1 text-zinc-400">
                        Times use the shop timezone: {{ day.timezone }}. The
                        default is now for two hours.
                    </p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label
                            class="flex flex-col gap-2 text-sm font-semibold"
                        >
                            Starts
                            <input
                                v-model="walkInRequest.starts_at_local"
                                required
                                type="datetime-local"
                                class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-lg text-white focus:border-emerald-400 focus:outline-none"
                            />
                        </label>
                        <label
                            class="flex flex-col gap-2 text-sm font-semibold"
                        >
                            Expected return
                            <input
                                v-model="walkInRequest.ends_at_local"
                                required
                                type="datetime-local"
                                :min="walkInRequest.starts_at_local"
                                class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-lg text-white focus:border-emerald-400 focus:outline-none"
                            />
                        </label>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="min-w-0 flex-1 text-xl font-bold">Bikes</h2>
                        <button
                            type="button"
                            class="min-h-12 rounded-xl bg-emerald-400 px-4 text-base font-bold text-zinc-950"
                            @click="addWalkInRider"
                        >
                            {{ terminalUi.add_rider }}
                        </button>
                    </div>
                    <p class="mt-1 text-zinc-400">
                        {{ terminalUi.availability_buffer_note }}
                    </p>
                    <p
                        v-if="props.bike_assignment_policy === 'pickup_only'"
                        class="mt-2 text-sm text-zinc-300"
                    >
                        Bikes stay unassigned until pickup.
                    </p>
                    <label class="mt-4 flex min-h-12 items-center gap-3 text-sm font-semibold">
                        <input
                            v-model="showUnavailableBikes"
                            type="checkbox"
                            class="size-6 accent-amber-400"
                        />
                        {{ terminalUi.show_unavailable_bikes }}
                    </label>
                    <div class="mt-4 flex flex-col gap-4">
                        <section
                            v-for="(rider, index) in walkInRiders"
                            :key="index"
                            class="flex flex-col gap-3 rounded-xl border border-zinc-700 bg-zinc-950 p-4"
                        >
                            <div class="flex items-center gap-3">
                                <h3 class="min-w-0 flex-1 text-lg font-bold">
                                    {{ terminalUi.rider }} {{ index + 1 }}
                                </h3>
                                <button
                                    v-if="walkInRiders.length > 1"
                                    type="button"
                                    class="min-h-12 rounded-xl bg-zinc-800 px-4 text-sm font-semibold text-zinc-100"
                                    @click="removeWalkInRider(index)"
                                >
                                    {{ terminalUi.remove_rider }}
                                </button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <label class="flex flex-col gap-2 text-sm font-semibold">
                                    {{ terminalUi.rider }}
                                    <input
                                        v-model="rider.rider_name"
                                        type="text"
                                        class="h-14 rounded-xl border border-zinc-700 bg-zinc-900 px-4 text-base text-white focus:border-emerald-400 focus:outline-none"
                                    />
                                </label>
                                <label class="flex flex-col gap-2 text-sm font-semibold">
                                    {{ terminalUi.rider_height_cm }}
                                    <input
                                        v-model="rider.rider_height_cm"
                                        type="number"
                                        min="0"
                                        max="250"
                                        inputmode="numeric"
                                        :placeholder="terminalUi.cm"
                                        class="h-14 rounded-xl border border-zinc-700 bg-zinc-900 px-4 text-base text-white focus:border-emerald-400 focus:outline-none"
                                    />
                                </label>
                                <label class="flex flex-col gap-2 text-sm font-semibold">
                                    {{ terminalUi.bike_category }}
                                    <select
                                        v-model="rider.category_id"
                                        class="h-14 rounded-xl border border-zinc-700 bg-zinc-900 px-4 text-base text-white focus:border-emerald-400 focus:outline-none"
                                    >
                                        <option :value="null">{{ terminalUi.all_categories }}</option>
                                        <option
                                            v-for="category in bikeCandidateResults.categories"
                                            :key="category.id"
                                            :value="category.id"
                                        >
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </label>
                            </div>
                            <div
                                v-if="riderHeight(rider) !== null"
                                class="flex flex-wrap items-center gap-3"
                            >
                                <p class="min-w-0 flex-1 text-sm text-zinc-300">
                                    {{ terminalUi.recommended_fit }}
                                </p>
                                <button
                                    type="button"
                                    class="min-h-12 rounded-xl bg-zinc-800 px-4 text-sm font-semibold text-white"
                                    @click="rider.show_all_sizes = !rider.show_all_sizes"
                                >
                                    {{ rider.show_all_sizes ? terminalUi.hide_all_sizes : terminalUi.show_all_sizes }}
                                </button>
                            </div>
                            <label class="flex flex-col gap-2 text-sm font-semibold">
                                {{ terminalUi.bike }}
                                <select
                                    v-model="rider.bike_id"
                                    class="h-16 rounded-xl border border-zinc-700 bg-zinc-900 px-4 text-base text-white focus:border-emerald-400 focus:outline-none"
                                >
                                    <option :value="null">{{ terminalUi.choose_bike }}</option>
                                    <option
                                        v-for="candidate in candidatesForRider(index)"
                                        :key="candidate.id"
                                        :value="candidate.id"
                                    >
                                        {{ candidateOptionLabel(candidate, rider) }}
                                    </option>
                                </select>
                            </label>
                            <p
                                v-if="rider.bike_id !== null && candidatesForRider(index).some((candidate) => candidate.id === rider.bike_id && candidate.conflict_reason !== null)"
                                role="alert"
                                class="rounded-xl border border-amber-500 bg-amber-950 p-3 text-sm font-semibold text-amber-100"
                            >
                                {{ terminalUi.unavailable_override_warning }}
                            </p>
                            <p
                                v-if="rider.bike_id !== null"
                                class="text-sm text-zinc-400"
                            >
                                {{ getEnumLabel('bikeSituation', bikeCandidateResults.candidates.find((candidate) => candidate.id === rider.bike_id)?.bike_situation_state ?? BikeSituation.Home) }}
                                <template v-if="candidateOwningReservationLabel(rider.bike_id)">
                                    · {{ candidateOwningReservationLabel(rider.bike_id) }}
                                </template>
                            </p>
                        </section>
                    </div>
                    <label
                        v-if="hasSelectedUnavailableBike()"
                        class="mt-4 flex min-h-16 items-center gap-3 rounded-xl border-2 border-amber-400 bg-amber-950 p-4 text-base font-bold text-amber-100"
                    >
                        <input
                            v-model="availabilityOverrideConfirmed"
                            type="checkbox"
                            class="size-7 accent-amber-400"
                        />
                        {{ terminalUi.confirm_availability_override }}
                    </label>
                    <p
                        v-if="bikeCandidatesLoading"
                        class="mt-4 rounded-xl border border-dashed border-zinc-700 p-4 text-zinc-300"
                    >
                        {{ terminalUi.loading_candidates }}
                    </p>
                    <p
                        v-else-if="bikeCandidateResults.candidates.length === 0"
                        class="mt-4 rounded-xl border border-dashed border-zinc-700 p-4 text-zinc-400"
                    >
                        {{ terminalUi.no_available_candidates }}
                    </p>
                    <p
                        v-if="bikeCandidatesError"
                        role="alert"
                        class="mt-4 rounded-xl border border-rose-700 bg-rose-950 p-4 text-rose-100"
                    >
                        {{ bikeCandidatesError }}
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5"
                >
                    <label
                        class="flex max-w-sm flex-col gap-2 text-sm font-semibold"
                    >
                        Total owed ({{ currencyLabel(day.currency, locale) }})
                        <input
                            v-model="walkInOwed"
                            required
                            type="text"
                            inputmode="decimal"
                            :placeholder="
                                currencyExponent(day.currency) === 0
                                    ? '12'
                                    : new Intl.NumberFormat(locale, {
                                          minimumFractionDigits:
                                              currencyExponent(day.currency),
                                          maximumFractionDigits:
                                              currencyExponent(day.currency),
                                      }).format(12)
                            "
                            class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-lg text-white focus:border-emerald-400 focus:outline-none"
                        />
                    </label>
                    <p class="mt-2 text-sm text-zinc-400">
                        This is a hand-entered Terminal quote.
                    </p>
                </section>

                <div
                    v-if="walkInError"
                    role="alert"
                    class="rounded-xl border border-rose-700 bg-rose-950 p-4 text-rose-100"
                >
                    {{ walkInError }}
                </div>

                <button
                    type="submit"
                    class="min-h-16 rounded-xl bg-emerald-400 px-6 text-xl font-bold text-zinc-950 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                    :disabled="
                        walkInRequest.processing ||
                        walkInRiders.some((rider) => rider.bike_id === null) ||
                        (hasSelectedUnavailableBike() && !availabilityOverrideConfirmed)
                    "
                >
                    {{
                        walkInRequest.processing
                            ? 'Creating reservation…'
                            : 'Create walk-in reservation'
                    }}
                </button>
            </form>
        </main>

        <main
            v-else-if="!selectedReservation"
            class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-4 p-4 sm:p-6"
        >
            <label class="block">
                <span class="sr-only">Search reservations</span>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Find a customer, bike, or model"
                    class="h-16 w-full rounded-2xl border border-zinc-700 bg-zinc-900 px-5 text-lg text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none"
                />
            </label>

            <section
                v-if="visibleReservations.length > 0"
                class="flex flex-col gap-3"
            >
                <button
                    v-for="reservation in visibleReservations"
                    :key="reservation.id"
                    type="button"
                    class="flex min-h-24 flex-col gap-3 rounded-2xl border border-zinc-800 bg-zinc-900 p-4 text-left hover:border-zinc-600 sm:p-5"
                    @click="openReservation(reservation)"
                >
                    <div class="flex w-full flex-wrap items-center gap-3">
                        <span class="min-w-0 flex-1 text-xl font-bold">{{
                            reservation.customer.name
                        }}</span>
                        <span
                            class="rounded-full px-3 py-1 text-sm font-semibold"
                            :class="
                                getStatusBadgeClasses(
                                    'reservationStage',
                                    reservation.stage,
                                )
                            "
                        >
                            {{
                                getEnumLabel(
                                    'reservationStage',
                                    reservation.stage,
                                )
                            }}
                        </span>
                        <span class="font-mono text-lg">{{
                            formatTime(reservation.starts_at)
                        }}</span>
                        <span
                            v-if="reservationNeedsAttention(reservation)"
                            class="rounded-full bg-amber-400 px-3 py-1 text-sm font-bold text-zinc-950"
                            >Needs attention</span
                        >
                    </div>
                    <ul class="flex w-full flex-wrap gap-2">
                        <li
                            v-for="line in reservation.lines"
                            :key="line.id"
                            class="flex flex-wrap items-center gap-2 rounded-lg bg-zinc-800 px-3 py-2 text-sm"
                        >
                            <span
                                >{{ line.product_label }} ·
                                {{
                                    displayBikeForLine(line)?.bid ??
                                    'Unassigned'
                                }}</span
                            >
                            <template v-if="displayBikeForLine(line)">
                                <span class="text-zinc-400">·</span>
                                <span>{{
                                    getEnumLabel(
                                        'bikeSituation',
                                        displayBikeForLine(line)!
                                            .bike_situation_state,
                                    )
                                }}</span>
                                <span
                                    v-if="
                                        occupyingReservationLabel(
                                            displayBikeForLine(line)!,
                                            reservation.id,
                                        )
                                    "
                                    class="text-amber-300"
                                >
                                    ·
                                    {{
                                        occupyingReservationLabel(
                                            displayBikeForLine(line)!,
                                            reservation.id,
                                        )
                                    }}
                                </span>
                                <span
                                    v-else-if="
                                        displayBikeForLine(line)!
                                            .bike_situation_state !==
                                            BikeSituation.Home &&
                                        displayBikeForLine(line)!
                                            .bike_situation_reservation_id ===
                                            null
                                    "
                                    class="text-amber-300"
                                >
                                    ·
                                    {{ readinessLabel('missing_reservation') }}
                                </span>
                            </template>
                            <template
                                v-if="
                                    isWithinReadinessWindow(reservation) ||
                                    isBeingPrepared(reservation)
                                "
                            >
                                <span
                                    v-for="issue in readinessIssuesForLine(
                                        reservation,
                                        line,
                                    )"
                                    :key="issue"
                                    :class="[
                                        getStatusBadgeClasses(
                                            'bikeReadinessIssue',
                                            issue,
                                        ),
                                        'rounded-full px-2 py-0.5 font-semibold',
                                    ]"
                                    >{{ readinessLabel(issue) }}</span
                                >
                            </template>
                        </li>
                    </ul>
                    <p
                        v-if="
                            reservation.owed_minor_units !==
                            reservation.paid_minor_units
                        "
                        class="text-sm font-semibold text-amber-300"
                    >
                        {{ balanceMessage(reservation) }}
                    </p>
                </button>
            </section>

            <section
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-zinc-700 bg-zinc-900/60 px-6 py-12 text-center"
            >
                <h2 class="text-2xl font-bold">
                    {{
                        search
                            ? 'No matching reservations'
                            : 'No reservations on the board'
                    }}
                </h2>
                <p v-if="search" class="max-w-xl text-zinc-400">
                    Try a different customer name, bike bid, or model.
                </p>
                <template v-else>
                    <p
                        v-if="day.bikeList.length === 0"
                        class="max-w-xl text-zinc-400"
                    >
                        This shop has no bikes yet. Add a category, model,
                        variant, and bike in Manage before starting a walk-in.
                    </p>
                    <p v-else class="max-w-xl text-zinc-400">
                        Reservations appear here when they overlap today or have
                        a bike away from Home. Select New walk-in to create an
                        immediate hand-quoted reservation.
                    </p>
                </template>
            </section>
        </main>

        <main
            v-else
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-4 p-4 sm:p-6"
        >
            <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
                <div class="flex flex-wrap items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-2xl font-bold">
                            {{ selectedReservation.customer.name }}
                        </h2>
                        <p class="mt-1 text-zinc-400">
                            {{ formatTime(selectedReservation.starts_at) }}–{{
                                formatTime(selectedReservation.ends_at)
                            }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-sm font-semibold"
                        :class="
                            getStatusBadgeClasses(
                                'reservationStage',
                                selectedReservation.stage,
                            )
                        "
                    >
                        {{
                            getEnumLabel(
                                'reservationStage',
                                selectedReservation.stage,
                            )
                        }}
                    </span>
                </div>
                <p
                    v-if="
                        selectedReservation.owed_minor_units !==
                        selectedReservation.paid_minor_units
                    "
                    class="mt-4 rounded-xl bg-amber-950/60 p-3 text-lg font-semibold text-amber-200"
                >
                    {{ balanceMessage(selectedReservation) }}
                </p>
            </section>

            <div
                v-if="pickupError"
                role="alert"
                class="rounded-xl border border-rose-700 bg-rose-950 p-4 text-rose-100"
            >
                {{ pickupError }}
            </div>

            <section aria-label="Reservation lines" class="flex flex-col gap-3">
                <article
                    v-for="line in selectedReservation.lines"
                    :key="line.id"
                    class="flex flex-col gap-4 rounded-2xl border border-zinc-800 bg-zinc-900 p-4 sm:flex-row sm:items-center sm:p-5"
                >
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-bold">
                            {{ line.product_label }}
                        </h3>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-lg bg-zinc-800 px-3 py-2 font-mono text-lg font-bold"
                            >
                                {{
                                    displayBikeForLine(line)?.bid ??
                                    'Unassigned'
                                }}
                            </span>
                            <span
                                class="rounded-full px-3 py-1 text-sm font-semibold"
                                :class="
                                    getStatusBadgeClasses(
                                        'bikeReservationStatus',
                                        line.status,
                                    )
                                "
                            >
                                {{
                                    getEnumLabel(
                                        'bikeReservationStatus',
                                        line.status,
                                    )
                                }}
                            </span>
                            <span
                                v-if="displayBikeForLine(line)"
                                class="rounded-full px-3 py-1 text-sm font-semibold"
                                :class="
                                    getStatusBadgeClasses(
                                        'bikeSituation',
                                        displayBikeForLine(line)
                                            ?.bike_situation_state,
                                    )
                                "
                            >
                                {{
                                    getEnumLabel(
                                        'bikeSituation',
                                        displayBikeForLine(line)
                                            ?.bike_situation_state,
                                    )
                                }}
                                <template
                                    v-if="
                                        occupyingReservationLabel(
                                            displayBikeForLine(line)!,
                                            selectedReservation.id,
                                        )
                                    "
                                >
                                    ·
                                    {{
                                        occupyingReservationLabel(
                                            displayBikeForLine(line)!,
                                            selectedReservation.id,
                                        )
                                    }}
                                </template>
                            </span>
                        </div>
                        <div
                            v-if="
                                readinessIssuesForLine(
                                    selectedReservation,
                                    line,
                                ).length > 0
                            "
                            class="mt-3 flex flex-wrap gap-2"
                            aria-label="Readiness"
                        >
                            <span
                                v-for="issue in readinessIssuesForLine(
                                    selectedReservation,
                                    line,
                                )"
                                :key="issue"
                                :class="[
                                    getStatusBadgeClasses(
                                        'bikeReadinessIssue',
                                        issue,
                                    ),
                                    'rounded-full px-3 py-1 text-sm font-semibold',
                                ]"
                                >{{ readinessLabel(issue) }}</span
                            >
                        </div>
                        <span
                            v-else-if="
                                line.status ===
                                    BikeReservationStatus.Assigned &&
                                displayBikeForLine(line)
                            "
                            class="mt-3 inline-flex rounded-full bg-emerald-950 px-3 py-1 text-sm font-semibold text-emerald-200"
                            >Ready</span
                        >
                    </div>

                    <label
                        v-if="
                            line.bike_id === null &&
                            line.status === BikeReservationStatus.Assigned
                        "
                        class="flex min-h-14 w-full flex-col gap-2 text-sm font-semibold sm:w-80"
                    >
                        Bike for pickup
                        <select
                            v-model="pickupBikeByLineId[line.id]"
                            class="h-14 rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-base text-white focus:border-emerald-400 focus:outline-none"
                        >
                            <option :value="undefined">Choose a bike</option>
                            <option
                                v-for="bike in bikesForLine(line)"
                                :key="bike.id"
                                :value="bike.id"
                            >
                                {{ bike.bid }} · {{ bike.model }} ·
                                {{ bike.variant }} ·
                                {{
                                    getEnumLabel(
                                        'bikeSituation',
                                        bike.bike_situation_state,
                                    )
                                }}
                                <template
                                    v-if="occupyingReservationLabel(bike)"
                                >
                                    ·
                                    {{
                                        occupyingReservationLabel(bike)
                                    }}</template
                                >
                            </option>
                        </select>
                    </label>
                    <button
                        type="button"
                        class="min-h-16 w-full shrink-0 rounded-xl px-6 text-lg font-bold sm:w-48"
                        :class="
                            canPickUp(selectedReservation, line)
                                ? 'bg-emerald-400 text-zinc-950'
                                : 'bg-zinc-800 text-zinc-500'
                        "
                        :disabled="
                            !canPickUp(selectedReservation, line) ||
                            pendingLineId !== null
                        "
                        @click="pickUpLine(line)"
                    >
                        {{
                            pendingLineId === line.id
                                ? 'Recording…'
                                : line.status === BikeReservationStatus.Out
                                  ? 'Picked up'
                                  : line.status === BikeReservationStatus.In
                                    ? 'Returned'
                                    : line.bike_id
                                      ? 'Pick up this bike'
                                      : pickupBikeByLineId[line.id]
                                        ? 'Pick up selected bike'
                                        : 'Choose a bike to pick up'
                        }}
                    </button>
                </article>
            </section>
        </main>

        <div
            v-if="eventLogOpen"
            class="fixed inset-0 z-50 flex justify-end bg-black/70"
            @keydown.esc="eventLogOpen = false"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="broadcast-log-title"
                class="flex h-full w-full max-w-3xl flex-col border-l border-zinc-700 bg-zinc-950 shadow-2xl"
            >
                <header
                    class="flex items-center gap-3 border-b border-zinc-800 p-4 sm:p-6"
                >
                    <div class="min-w-0 flex-1">
                        <h2 id="broadcast-log-title" class="text-2xl font-bold">
                            {{ terminalUi.event_log_title }}
                        </h2>
                        <p class="mt-1 text-sm text-zinc-400">
                            {{ terminalUi.event_log_description }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="min-h-14 rounded-xl border border-zinc-600 px-4 text-base font-semibold"
                        @click="day.clearBroadcastLog()"
                    >
                        {{ terminalUi.event_log_clear }}
                    </button>
                    <button
                        type="button"
                        class="min-h-14 rounded-xl bg-zinc-800 px-4 text-base font-semibold"
                        @click="eventLogOpen = false"
                    >
                        {{ terminalUi.event_log_close }}
                    </button>
                </header>
                <ol
                    v-if="day.broadcastLog.length > 0"
                    class="flex-1 space-y-3 overflow-y-auto p-4 sm:p-6"
                    aria-label="Received broadcast events"
                    aria-live="polite"
                >
                    <li
                        v-for="entry in day.broadcastLog"
                        :key="entry.id"
                        class="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <code
                                class="font-semibold break-all text-emerald-300"
                                >{{ entry.event }}</code
                            >
                            <time
                                class="text-sm text-zinc-400"
                                :datetime="entry.receivedAt"
                            >
                                {{
                                    new Date(
                                        entry.receivedAt,
                                    ).toLocaleTimeString(locale, {
                                        timeZone: day.timezone,
                                    })
                                }}
                            </time>
                        </div>
                        <p class="mt-2 text-sm text-zinc-300">
                            {{ eventSummary(entry) }}
                        </p>
                        <details
                            class="mt-3 rounded-lg border border-zinc-800 bg-zinc-950"
                        >
                            <summary
                                class="min-h-12 cursor-pointer px-3 py-3 text-sm font-semibold text-zinc-300"
                            >
                                {{ terminalUi.event_log_raw_json }}
                            </summary>
                            <pre
                                class="max-h-72 overflow-auto border-t border-zinc-800 p-3 text-xs break-all whitespace-pre-wrap text-zinc-300"
                                >{{ JSON.stringify(entry.payload, null, 2) }}</pre
                            >
                        </details>
                    </li>
                </ol>
                <p
                    v-else
                    class="flex flex-1 items-center justify-center p-8 text-center text-lg text-zinc-400"
                >
                    {{ terminalUi.event_log_waiting }}
                </p>
            </section>
        </div>
    </div>
</template>
