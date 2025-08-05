<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import ToastClose from './ToastClose.vue'
import ToastTitle from './ToastTitle.vue'
import ToastDescription from './ToastDescription.vue'
import type { ToastProps } from './types'

interface Props extends ToastProps {}

const props = withDefaults(defineProps<Props>(), {
  type: 'default',
  open: true,
  duration: 5000,
})

const emit = defineEmits<{
  (e: 'close'): void
}>()

const isOpen = ref(props.open)

const typeClasses = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-green-100 border-green-200 text-green-900'
    case 'destructive':
      return 'bg-red-100 border-red-200 text-red-900'
    case 'warning':
      return 'bg-yellow-100 border-yellow-200 text-yellow-900'
    case 'info':
      return 'bg-blue-100 border-blue-200 text-blue-900'
    default:
      return 'bg-white border-gray-200 text-gray-900'
  }
})

watch(() => props.open, (newVal) => {
  isOpen.value = newVal
})

const closeToast = () => {
  isOpen.value = false
  emit('close')
  if (props.onOpenChange) {
    props.onOpenChange(false)
  }
}
</script>

<template>
  <div 
    v-if="isOpen"
    class="relative rounded-lg border p-4 shadow-lg transition-all duration-300 ease-in-out"
    :class="typeClasses"
  >
    <div class="flex items-start gap-2">
      <div class="flex-1 space-y-1">
        <ToastTitle v-if="title" :class="type === 'destructive' ? 'text-red-900' : ''">
          {{ title }}
        </ToastTitle>
        <ToastDescription v-if="description" :class="type === 'destructive' ? 'text-red-800' : ''">
          {{ description }}
        </ToastDescription>
      </div>
      <ToastClose @click="closeToast" />
    </div>
  </div>
</template>