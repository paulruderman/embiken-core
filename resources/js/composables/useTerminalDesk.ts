import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import AcceptWaiverAction from '@/actions/Embiken/BikeRentals/Actions/AcceptWaiverAction';
import AllocateLineAction from '@/actions/Embiken/BikeRentals/Actions/AllocateLineAction';
import AssignBikeAction from '@/actions/Embiken/BikeRentals/Actions/AssignBikeAction';
import CancelAction from '@/actions/Embiken/BikeRentals/Actions/CancelAction';
import CreateWalkInReservationAction from '@/actions/Embiken/BikeRentals/Actions/CreateWalkInReservationAction';
import ExtendAction from '@/actions/Embiken/BikeRentals/Actions/ExtendAction';
import PickupAction from '@/actions/Embiken/BikeRentals/Actions/PickupAction';
import PutAwayAction from '@/actions/Embiken/BikeRentals/Actions/PutAwayAction';
import RecordCashPaymentAction from '@/actions/Embiken/BikeRentals/Actions/RecordCashPaymentAction';
import ReleaseLineAction from '@/actions/Embiken/BikeRentals/Actions/ReleaseLineAction';
import ReturnAction from '@/actions/Embiken/BikeRentals/Actions/ReturnAction';
import SwapAssetAction from '@/actions/Embiken/BikeRentals/Actions/SwapAssetAction';
import UnassignBikeAction from '@/actions/Embiken/BikeRentals/Actions/UnassignBikeAction';
import { useDayStore, type DayPatch } from '@/stores/day';
import type {
    Line,
    Reservation,
} from '../../../packages/bike-rentals/resources/js/Terminal/prototype/mock';

type DeskRequest = {
    product_id?: number;
    amount_minor_units?: number;
    bike_id?: number;
    email?: string;
    ends_at?: string;
    name?: string;
    owed_minor_units?: number;
    phone?: string;
};

function apply(day: ReturnType<typeof useDayStore>, payload: unknown): void {
    const patch =
        (payload as { data?: DayPatch })?.data ?? (payload as DayPatch);

    day.applyPatch(patch);
}

export function useTerminalDesk() {
    const day = useDayStore();
    const error = ref('');
    const http = useHttp<DeskRequest, DayPatch>({});

    function request(
        method: 'post' | 'delete',
        url: string,
        body: DeskRequest = {},
        onSuccess?: (patch: DayPatch) => void,
    ): void {
        void http
            .transform(() => body)
            [method](url, {
                onSuccess: (response) => {
                    apply(day, response);
                    const patch =
                        (response as { data?: DayPatch })?.data ??
                        (response as DayPatch);
                    onSuccess?.(patch);
                },
            });
    }

    function post(
        url: string,
        body: DeskRequest = {},
        onSuccess?: (patch: DayPatch) => void,
    ): void {
        request('post', url, body, onSuccess);
    }

    return {
        get error(): string {
            return error.value;
        },
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
            const outstanding = Math.max(
                reservation.owed - reservation.paid,
                0,
            );

            if (outstanding <= 0) {
                return;
            }

            post(RecordCashPaymentAction.url(reservation.public_id), {
                amount_minor_units: outstanding,
            });
        },
        acceptWaiver(reservation: Reservation): void {
            post(AcceptWaiverAction.url(reservation.public_id));
        },
        cancelReservation(reservation: Reservation): void {
            post(CancelAction.url(reservation.public_id));
        },
        assignBike(line: Line, bikeId: number): void {
            post(AssignBikeAction.url({ line: line.id }), { bike_id: bikeId });
        },
        unassignBike(line: Line): void {
            post(UnassignBikeAction.url({ line: line.id }));
        },
        swapAsset(line: Line, bikeId: number): void {
            post(SwapAssetAction.url({ line: line.id }), { bike_id: bikeId });
        },
        allocateLine(
            reservation: Reservation,
            productId: number,
            bikeId?: number,
        ): void {
            const body: DeskRequest = {};

            if (bikeId !== undefined) {
                body.bike_id = bikeId;
            } else {
                body.product_id = productId;
            }

            post(AllocateLineAction.url(reservation.public_id), body);
        },
        releaseLine(line: Line): void {
            request('delete', ReleaseLineAction.url({ line: line.id }));
        },
        extendReservation(reservation: Reservation, requote: boolean): void {
            const ends = new Date(reservation.endsAtIso);
            ends.setHours(ends.getHours() + 1);
            const body: DeskRequest = {
                ends_at: ends.toISOString(),
            };

            if (requote) {
                body.owed_minor_units = reservation.owed + 2000;
            }

            post(ExtendAction.url(reservation.public_id), body);
        },
        startWalkIn(onCreated?: (id: number) => void): void {
            const name = window.prompt('Customer name')?.trim();
            const email = window.prompt('Customer email')?.trim();
            const phone = window.prompt('Customer phone')?.trim();

            if (!name || !email || !phone) {
                return;
            }

            post(
                CreateWalkInReservationAction.url(),
                { name, email, phone },
                (patch) => {
                    if (patch.reservation) {
                        onCreated?.(patch.reservation.id);
                    }
                },
            );
        },
    };
}
