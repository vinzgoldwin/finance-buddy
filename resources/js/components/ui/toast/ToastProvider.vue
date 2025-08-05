<script setup lang="ts">
import { provide, type Ref } from 'vue'
import type { Toast, ToastPosition } from './use-toast'

const props = defineProps<{
  toasts: Ref<Toast[]>
  position?: ToastPosition
}>()

// Provide toasts to child components
provide('toasts', props.toasts)
provide('position', props.position)
</script>

<template>
  <div>
    <slot />
  </div>
</template>