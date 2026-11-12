import { defineStore } from 'pinia';

export type CfdWaiver = {
    version_id: number;
    version: number;
    locale: string;
    content: string;
    accepted_at: string | null;
};

export type CfdTicket = {
    id: number;
    starts_at: string;
    ends_at: string;
    owed_minor_units: number;
    paid_minor_units: number;
    currency: string;
    package: { name: string } | null;
    customer: { id: number; name: string; email: string; phone: string };
    waiver: CfdWaiver | null;
    lines: {
        id: number;
        product_label: string;
        bid: string | null;
        status: string;
    }[];
} | null;

export const useCfdTicketStore = defineStore('cfdTicket', {
    state: () => ({ ticket: null as CfdTicket }),
    actions: {
        hydrate(ticket: CfdTicket): void {
            this.ticket = ticket;
        },
    },
});
