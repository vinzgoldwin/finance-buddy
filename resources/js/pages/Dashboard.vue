<script setup lang="ts">
/* ───────────── imports ───────────── */
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart } from '@/components/ui/chart-bar';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArcElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, LineElement, PointElement, Title } from 'chart.js';
import { computed, ref, watch } from 'vue';
import { Doughnut } from 'vue-chartjs';

/*  shadcn-vue controls  */
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

/* Custom components */
import SpendingLimitCard from '@/components/SpendingLimitCard.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Title, Legend);

/* ───────────── Inertia props ───────────── */
interface Metrics {
    income: number;
    expenses: number;
    netPct: number;
    savings: number;
}
const props = defineProps<{
    metrics: Metrics;
    monthly: Array<{ month: string; income: number; expenses: number }>;
    categories: Array<{ label: string; value: number }>;
    recent: Array<{
        id: number;
        date: string;
        description: string;
        category: string;
        amount: number;
        currency: string;
    }>;
    currency: 'USD' | 'IDR';
    date?: string;
    monthOptions: Array<{ value: string; label: string }>;
    barData: Array<{ name: string; income: number; expenses: number; savings: number }>;
    spendingLimit: {
        amount: number;
        interval: string;
        spent: number;
        currency: 'USD' | 'IDR';
        dateRange: string;
    };
}>();

/* ───────────── helpers ───────────── */
const money = (v: number, curr = props.currency) =>
    new Intl.NumberFormat(curr === 'IDR' ? 'id-ID' : 'en-US', { style: 'currency', currency: curr }).format(v);

/** shorter labels for the bar‑chart Y‑axis */
const moneyShort = (v: number, curr = props.currency) => {
    if (curr === 'IDR' && Math.abs(v) >= 1_000_000) {
        // show in millions
        return `Rp ${Math.round(v / 1_000_000)} Juta`;
    }
    return money(v, curr);
};

const COLOR_BY_CATEGORY: Record<string, string> = {
    'Housing & Utilities': '#3b82f6',
    'Food & Groceries': '#f97316',
    'Transport & Travel': '#0ea5e9',
    'Health & Insurance': '#ef4444',
    'Shopping & Lifestyle': '#a855f7',
    'Savings & Investing': '#10b981',
    Other: '#64748b',
};

const donutData = computed(() => {
    const labels = props.categories.map((c) => c.label).filter((l) => l !== 'Savings & Investing');
    const data   = props.categories.filter((c) => c.label !== 'Savings & Investing').map((c) => c.value);
    const bg = labels.map((l) => COLOR_BY_CATEGORY[l] ?? '#d1d5db');
    return { labels, datasets: [{ data, backgroundColor: bg, hoverBackgroundColor: bg, borderWidth: 0 }] };
});

/** simple legend items for the donut */
const donutLegend = computed(() =>
    donutData.value.labels.map((l, i) => ({
        label: l,
        color: donutData.value.datasets[0].backgroundColor[i] as string,
    })),
);

const barData = computed(() => props.barData);

/* ───────────── month-picker state ───────────── */
const monthOptions = props.monthOptions;
const today = new Date();
const currentMonth = ref(props.date ?? monthOptions[0]?.value ?? `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`);

/* ───────────── currency tabs state ───────────── */
const currencyTab = ref<'USD' | 'IDR'>(props.currency);

/* ───────────── spending limit state ───────────── */
const spendingLimit = ref(props.spendingLimit);

const updateSpendingLimit = (newLimit: { amount: number; interval: string; spent: number; currency: 'USD' | 'IDR' }) => {
    spendingLimit.value = newLimit;
};

/*  react to changes  */
watch(currentMonth, (val) => {
    router.get('/dashboard', { currency: currencyTab.value, date: val }, { preserveState: true, replace: true });
});
watch(currencyTab, (val) => {
    router.get('/dashboard', { currency: val, date: currentMonth.value }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]" class="px-2">
        <!-- ── control strip ───────────────────────────────────────── -->
        <div class="mb-1 flex flex-wrap gap-3 p-2 sm:ml-4">
            <Tabs v-model="currencyTab" class="w-[112px]">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="IDR">IDR</TabsTrigger>
                    <TabsTrigger value="USD">USD</TabsTrigger>
                </TabsList>
            </Tabs>

            <Select v-model="currentMonth">
                <SelectTrigger class="w-[140px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="opt in monthOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- ── dashboard grid ──────────────────────────────────────── -->
        <section class="grid grid-cols-1 gap-6 px-3 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-5">
            <!-- metrics -->
            <Card class="md:col-span-1">
                <CardHeader>
                    <CardDescription>Total Income</CardDescription>
                    <CardTitle class="text-lg mt-2">{{ money(metrics.income) }}</CardTitle>
                </CardHeader>
            </Card>

            <Card class="md:col-span-1">
                <CardHeader>
                    <CardDescription>Total Expenses</CardDescription>
                    <CardTitle class="text-lg mt-2">{{ money(metrics.expenses) }}</CardTitle>
                </CardHeader>
            </Card>

            <Card class="md:col-span-1">
                <CardHeader>
                    <CardDescription>Savings & Investing</CardDescription>
                    <CardTitle class="text-lg mt-2">{{ money(metrics.savings) }}</CardTitle>
                </CardHeader>
            </Card>

            <!-- Spending Limit Card -->
            <div class="md:col-span-2">
                <SpendingLimitCard
                    :spending-limit="spendingLimit"
                    :currency="currencyTab"
                    class="sm:col-span-2 md:col-span-4 xl:col-span-5"
                    @update:spendingLimit="updateSpendingLimit"
                />
            </div>


            <!-- donut -->
            <Card class="sm:col-span-2 md:col-span-1 md:row-span-1">
                <CardHeader class="pb-2"><CardTitle>Spending Categories</CardTitle></CardHeader>
                <CardContent class="flex flex-col items-center pt-2">
                    <div class="h-24 w-full">
                        <Doughnut
                            :data="donutData"
                            :options="{
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                            }"
                        />
                    </div>

                    <!-- custom legend -->
                    <ul class="mt-2 flex flex-wrap justify-center gap-x-3 gap-y-1 text-xs">
                        <li v-for="item in donutLegend" :key="item.label" class="flex items-center gap-1">
                            <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: item.color }" />
                            {{ item.label }}
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Bar Chart -->
            <Card class="sm:col-span-4">
                <CardHeader><CardTitle>6‑Month Income, Savings & Expenses</CardTitle></CardHeader>
                <CardContent class="h-48 sm:h-60">
                    <BarChart
                        class="h-full"
                        :data="barData"
                        index="name"
                        :categories="['income', 'savings', 'expenses']"
                        :colors="['#0ea5e9','#10b981','#ef4444']"
                        :y-formatter="(tick) => (typeof tick === 'number' ? moneyShort(tick, currencyTab) : '')"
                        :x-formatter="(tick) => barData[tick]?.name || ''"
                        :show-tooltip="true"
                    />
                </CardContent>
            </Card>

            <!-- coach stub -->
            <Card class="sm:col-span-2 md:row-span-2">
                <CardHeader><CardTitle>AI Finance Coach</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <p class="rounded-lg bg-muted p-4">Consider reducing your spending on shopping to increase your savings.</p>
                    <input class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none" placeholder="Type a message…" />
                </CardContent>
            </Card>

            <!-- recent transactions -->
            <Card class="sm:col-span-2 xl:col-span-3">
                <CardHeader><CardTitle>Recent Transactions</CardTitle></CardHeader>
                <CardContent class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-border">
                            <tr class="text-left text-muted-foreground">
                                <th class="px-3 py-2 whitespace-nowrap">Date</th>
                                <th class="max-w-[40ch] truncate px-3 py-2">Description</th>
                                <th class="px-3 py-2 whitespace-nowrap">Category</th>
                                <th class="px-3 py-2 text-right whitespace-nowrap">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in recent" :key="t.id" class="border-b border-border/50 last:border-0">
                                <td class="px-3 py-2 whitespace-nowrap">{{ t.date }}</td>
                                <td class="max-w-[40ch] px-3 py-2">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span class="block truncate">{{ t.description }}</span>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ t.description }}</TooltipContent>
                                    </Tooltip>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ t.category }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap" :class="t.amount < 0 ? 'text-red-400' : 'text-emerald-400'">
                                    {{ money(t.amount, t.currency) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </section>
    </AppLayout>
</template>
