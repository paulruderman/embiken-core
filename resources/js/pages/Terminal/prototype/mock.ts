/**
 * PROTOTYPE fixture shop. In-memory only. Not Availability.
 */

export type Situation = 'home' | 'prepping' | 'staged' | 'rented_out' | 'back';

export type Stage = 'Provisional' | 'Confirmed' | 'Cancelled' | 'No Show';

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

export function createShop(): Shop {
    return {
        bikes: [
            { id: 1, bid: 'A1', model: 'Trek FX', variant: 'M', situation: 'home', in_service: true },
            { id: 2, bid: 'A2', model: 'Trek FX', variant: 'L', situation: 'home', in_service: true },
            { id: 3, bid: 'B1', model: 'Turbo', variant: 'M', situation: 'rented_out', in_service: true },
            { id: 4, bid: 'B2', model: 'Turbo', variant: 'L', situation: 'staged', in_service: true },
            { id: 5, bid: 'C1', model: 'Escape', variant: 'S', situation: 'home', in_service: true },
            { id: 6, bid: 'C2', model: 'Escape', variant: 'M', situation: 'prepping', in_service: true },
            { id: 7, bid: 'D1', model: 'Kids', variant: '24', situation: 'home', in_service: true },
            { id: 8, bid: 'D2', model: 'Kids', variant: '20', situation: 'back', in_service: true },
            { id: 9, bid: 'E1', model: 'Cargo', variant: '1', situation: 'rented_out', in_service: true },
            { id: 10, bid: 'E2', model: 'Cargo', variant: '1', situation: 'home', in_service: false },
        ],
        reservations: [
            {
                id: 101,
                customer: 'Maya Chen',
                stage: 'Confirmed',
                starts: '10:00',
                ends: '16:00',
                owed: 8000,
                paid: 4000,
                waiver: true,
                myrental: 'myrental/maya-token',
                lines: [{ id: 1, product: 'Turbo M', bike_id: 3 }],
            },
            {
                id: 102,
                customer: 'Walk-in',
                stage: 'Provisional',
                starts: 'now',
                ends: '14:00',
                owed: 3600,
                paid: 0,
                waiver: false,
                myrental: 'myrental/walkin-token',
                lines: [{ id: 2, product: 'Turbo L', bike_id: 4 }],
            },
            {
                id: 103,
                customer: 'Sam Ortiz',
                stage: 'Confirmed',
                starts: '11:30',
                ends: '17:00',
                owed: 5400,
                paid: 5400,
                waiver: true,
                myrental: 'myrental/sam-token',
                lines: [{ id: 3, product: 'Escape M', bike_id: 6 }],
            },
            {
                id: 104,
                customer: 'Priya Shah',
                stage: 'Confirmed',
                starts: '09:00',
                ends: '12:00',
                owed: 2400,
                paid: 2400,
                waiver: true,
                myrental: 'myrental/priya-token',
                lines: [{ id: 4, product: 'Kids 20', bike_id: 8 }],
            },
            {
                id: 105,
                customer: 'Leo Park',
                stage: 'Confirmed',
                starts: '08:00',
                ends: '10:00',
                owed: 9000,
                paid: 9000,
                waiver: true,
                myrental: 'myrental/leo-token',
                lines: [{ id: 5, product: 'Cargo 1', bike_id: 9 }],
            },
        ],
    };
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

export function setSituation(shop: Shop, bikeId: number, situation: Situation): void {
    const bike = bikeFor(shop, bikeId);

    if (bike) {
        bike.situation = situation;
    }
}

export function pickup(shop: Shop, reservation: Reservation): void {
    reservation.stage = 'Confirmed';

    for (const line of reservation.lines) {
        if (line.bike_id) {
            setSituation(shop, line.bike_id, 'rented_out');
        }
    }
}

export function markReturned(shop: Shop, reservation: Reservation, to: 'home' | 'back'): void {
    for (const line of reservation.lines) {
        if (line.bike_id) {
            setSituation(shop, line.bike_id, to);
        }
    }
}

export function takeCash(shop: Shop, reservation: Reservation): void {
    reservation.paid = reservation.owed;
}

export function acceptWaiver(reservation: Reservation): void {
    reservation.waiver = true;
}

export function cancelReservation(shop: Shop, reservation: Reservation): void {
    reservation.stage = 'Cancelled';

    for (const line of reservation.lines) {
        if (!line.bike_id) {
            continue;
        }

        const bike = bikeFor(shop, line.bike_id);

        if (bike && bike.situation !== 'rented_out') {
            bike.situation = 'home';
        }
    }
}

export function assignBike(shop: Shop, reservation: Reservation, bikeId: number): void {
    const line = reservation.lines[0];

    if (!line) {
        return;
    }

    if (line.bike_id) {
        setSituation(shop, line.bike_id, 'home');
    }

    line.bike_id = bikeId;
    setSituation(shop, bikeId, 'prepping');
}

export function hourOf(stamp: string): number {
    if (stamp === 'now') {
        return 12;
    }

    const match = /^(\d{1,2}):/.exec(stamp);

    return match ? Number(match[1]) : 8;
}

export function bumpEnd(ends: string): string {
    const match = /^(\d{1,2}):(\d{2})$/.exec(ends);

    if (!match) {
        return '18:00';
    }

    const hour = (Number(match[1]) + 1) % 24;

    return `${String(hour).padStart(2, '0')}:${match[2]}`;
}

export function extendReservation(shop: Shop, reservationId: number, requote: boolean): Reservation | undefined {
    const reservation = shop.reservations.find((row) => row.id === reservationId);

    if (!reservation) {
        return undefined;
    }

    reservation.ends = bumpEnd(reservation.ends);

    if (requote) {
        reservation.owed += 2000;
    }

    return reservation;
}

export function startWalkIn(shop: Shop): Reservation {
    const home = shop.bikes.find((bike) => bike.situation === 'home' && bike.in_service);
    const id = Math.max(...shop.reservations.map((reservation) => reservation.id)) + 1;
    const reservation: Reservation = {
        id,
        customer: 'Walk-in',
        stage: 'Provisional',
        starts: 'now',
        ends: '+2h',
        owed: 3600,
        paid: 0,
        waiver: false,
        myrental: `myrental/new-${id}`,
        lines: [{ id: id * 10, product: home?.model ?? 'bike', bike_id: home?.id ?? null }],
    };

    if (home) {
        home.situation = 'prepping';
    }

    shop.reservations.unshift(reservation);

    return reservation;
}
