<script setup lang="ts">
import { type HTMLAttributes } from 'vue'
import { Button } from '@/components/ui/button'

interface Props {
  class?: HTMLAttributes['class']
  altText: string
}

defineProps<Props>()
</script>

<template>
  <Button 
    variant="outline" 
    size="sm"
    class="mt-2 w-full"
    :class="class"
  >
    <slot />
  </Button>
</template>