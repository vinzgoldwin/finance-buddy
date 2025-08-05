<script setup lang="ts">
import { inject, type Ref } from 'vue'
import Toast from './Toast.vue'
import type { Toast as ToastType } from './use-toast'

const toasts = inject<Ref<ToastType[]>>('toasts')
</script>

<template>
  <div v-if="toasts">
    <Toast
      v-for="toast in toasts.value"
      :key="toast.id"
      v-bind="toast"
      @close="toast.open = false"
    />
  </div>
</template>