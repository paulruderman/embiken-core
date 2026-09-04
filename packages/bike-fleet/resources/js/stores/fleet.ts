import { defineStore } from 'pinia';

export const useFleetStore = defineStore('embiken-fleet', {
    state: () => ({
        bikes: {} as Record<number, Record<string, unknown>>,
    }),
    actions: {
        hydrate(payload: { bikes?: Array<{ id: number }> }): void {
            this.bikes = Object.fromEntries(
                (payload.bikes ?? []).map((bike) => [bike.id, bike]),
            );
        },
        patchBike(bike: { id: number }): void {
            this.bikes[bike.id] = bike;
        },
        removeBike(id: number): void {
            delete this.bikes[id];
        },
    },
});
