import { defineStore } from 'pinia';
import type {
    BikeReservationStatus,
    BikeSituation,
    ReservationStage,
    ReturnSituation,
} from '@/types/domain';

export type DayBike = {
    id: number;
    bid: string;
    in_service: boolean;
    self_bookable: boolean;
    bike_situation_state: BikeSituation;
    bike_situation_reservation_id: number | null;
    model: string;
    variant: string;
    photo_url: string | null;
};

export type DayLine = {
    id: number;
    product_id: number;
    product_label: string;
    bike_id: number | null;
    status: BikeReservationStatus;
    rider_name: string | null;
    rider_height_cm: number | null;
};

export type DayReservation = {
    id: number;
    stage: ReservationStage | string;
    starts_at: string;
    ends_at: string;
    owed: number;
    paid: number;
    customer: { id: number; name: string };
    waiver_accepted_at: string | null;
    myrental_token: string | null;
    lines: DayLine[];
};

export type DaySnapshot = {
    tenant_id: string;
    location_id: number;
    timezone: string;
    currency: string;
    return_situation: ReturnSituation | string;
    bikes: DayBike[];
    reservations: DayReservation[];
};

export type DayPatch = {
    reservation?: DayReservation | null;
    bikes?: DayBike[];
};

function indexById<T extends { id: number }>(rows: T[]): Record<number, T> {
    return Object.fromEntries(rows.map((row) => [row.id, row]));
}

export const useDayStore = defineStore('day', {
    state: () => ({
        hydrated: false,
        tenantId: '',
        locationId: 0,
        timezone: 'UTC',
        currency: 'usd',
        returnSituation: 'home',
        bikes: {} as Record<number, DayBike>,
        reservations: {} as Record<number, DayReservation>,
    }),
    getters: {
        bikeList: (state) => Object.values(state.bikes),
        reservationList: (state) => Object.values(state.reservations),
        bikeById: (state) => {
            return (id: number) => state.bikes[id];
        },
        reservationById: (state) => {
            return (id: number) => state.reservations[id];
        },
        occupyingReservation: (state) => {
            return (bike: DayBike) =>
                bike.bike_situation_reservation_id
                    ? state.reservations[bike.bike_situation_reservation_id]
                    : undefined;
        },
    },
    actions: {
        hydrate(snapshot: DaySnapshot): void {
            this.tenantId = snapshot.tenant_id;
            this.locationId = snapshot.location_id;
            this.timezone = snapshot.timezone;
            this.currency = snapshot.currency;
            this.returnSituation = snapshot.return_situation;
            this.bikes = indexById(snapshot.bikes);
            this.reservations = indexById(snapshot.reservations);
            this.hydrated = true;
        },
        patchBike(bike: DayBike): void {
            this.bikes[bike.id] = bike;
        },
        patchReservation(reservation: DayReservation): void {
            this.reservations[reservation.id] = reservation;
        },
        removeBike(id: number): void {
            delete this.bikes[id];
        },
        removeReservation(id: number): void {
            delete this.reservations[id];
        },
        applyPatch(patch: DayPatch): void {
            if (patch.reservation) {
                this.patchReservation(patch.reservation);
            }

            for (const bike of patch.bikes ?? []) {
                this.patchBike(bike);
            }
        },
    },
});
