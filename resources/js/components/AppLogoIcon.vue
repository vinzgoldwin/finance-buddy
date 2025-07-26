<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

import lightLogo from '../../assets/images/finance_buddy_light.svg';
import darkLogo from '../../assets/images/finance_buddy_dark.svg';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const { appearance } = useAppearance();

const logo = computed(() => {
    if (appearance.value === 'dark') {
        return lightLogo;
    }

    if (appearance.value === 'system') {
        if (typeof window !== 'undefined') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            return prefersDark ? lightLogo : darkLogo;
        }
    }

    return darkLogo;
});

</script>

<template>
    <img :src="logo" :class="className" v-bind="$attrs" />
</template>
