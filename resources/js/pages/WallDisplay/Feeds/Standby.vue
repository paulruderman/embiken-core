<script setup lang="ts">
defineProps<{ feed: { name: string } }>();
</script>

<template>
    <section class="flex min-h-[85vh] items-center justify-center text-center">
        <h1 class="text-5xl font-light">{{ feed.name }}</h1>
    </section>
</template>
