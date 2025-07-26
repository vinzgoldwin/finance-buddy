<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI Advisor', href: '/advisor' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="AI Advisor" />
        <div class="p-4"></div>
    </AppLayout>
</template>
