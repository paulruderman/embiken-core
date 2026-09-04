import { useHttp } from '@inertiajs/vue3';
import AcceptWaiverAction from '@/actions/Embiken/BikeRentals/Actions/AcceptWaiverAction';
import AssignBikeAction from '@/actions/Embiken/BikeRentals/Actions/AssignBikeAction';
import CancelAction from '@/actions/Embiken/BikeRentals/Actions/CancelAction';
import CreateWalkInReservationAction from '@/actions/Embiken/BikeRentals/Actions/CreateWalkInReservationAction';
import ExtendAction from '@/actions/Embiken/BikeRentals/Actions/ExtendAction';
import PickupAction from '@/actions/Embiken/BikeRentals/Actions/PickupAction';
import PutAwayAction from '@/actions/Embiken/BikeRentals/Actions/PutAwayAction';
import RecordCashPaymentAction from '@/actions/Embiken/BikeRentals/Actions/RecordCashPaymentAction';
import ReturnAction from '@/actions/Embiken/BikeRentals/Actions/ReturnAction';
import SwapAssetAction from '@/actions/Embiken/BikeRentals/Actions/SwapAssetAction';
import { useDayStore, type DayPatch } from '@/stores/day';
import type { Line, Reservation } from '@bike-rentals/Terminal/prototype/mock';

function apply(day: ReturnType<typeof useDayStore>, payload: unknown): void {
    const patch = (payload as { data?: DayPatch })?.data ?? (payload as DayPatch);

    day.applyPatch(patch);
}

export function useTerminalDesk() {
    const day = useDayStore();
    const http = useHttp<Record<string, unknown>, DayPatch>({});

    function post(
        url: string,
        body: Record<string, unknown> = {},
        onSuccess?: (patch: DayPatch) => void,
    ): void {
        http.transform(() => body).post(url, {
            onSuccess: (response) => {
                apply(day, response);
                const patch =
                    (response as { data?: DayPatch })?.data ?? (response as DayPatch);
                onSuccess?.(patch);
            },
        });
    }

    return {
        pickup(reservation: Reservation): void {
            for (const line of reservation.lines) {
                if (line.bike_id) {
                    post(PickupAction.url({ line: line.id }));
                }
            }
        },
        markReturned(reservation: Reservation): void {
            for (const line of reservation.lines) {
                if (line.bike_id) {
                    post(ReturnAction.url({ line: line.id }));
                }
            }
        },
        putAway(bikeId: number): void {
            post(PutAwayAction.url(bikeId));
        },
        takeCash(reservation: Reservation): void {
            const outstanding = Math.max(reservation.owed - reservation.paid, 0);

            if (outstanding <= 0) {
                return;
            }

            post(RecordCashPaymentAction.url(reservation.id), {
                amount_cents: outstanding,
            });
        },
        acceptWaiver(reservation: Reservation): void {
            post(AcceptWaiverAction.url(reservation.id));
        },
        cancelReservation(reservation: Reservation): void {
            post(CancelAction.url(reservation.id));
        },
        assignBike(line: Line, bikeId: number): void {
            post(AssignBikeAction.url({ line: line.id }), { bike_id: bikeId });
        },
        swapAsset(line: Line, bikeId: number): void {
            post(SwapAssetAction.url({ line: line.id }), { bike_id: bikeId });
        },
        extendReservation(reservation: Reservation, requote: boolean): void {
            const ends = new Date(reservation.endsAtIso);
            ends.setHours(ends.getHours() + 1);
            const body: Record<string, unknown> = {
                ends_at: ends.toISOString(),
            };

            if (requote) {
                body.owed = reservation.owed + 2000;
            }

            post(ExtendAction.url(reservation.id), body);
        },
        startWalkIn(onCreated?: (id: number) => void): void {
            post(CreateWalkInReservationAction.url(), {}, (patch) => {
                if (patch.reservation) {
                    onCreated?.(patch.reservation.id);
                }
            });
        },
    };
}
