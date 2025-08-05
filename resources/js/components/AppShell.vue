<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/use-toast';
import ToastProvider   from '@/components/ui/toast/ToastProvider.vue'
import ToastViewport   from '@/components/ui/toast/ToastViewport.vue'
import ToastList       from '@/components/ui/toast/ToastList.vue'
interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const isOpen = usePage().props.sidebarOpen;
const { toasts } = useToast();
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
        <ToastProvider :toasts="toasts">
            <ToastViewport>
                <ToastList />
            </ToastViewport>
        </ToastProvider>
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
        <ToastProvider :toasts="toasts">
            <ToastViewport>
                <ToastList />
            </ToastViewport>
        </ToastProvider>
    </SidebarProvider>
</template>
