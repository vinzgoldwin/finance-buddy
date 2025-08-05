<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { AlertCircle, Brain, TrendingUp, Wallet, Heart } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'AI Advisor', href: '/advisor' }];

// Form state
const period = ref('month');
const language = ref('en');

// Analysis state
const insights = ref<{
  spending_insights: string;
  savings_recommendations: string;
  budgeting_assistance: string;
  financial_health: string;
} | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

// Period options
const periodOptions = [
    { value: 'week', label: 'Last Week' },
    { value: 'month', label: 'Last Month' },
    { value: 'quarter', label: 'Last Quarter' },
    { value: 'year', label: 'Last Year' },
];

// Language options
const languageOptions = [
    { value: 'en', label: 'English' },
    { value: 'id', label: 'Indonesian' },
];

// Computed properties
const hasInsights = computed(() => insights.value !== null && 
  (insights.value.spending_insights.trim() !== '' || 
   insights.value.savings_recommendations.trim() !== '' || 
   insights.value.budgeting_assistance.trim() !== '' || 
   insights.value.financial_health.trim() !== ''));

// Methods
const requestAnalysis = () => {
    loading.value = true;
    error.value = null;
    
    router.post(route('advisor.analyze'), {
        period: period.value,
        language: language.value,
    }, {
        onSuccess: () => {
            loading.value = false;
            // For now, we'll simulate getting insights
            // In a real implementation, you would get this from the actual API response
            insights.value = {
                spending_insights: "I noticed you've been spending a bit more on dining out lately. Consider cooking at home more often to save some money.",
                savings_recommendations: "Your savings rate is looking good! Try to set aside a fixed amount each month for your emergency fund.",
                budgeting_assistance: "You're doing well with your budget. Consider allocating 10% of your income to savings if you're not already doing so.",
                financial_health: "Overall, your financial health looks solid. Keep up the good work on tracking your expenses!"
            };
        },
        onError: (errors: any) => {
            loading.value = false;
            error.value = errors[0] || 'An error occurred while analyzing your transactions.';
        }
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="AI Advisor" />
        
        <div class="container mx-auto py-8">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">AI Financial Advisor</h1>
                <p class="text-muted-foreground mt-2">
                    Get personalized insights and recommendations based on your spending patterns
                </p>
            </div>

            <Card class="mb-8">
                <CardHeader>
                    <CardTitle>Analysis Settings</CardTitle>
                    <CardDescription>
                        Choose the time period and language for your financial analysis
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Time Period</label>
                            <Select v-model="period">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select period" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="option in periodOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Language</label>
                            <Select v-model="language">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select language" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="option in languageOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        
                        <div class="flex items-end">
                            <Button 
                                @click="requestAnalysis" 
                                :disabled="loading"
                                class="w-full"
                            >
                                <Brain class="mr-2 h-4 w-4" />
                                {{ loading ? 'Analyzing...' : 'Get Insights' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Error Message -->
            <div v-if="error" class="mb-8 p-4 bg-destructive/20 text-destructive rounded-lg flex items-start">
                <AlertCircle class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0" />
                <div>
                    <h4 class="font-medium">Error</h4>
                    <p class="text-sm">{{ error }}</p>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <TrendingUp class="mr-2 h-5 w-5" />
                            Spending Insights
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Skeleton class="h-4 w-full mb-2" />
                        <Skeleton class="h-4 w-3/4 mb-2" />
                        <Skeleton class="h-4 w-5/6" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Wallet class="mr-2 h-5 w-5" />
                            Savings Recommendations
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Skeleton class="h-4 w-full mb-2" />
                        <Skeleton class="h-4 w-2/3 mb-2" />
                        <Skeleton class="h-4 w-4/5" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Brain class="mr-2 h-5 w-5" />
                            Budgeting Assistance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Skeleton class="h-4 w-full mb-2" />
                        <Skeleton class="h-4 w-3/4 mb-2" />
                        <Skeleton class="h-4 w-5/6" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Heart class="mr-2 h-5 w-5" />
                            Financial Health
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Skeleton class="h-4 w-full mb-2" />
                        <Skeleton class="h-4 w-2/3 mb-2" />
                        <Skeleton class="h-4 w-4/5" />
                    </CardContent>
                </Card>
            </div>

            <!-- Insights Results -->
            <div v-else-if="hasInsights && insights" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <TrendingUp class="mr-2 h-5 w-5" />
                            Spending Insights
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-line">{{ insights.spending_insights }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Wallet class="mr-2 h-5 w-5" />
                            Savings Recommendations
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-line">{{ insights.savings_recommendations }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Brain class="mr-2 h-5 w-5" />
                            Budgeting Assistance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-line">{{ insights.budgeting_assistance }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Heart class="mr-2 h-5 w-5" />
                            Financial Health
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-line">{{ insights.financial_health }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else-if="!loading && !hasInsights" class="text-center py-12">
                <Brain class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-medium">Get Personalized Financial Insights</h3>
                <p class="mt-2 text-muted-foreground">
                    Select a time period and language, then click "Get Insights" to receive personalized recommendations.
                </p>
            </div>
        </div>
    </AppLayout>
</template>