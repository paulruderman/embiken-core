/** Mirrors App\Enums\BikeSituation */
export const BikeSituation = {
    Home: 'home',
    Prepping: 'prepping',
    Staged: 'staged',
    RentedOut: 'rented_out',
    Back: 'back',
} as const;
export type BikeSituation = (typeof BikeSituation)[keyof typeof BikeSituation];

/** Mirrors App\Enums\ReservationStage */
export const ReservationStage = {
    Provisional: 'provisional',
    Confirmed: 'confirmed',
    Cancelled: 'cancelled',
    Out: 'out',
    Returned: 'returned',
    Completed: 'completed',
    NoShow: 'no_show',
} as const;
export type ReservationStage =
    (typeof ReservationStage)[keyof typeof ReservationStage];

/** Mirrors App\Enums\BikeReservationStatus */
export const BikeReservationStatus = {
    Assigned: 'assigned',
    Out: 'out',
    In: 'in',
} as const;
export type BikeReservationStatus =
    (typeof BikeReservationStatus)[keyof typeof BikeReservationStatus];

/** Mirrors App\Enums\ReturnSituation */
export const ReturnSituation = {
    Home: 'home',
    Back: 'back',
} as const;
export type ReturnSituation =
    (typeof ReturnSituation)[keyof typeof ReturnSituation];
