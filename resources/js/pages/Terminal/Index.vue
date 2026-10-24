<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
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
    owed_minor_units: number;
};
type PickupForm = { bike_id?: number };

const props = defineProps<DaySnapshot>();
const locale = usePage().props.locale;
const day = useDayStore();
const search = ref('');
const selectedReservationId = ref<number | null>(null);
const walkInOpen = ref(false);
const pendingLineId = ref<number | null>(null);
const pickupError = ref('');
const pickupBikeByLineId = ref<Record<number, number>>({});
const pickupRequest = useHttp<PickupForm, DayPatchResponse>({});
const walkInOwed = ref('');
const walkInError = ref('');
const walkInRequest = useHttp<WalkInForm, DayPatchResponse>({
    name: '',
    email: '',
    phone: '',
    bike_ids: [],
    owed_minor_units: 0,
});

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

function occupyingReservation(bike: DayBike): DayReservation | undefined {
    return bike.bike_situation_reservation_id === null
        ? undefined
        : day.reservationById(bike.bike_situation_reservation_id);
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

    if (line.bike_id === null) {
        return Boolean(bike?.in_service && bike.variant_id === line.product_id);
    }

    if (!bike?.in_service) {
        return false;
    }

    const availableSituations: DayBike['bike_situation_state'][] = [
        BikeSituation.Home,
        BikeSituation.Prepping,
        BikeSituation.Staged,
    ];

    return (
        availableSituations.includes(bike.bike_situation_state) &&
        (bike.bike_situation_reservation_id === null ||
            bike.bike_situation_reservation_id === reservation.id)
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
    walkInOpen.value = true;
}

function submitWalkIn(): void {
    const selectedBikes = [...walkInRequest.bike_ids];
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
        .transform((data) => ({ ...data, owed_minor_units: owedMinorUnits }))
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

const walkInBikes = computed(() =>
    day.bikeList
        .filter((bike) => bike.in_service)
        .sort((left, right) => left.bid.localeCompare(right.bid)),
);

function bikesForLine(line: DayLine): DayBike[] {
    return day.bikeList
        .filter(
            (bike) => bike.in_service && bike.variant_id === line.product_id,
        )
        .sort((left, right) => left.bid.localeCompare(right.bid));
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
                v-if="!selectedReservation && !walkInOpen"
                type="button"
                class="ml-auto min-h-14 rounded-xl bg-emerald-400 px-5 text-lg font-bold text-zinc-950"
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
                    <h2 class="text-xl font-bold">Bikes</h2>
                    <p class="mt-1 text-zinc-400">
                        Choose one bike for each person in the party.
                    </p>
                    <p
                        v-if="props.bike_assignment_policy === 'pickup_only'"
                        class="mt-2 text-sm text-zinc-300"
                    >
                        Bikes stay unassigned until pickup.
                    </p>
                    <div
                        v-if="walkInBikes.length > 0"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                    >
                        <label
                            v-for="bike in walkInBikes"
                            :key="bike.id"
                            class="flex min-h-20 cursor-pointer items-center gap-4 rounded-xl border border-zinc-700 bg-zinc-950 p-4 has-checked:border-emerald-400 has-checked:bg-emerald-950/40"
                        >
                            <input
                                v-model="walkInRequest.bike_ids"
                                type="checkbox"
                                :value="bike.id"
                                class="size-6 accent-emerald-400"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="block text-lg font-bold"
                                    >{{ bike.bid }} · {{ bike.model }} ·
                                    {{ bike.variant }}</span
                                >
                                <span class="block text-sm text-zinc-400">
                                    {{
                                        getEnumLabel(
                                            'bikeSituation',
                                            bike.bike_situation_state,
                                        )
                                    }}
                                    <template v-if="occupyingReservation(bike)">
                                        ·
                                        {{
                                            occupyingReservation(bike)?.customer
                                                .name
                                        }}’s reservation
                                    </template>
                                    <template
                                        v-else-if="
                                            bike.bike_situation_state !==
                                            BikeSituation.Home
                                        "
                                    >
                                        · no occupying reservation is linked
                                    </template>
                                </span>
                            </span>
                        </label>
                    </div>
                    <p
                        v-else
                        class="mt-4 rounded-xl border border-dashed border-zinc-700 p-4 text-zinc-400"
                    >
                        No in-service bikes are configured for walk-ins yet.
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
                        walkInRequest.bike_ids.length === 0 ||
                        walkInBikes.length === 0
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
                    </div>
                    <ul class="flex w-full flex-wrap gap-2">
                        <li
                            v-for="line in reservation.lines"
                            :key="line.id"
                            class="rounded-lg bg-zinc-800 px-3 py-2 text-sm"
                        >
                            {{ line.product_label }} ·
                            {{
                                line.bike_id ? bikeFor(line)?.bid : 'Unassigned'
                            }}
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
                                {{ bikeFor(line)?.bid ?? 'Unassigned' }}
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
                                v-if="bikeFor(line)"
                                class="rounded-full px-3 py-1 text-sm font-semibold"
                                :class="
                                    getStatusBadgeClasses(
                                        'bikeSituation',
                                        bikeFor(line)?.bike_situation_state,
                                    )
                                "
                            >
                                {{
                                    getEnumLabel(
                                        'bikeSituation',
                                        bikeFor(line)?.bike_situation_state,
                                    )
                                }}
                                <template
                                    v-if="occupyingReservation(bikeFor(line)!)"
                                >
                                    ·
                                    {{
                                        occupyingReservation(bikeFor(line)!)
                                            ?.customer.name
                                    }}
                                </template>
                            </span>
                        </div>
                        <p
                            v-if="
                                bikeFor(line) &&
                                bikeFor(line)?.bike_situation_reservation_id !==
                                    null &&
                                bikeFor(line)?.bike_situation_reservation_id !==
                                    selectedReservation.id
                            "
                            class="mt-2 text-sm font-semibold text-amber-300"
                        >
                            Wrong place: this bike is occupied by another
                            reservation.
                        </p>
                        <p
                            v-else-if="
                                bikeFor(line) &&
                                bikeFor(line)?.bike_situation_state !==
                                    BikeSituation.Home &&
                                !occupyingReservation(bikeFor(line)!)
                            "
                            class="mt-2 text-sm text-amber-300"
                        >
                            No occupying reservation is recorded for this bike
                            situation.
                        </p>
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
                                <template v-if="occupyingReservation(bike)">
                                    ·
                                    {{
                                        occupyingReservation(bike)?.customer
                                            .name
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
    </div>
</template>
