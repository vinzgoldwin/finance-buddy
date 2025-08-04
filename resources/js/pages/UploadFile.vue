<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { FileUp, Loader2 } from 'lucide-vue-next';

import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Upload File', href: '/files/upload' }];

const form = useForm<{ file: File | null }>({
    file: null,
});

// Processing states with corresponding messages
const processingStates = {
    uploading: 'Uploading your file...',
    extracting: 'Extracting text from your document...',
    analyzing: 'Analyzing transactions with AI...',
    saving: 'Saving transactions to your account...',
    completed: 'Transactions successfully imported!',
};

// Current processing state
const currentProcessingState = ref<keyof typeof processingStates | null>(null);

// Watch for form processing changes to update status messages
watch(
    () => form.processing,
    (isProcessing) => {
        if (isProcessing) {
            currentProcessingState.value = 'uploading';

            // Simulate progress through states
            setTimeout(() => {
                if (form.processing) currentProcessingState.value = 'extracting';
                setTimeout(() => {
                    if (form.processing) currentProcessingState.value = 'analyzing';
                    setTimeout(() => {
                        if (form.processing) currentProcessingState.value = 'saving';
                    }, 1500);
                }, 1500);
            }, 1000);
        } else {
            // Reset state when processing is complete
            setTimeout(() => {
                currentProcessingState.value = null;
            }, 2000);
        }
    },
);

const submit = () => {
    form.post(route('files.store'), {
        forceFormData: true,
        onStart: () => {
            currentProcessingState.value = 'uploading';
        },
        onSuccess: () => {
            currentProcessingState.value = 'completed';
        },
    });
};
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
                            class="text-muted-foreground file:mr-4 file:rounded-md file:border file:border-input file:bg-primary file:px-2 file:py-0.5 file:text-sm file:font-medium file:text-primary-foreground"
                            @change="form.file = $event.target.files[0]"
                            :disabled="form.processing"
                        />

                        <!-- helper text -->
                        <p class="text-xs text-muted-foreground">Accepted formats: PDF or CSV • Max 5 MB</p>

                        <!-- validation errors (Inertia automatically injects them) -->
                        <p v-if="form.errors.file" class="text-sm text-destructive">
                            {{ form.errors.file }}
                        </p>
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ form.processing ? 'Processing…' : 'Upload' }}
                    </Button>

                    <!-- Processing indicator -->
                    <div v-if="currentProcessingState" class="mt-4 space-y-3">
                        <div class="flex items-center justify-center space-x-2">
                            <Loader2 class="h-4 w-4 animate-spin" />
                            <span class="text-sm text-muted-foreground">
                                {{ processingStates[currentProcessingState] }}
                            </span>
                        </div>
                        <div class="w-full rounded-full bg-secondary">
                            <div
                                class="h-2 rounded-full bg-primary transition-all duration-500 ease-out"
                                :class="{
                                    'w-1/4': currentProcessingState === 'uploading',
                                    'w-2/4': currentProcessingState === 'extracting',
                                    'w-3/4': currentProcessingState === 'analyzing',
                                    'w-full': currentProcessingState === 'saving' || currentProcessingState === 'completed',
                                }"
                            ></div>
                        </div>
                    </div>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template>
