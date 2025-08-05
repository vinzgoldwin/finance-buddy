import { ref, type Ref } from 'vue'
import type { ToastProps } from './types'

const TOAST_LIMIT = 3
const TOAST_REMOVE_DELAY = 1000000 // Set to a large number to prevent auto-remove

export type ToastType = 'default' | 'destructive' | 'success' | 'warning' | 'info'
export type ToastPosition = 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left'

export interface Toast extends ToastProps {
  id: string
  title?: string
  description?: string
  action?: any
  type?: ToastType
  position?: ToastPosition
}

export type ToastOptions = Omit<Toast, 'id'>

const toasts: Ref<Toast[]> = ref([])

export function useToast() {
  function addToast(options: ToastOptions) {
    const id = Math.random().toString(36).substring(2, 9)

    // Limit the number of toasts
    if (toasts.value.length >= TOAST_LIMIT) {
      toasts.value.shift()
    }

    toasts.value.push({
      id,
      ...options,
      open: true,
    })

      console.log(toasts.value);

    if (options.duration !== 0) {
      setTimeout(() => {
        removeToast(id)
      }, options.duration || TOAST_REMOVE_DELAY)
    }

    return id
  }

  function removeToast(id: string) {
    toasts.value = toasts.value.map(t =>
      t.id === id ? { ...t, open: false } : t
    )

    // Remove from array after animation
    setTimeout(() => {
      toasts.value = toasts.value.filter(t => t.id !== id)
    }, 300)
  }

  function updateToast(id: string, options: Partial<ToastOptions>) {
    toasts.value = toasts.value.map(t =>
      t.id === id ? { ...t, ...options } : t
    )
  }

  return {
    toasts,
    addToast,
    removeToast,
    updateToast,
  }
}

// Convenience function for adding toasts
export function toast(options: ToastOptions) {
  const { addToast } = useToast()
  return addToast(options)
}
