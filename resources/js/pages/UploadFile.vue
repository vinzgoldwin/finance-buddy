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
    { title: 'Upload File', href: '/files/upload' },
]

const form = useForm<{ file: File | null }>({
    file: null,
})

const submit = () => {
    form.post(route('files.store'), { forceFormData: true })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Upload File" />

        <Card class="mx-auto mt-8 max-w-lg shadow-lg">
            <CardHeader class="flex items-center space-x-2">
                <FileUp class="h-5 w-5 shrink-0" />
                <CardTitle class="text-lg font-medium">Upload your file</CardTitle>
            </CardHeader>

            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid w-full items-center gap-2">
                        <Label for="file">Finance file</Label>

                        <Input
                            id="finance_file"
                            type="file"
                            name="file"
                            accept="application/pdf,text/csv"
                            class="file:mr-4 file:rounded-md file:border file:border-input file:bg-primary file:px-2 file:py-0.5 file:text-sm file:font-medium file:text-primary-foreground text-muted-foreground"
                            @change="form.file = $event.target.files[0]"
                            :disabled="form.processing"
                        />

                        <!-- helper text -->
                        <p class="text-xs text-muted-foreground">
                            Accepted formats: PDF or CSV • Max 5 MB
                        </p>

                        <!-- validation errors (Inertia automatically injects them) -->
                        <p v-if="form.errors.file" class="text-sm text-destructive">
                            {{ form.errors.file }}
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
