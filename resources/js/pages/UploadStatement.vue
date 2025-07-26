<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Upload Statement', href: '/statements/upload' },
]

const form = useForm({
  statement: null as File | null,
})

const submit = () => {
  form.post(route('statements.store'), {
    forceFormData: true,
  })
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Upload Statement" />
    <form @submit.prevent="submit" class="p-4 space-y-4">
      <input
        id="statement"
        type="file"
        name="statement"
        accept="application/pdf"
        @change="form.statement = $event.target.files[0]"
        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
      />
      <Button type="submit" :disabled="form.processing">Upload</Button>
    </form>
  </AppLayout>
</template>
