import { defineStore } from 'pinia';

export const useRentalsStore = defineStore('embiken-rentals', {
    state: () => ({
        reservations: {} as Record<number, Record<string, unknown>>,
    }),
    actions: {
        hydrate(payload: { reservations?: Array<{ id: number }> }): void {
            this.reservations = Object.fromEntries(
                (payload.reservations ?? []).map((row) => [row.id, row]),
            );
        },
        patchReservation(reservation: { id: number }): void {
            this.reservations[reservation.id] = reservation;
        },
        removeReservation(id: number): void {
            delete this.reservations[id];
        },
    },
});
