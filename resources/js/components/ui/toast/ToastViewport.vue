<script setup lang="ts">
import { computed, inject } from 'vue'
import type { ToastPosition } from './use-toast'

const position = inject<ToastPosition | undefined>('position', 'top-right')

const positionClasses = computed(() => {
  switch (position) {
    case 'top-left':
      return 'top-0 left-0'
    case 'top-right':
      return 'top-0 right-0'
    case 'bottom-left':
      return 'bottom-0 left-0'
    case 'bottom-right':
      return 'bottom-0 right-0'
    default:
      return 'top-0 right-0'
  }
})
</script>

<template>
    <teleport to="body">
        <div
            class="fixed z-50 flex flex-col gap-2 p-4 w-full max-w-xs"
            :class="positionClasses"
        >
            <slot />
        </div>
    </teleport>
</template>
