<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';

type Ticket = Record<string, unknown> | null;
const props = defineProps<{ tenantId: string; deviceId: number }>();
const emit = defineEmits<{ ticket: [ticket: Ticket] }>();
const channel = `tenant.${props.tenantId}.cfd.${props.deviceId}`;
useEcho<{ ticket: Ticket }>(channel, '.CustomerDisplayTicketUpdated', (event) => {
    emit('ticket', event.ticket);
});
</script>

<template><span class="hidden" /></template>
