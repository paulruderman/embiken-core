/**
 * Color names that map to Tailwind class sets.
 * These are the values returned by PHP enum getColor().
 */
export type StatusColor =
    | 'gray'
    | 'blue'
    | 'green'
    | 'red'
    | 'yellow'
    | 'indigo'
    | 'sky'
    | 'purple'
    | 'amber'
    | 'orange';

export interface EnumMeta {
    label: string;
    color: StatusColor;
    description: string;
}

export type EnumLookupTable = Record<string, EnumMeta>;

export interface SharedEnums {
    reservationStage: EnumLookupTable;
    bikeSituation: EnumLookupTable;
    bikeReservationStatus: EnumLookupTable;
    packageMeter: EnumLookupTable;
    confirmThreshold: EnumLookupTable;
    bikeAssignmentPolicy: EnumLookupTable;
    returnSituation: EnumLookupTable;
    reservationChannel: EnumLookupTable;
    staffRole: EnumLookupTable;
    transactionKind: EnumLookupTable;
    transactionStatus: EnumLookupTable;
    serviceStage: EnumLookupTable;
    weekday: EnumLookupTable;
}

export type EnumName = keyof SharedEnums;
