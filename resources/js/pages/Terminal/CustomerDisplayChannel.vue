<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';

const props = defineProps<{ tenantId: string; deviceId: number }>();
const emit = defineEmits<{ ticket: [deviceId: number, ticket: Record<string, any> | null] }>();
useEcho<{ ticket: Record<string, any> | null }>(
    `tenant.${props.tenantId}.cfd.${props.deviceId}`,
    '.CustomerDisplayTicketUpdated',
    (event) => emit('ticket', props.deviceId, event.ticket),
);
</script>

<template><span class="hidden" /></template>
