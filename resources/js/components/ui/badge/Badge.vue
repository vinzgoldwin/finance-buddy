<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  variant?: 'default' | 'secondary' | 'destructive' | 'outline'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default'
})

const badgeClass = computed(() => cn(
  'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
  {
    'border-transparent bg-primary text-primary-foreground': props.variant === 'default',
    'border-transparent bg-secondary text-secondary-foreground': props.variant === 'secondary',
    'border-transparent bg-destructive text-destructive-foreground': props.variant === 'destructive',
    'text-foreground': props.variant === 'outline',
  },
  props.class
))
</script>

<template>
  <div :class="badgeClass">
    <slot />
  </div>
</template>