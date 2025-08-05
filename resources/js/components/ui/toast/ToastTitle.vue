<script setup lang="ts">
import { type HTMLAttributes } from 'vue'

interface Props {
  class?: HTMLAttributes['class']
}

defineProps<Props>()
</script>

<template>
  <h3 class="text-sm font-semibold" :class="class">
    <slot />
  </h3>
</template>