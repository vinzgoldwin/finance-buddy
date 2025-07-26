<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

import { FileUp } from 'lucide-vue-next'

import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Upload Statement', href: '/statements/upload' },
]

const form = useForm<{ statement: File | null }>({
    statement: null,
})

const submit = () => {
    form.post(route('statements.store'), { forceFormData: true })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Upload Statement" />

        <Card class="mx-auto mt-8 max-w-lg shadow-lg">
            <CardHeader class="flex items-center space-x-2">
                <FileUp class="h-5 w-5 shrink-0" />
                <CardTitle class="text-lg font-medium">Upload your statement</CardTitle>
            </CardHeader>

            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid w-full items-center gap-2">
                        <Label for="statement">Statement file</Label>

                        <!-- shadcn Input handles focus / ring styling for us -->
                        <Input
                            id="statement"
                            type="file"
                            name="statement"
                            accept="application/pdf,text/csv"
                            @change="form.statement = $event.target.files[0]"
                            :disabled="form.processing"
                        />

                        <!-- helper text -->
                        <p class="text-xs text-muted-foreground">
                            Accepted formats: PDF or CSV • Max 5 MB
                        </p>

                        <!-- validation errors (Inertia automatically injects them) -->
                        <p v-if="form.errors.statement" class="text-sm text-destructive">
                            {{ form.errors.statement }}
                        </p>
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ form.processing ? 'Uploading…' : 'Upload' }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template>
