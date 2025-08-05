<script setup lang="ts">
import { type HTMLAttributes } from 'vue'

interface Props {
  class?: HTMLAttributes['class']
}

defineProps<Props>()
</script>

<template>
  <p class="text-sm opacity-90" :class="class">
    <slot />
  </p>
</template>