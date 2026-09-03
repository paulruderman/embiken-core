/**
 * Prototype view-model over the Pinia day store. Display helpers only.
 */
import { reactive, watch } from 'vue';
import { useDayStore, type DayBike, type DayReservation } from '@/stores/day';

export type Situation = 'home' | 'prepping' | 'staged' | 'rented_out' | 'back';

export type Stage = string;

export type Bike = {
    id: number;
    bid: string;
    model: string;
    variant: string;
    situation: Situation;
    in_service: boolean;
};

export type Line = {
    id: number;
    product: string;
    bike_id: number | null;
};

export type Reservation = {
    id: number;
    customer: string;
    stage: Stage;
    starts: string;
    ends: string;
    startsAtIso: string;
    endsAtIso: string;
    owed: number;
    paid: number;
    waiver: boolean;
    myrental: string;
    lines: Line[];
};

export type Shop = {
    bikes: Bike[];
    reservations: Reservation[];
};

export function money(cents: number): string {
    return `$${(cents / 100).toFixed(0)}`;
}

function stageLabel(stage: string): string {
    return stage.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function clock(iso: string, timezone: string): string {
    return new Date(iso).toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: timezone,
    });
}

function mapBike(bike: DayBike): Bike {
    return {
        id: bike.id,
        bid: bike.bid,
        model: bike.model,
        variant: bike.variant,
        situation: bike.bike_situation_state,
        in_service: bike.in_service,
    };
}

function mapReservation(reservation: DayReservation, timezone: string): Reservation {
    return {
        id: reservation.id,
        customer: reservation.customer.name,
        stage: stageLabel(reservation.stage),
        starts: clock(reservation.starts_at, timezone),
        ends: clock(reservation.ends_at, timezone),
        startsAtIso: reservation.starts_at,
        endsAtIso: reservation.ends_at,
        owed: reservation.owed,
        paid: reservation.paid,
        waiver: reservation.waiver_accepted_at !== null,
        myrental: reservation.myrental_token ?? '',
        lines: reservation.lines.map((line) => ({
            id: line.id,
            product: line.product_label,
            bike_id: line.bike_id,
        })),
    };
}

export function useShopFloor(): Shop {
    const day = useDayStore();
    const shop = reactive<Shop>({ bikes: [], reservations: [] });

    watch(
        () => [day.bikes, day.reservations, day.timezone] as const,
        () => {
            shop.bikes = day.bikeList.map(mapBike);
            shop.reservations = day.reservationList.map((reservation) =>
                mapReservation(reservation, day.timezone),
            );
        },
        { deep: true, immediate: true },
    );

    return shop;
}

export function bikeFor(shop: Shop, id: number | null): Bike | undefined {
    return shop.bikes.find((bike) => bike.id === id);
}

export function reservationForBike(shop: Shop, bikeId: number): Reservation | undefined {
    return shop.reservations.find(
        (reservation) =>
            reservation.stage !== 'Cancelled' &&
            reservation.lines.some((line) => line.bike_id === bikeId),
    );
}

export function openReservations(shop: Shop): Reservation[] {
    return shop.reservations.filter((reservation) => reservation.stage !== 'Cancelled');
}

export function situationLabel(situation: Situation): string {
    return situation.replace('_', ' ');
}

export function bikeProduct(bike: Bike): string {
    return `${bike.model} ${bike.variant}`;
}

export function bikeCaption(shop: Shop, bike: Bike): string {
    const occupying = reservationForBike(shop, bike.id);
    const situation = situationLabel(bike.situation);

    if (occupying) {
        return `${situation} · ${occupying.customer}`;
    }

    if (bike.situation === 'home') {
        return situation;
    }

    return `${situation} · no ticket`;
}

export type PartyLine = {
    line: Line;
    bike: Bike | undefined;
    occupying: Reservation | undefined;
    onThisTicket: boolean;
};

export function partyLines(shop: Shop, reservation: Reservation): PartyLine[] {
    return reservation.lines.map((line) => {
        const bike = bikeFor(shop, line.bike_id);
        const occupying = line.bike_id ? reservationForBike(shop, line.bike_id) : undefined;

        return {
            line,
            bike,
            occupying,
            onThisTicket: occupying?.id === reservation.id,
        };
    });
}

export function moneyLane(reservation: Reservation): 'unpaid' | 'partial' | 'settled' {
    if (reservation.paid <= 0) {
        return 'unpaid';
    }

    if (reservation.paid < reservation.owed) {
        return 'partial';
    }

    return 'settled';
}

export function sizeKey(line: Line): string {
    const parts = line.product.split(' ');

    return parts[parts.length - 1] ?? '—';
}

export function exceptionFlags(shop: Shop, reservation: Reservation): string[] {
    const flags: string[] = [];

    if (reservation.owed !== reservation.paid) {
        flags.push('money');
    }

    if (!reservation.waiver) {
        flags.push('waiver');
    }

    if (reservation.lines.some((line) => line.bike_id === null)) {
        flags.push('unassigned');
    }

    if (partyLines(shop, reservation).some((row) => row.bike && !row.onThisTicket)) {
        flags.push('wrong bike');
    }

    const lateOut = reservation.lines.some((line) => {
        const bike = bikeFor(shop, line.bike_id);

        return bike?.situation === 'rented_out' && hourOf(reservation.ends) <= 12;
    });

    if (lateOut) {
        flags.push('late');
    }

    return flags;
}

function rankForLine(line: Line, bike: Bike): number {
    const label = `${bike.model} ${bike.variant}`;

    if (line.product === label) {
        return 0;
    }

    if (line.product.startsWith(bike.model)) {
        return 1;
    }

    return 2;
}

export function candidateBikes(shop: Shop, reservation: Reservation, line: Line, mode: 'assign' | 'swap'): Bike[] {
    const taken = new Set(
        reservation.lines
            .filter((item) => item.id !== line.id && item.bike_id !== null)
            .map((item) => item.bike_id as number),
    );

    return shop.bikes
        .filter((bike) => {
            if (!bike.in_service || taken.has(bike.id)) {
                return false;
            }

            if (mode === 'assign') {
                return bike.situation === 'home';
            }

            return bike.id !== line.bike_id;
        })
        .sort((left, right) => rankForLine(line, left) - rankForLine(line, right) || left.bid.localeCompare(right.bid));
}

export function hourOf(stamp: string): number {
    if (stamp === 'now') {
        return 12;
    }

    const match = /^(\d{1,2}):/.exec(stamp);

    return match ? Number(match[1]) : 8;
}

export function overlapsHour(reservation: Reservation, hour: number): boolean {
    return hourOf(reservation.starts) <= hour && hourOf(reservation.ends) > hour;
}

export function busyAt(shop: Shop, bikeId: number, hour: number): Reservation | undefined {
    return openReservations(shop).find(
        (reservation) =>
            overlapsHour(reservation, hour) &&
            reservation.lines.some((line) => line.bike_id === bikeId),
    );
}
