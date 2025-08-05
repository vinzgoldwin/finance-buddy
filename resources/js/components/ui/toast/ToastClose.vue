<script setup lang="ts">
import { type HTMLAttributes } from 'vue'
import { X } from 'lucide-vue-next'

interface Props {
  class?: HTMLAttributes['class']
}

defineProps<Props>()
</script>

<template>
  <button 
    class="absolute top-2 right-2 rounded-md p-1 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300"
    :class="class"
    @click="$emit('click')"
  >
    <X class="h-4 w-4" />
  </button>
</template>