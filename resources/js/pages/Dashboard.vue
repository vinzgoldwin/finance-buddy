<script setup lang="ts">
/* ───────────── imports ───────────── */
import { Head, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import {
    Card, CardHeader, CardTitle, CardDescription, CardContent,
} from '@/components/ui/card'
import { BarChart } from '@/components/ui/chart-bar'
import { Doughnut } from 'vue-chartjs'
import {
    CategoryScale, LinearScale, PointElement, LineElement,
    Title, ArcElement, Legend, Chart as ChartJS,
} from 'chart.js'

/*  shadcn-vue controls  */
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

ChartJS.register(
    CategoryScale, LinearScale, PointElement,
    LineElement, ArcElement, Title, Legend,
)

/* ───────────── Inertia props ───────────── */
interface Metrics { income:number; expenses:number; netPct:number }
const props = defineProps<{
    metrics: Metrics
    monthly: Array<{ month:string; income:number; expenses:number }>
    categories: Array<{ label:string; value:number }>
    recent: Array<{
        id:number; date:string; description:string;
        category:string; amount:number; currency:string
    }>
    currency: 'USD' | 'IDR'
    date?: string
    monthOptions: Array<{ value:string; label:string }>
    barData: Array<{ name:string; income:number; expenses:number }>
}>()

/* ───────────── helpers ───────────── */
const money = (v:number, curr = props.currency) =>
    new Intl.NumberFormat(curr === 'IDR' ? 'id-ID' : 'en-US',
        { style:'currency', currency:curr }).format(v)

const COLOR_BY_CATEGORY:Record<string,string> = {
    'Housing & Utilities':'#3b82f6','Food & Groceries':'#f97316',
    'Transport & Travel':'#0ea5e9','Health & Insurance':'#ef4444',
    'Shopping & Lifestyle':'#a855f7','Savings & Investing':'#10b981','Other':'#64748b',
}

const donutData = computed(() => {
    const labels = props.categories.map(c => c.label)
    const data   = props.categories.map(c => c.value)
    const bg     = labels.map(l => COLOR_BY_CATEGORY[l] ?? '#d1d5db')
    return { labels, datasets:[{ data, backgroundColor:bg, hoverBackgroundColor:bg, borderWidth:0 }] }
})


const barData = computed(() => props.barData)

/* ───────────── month-picker state ───────────── */
const monthOptions = props.monthOptions
const today = new Date()
const currentMonth = ref(
    props.date ?? monthOptions[0]?.value ?? `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
)

/* ───────────── currency tabs state ───────────── */
const currencyTab = ref<'USD'|'IDR'>(props.currency)

/*  react to changes  */
watch(currentMonth, (val) => {
    router.get('/dashboard',
        { currency: currencyTab.value, date: val },
        { preserveState: true, replace: true },
    )
})
watch(currencyTab, (val) => {
    router.get('/dashboard',
        { currency: val, date: currentMonth.value },
        { preserveState: true, replace: true },
    )
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="[{ title:'Dashboard', href:'/dashboard' }]" class="px-2">

        <!-- ── control strip ───────────────────────────────────────── -->
        <div class="mb-1 ml-4 flex flex-wrap gap-3 p-2">

            <!-- currency toggle as Tabs -->
            <Tabs v-model="currencyTab" class="w-[120px]">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="IDR">IDR</TabsTrigger>
                    <TabsTrigger value="USD">USD</TabsTrigger>
                </TabsList>
            </Tabs>

            <!-- month picker -->
            <Select v-model="currentMonth">
                <SelectTrigger class="w-[150px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="opt in monthOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- ── dashboard grid ──────────────────────────────────────── -->
        <section class="grid gap-6 px-3 md:grid-cols-3 xl:grid-cols-4">

            <!-- metrics -->
            <Card class="col-span-full sm:col-span-1">
                <CardHeader>
                    <CardDescription>Total Income</CardDescription>
                    <CardTitle class="text-2xl">{{ money(metrics.income) }}</CardTitle>
                </CardHeader>
            </Card>

            <Card class="col-span-full sm:col-span-1">
                <CardHeader>
                    <CardDescription>Total Expenses</CardDescription>
                    <CardTitle class="text-2xl">{{ money(metrics.expenses) }}</CardTitle>
                </CardHeader>
            </Card>

            <Card class="col-span-full sm:col-span-1">
                <CardHeader>
                    <CardDescription>Net Balance</CardDescription>
                    <CardTitle class="text-3xl text-emerald-400">
                        {{ metrics.netPct }}%
                    </CardTitle>
                </CardHeader>
            </Card>

            <!-- donut -->
            <Card class="row-span-2">
                <CardHeader><CardTitle>Spending Categories</CardTitle></CardHeader>
                <CardContent class="flex min-h-56 flex-col items-center justify-center">
                    <div class="w-full h-64">
                    <Doughnut
                        :data="donutData"
                        :options="{
                            responsive:true,
                            maintainAspectRatio: false,
                            layout: { padding: { bottom: 10} },
                            plugins: {
                                legend: {
                                   position:'bottom',
                                   align:'start',
                                   labels: {
                                        boxWidth:24,
                                        boxHeight:14
                                    }
                                }
                            }
                        }"
                    />
                    </div>
                </CardContent>
            </Card>

            <!-- Bar Chart -->
            <Card class="col-span-full xl:col-span-3">
                <CardHeader><CardTitle>6-Month Income vs Expenses</CardTitle></CardHeader>
                <CardContent class="h-60 ">
                    <BarChart
                        class="h-full"
                        :data="barData"
                        index="name"
                        :categories="['income', 'expenses']"
                        :y-formatter="tick =>
                        typeof tick === 'number' ? money(tick, currencyTab)  : ''"
                    />
                </CardContent>
            </Card>

            <!-- recent transactions -->
            <Card class="col-span-full xl:col-span-3">
                <CardHeader><CardTitle>Recent Transactions</CardTitle></CardHeader>
                <CardContent class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-border">
                        <tr class="text-left text-muted-foreground">
                            <th class="px-3 py-2 whitespace-nowrap">Date</th>
                            <th class="px-3 py-2 max-w-[40ch] truncate">Description</th>
                            <th class="px-3 py-2 whitespace-nowrap">Category</th>
                            <th class="px-3 py-2 whitespace-nowrap text-right">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="t in recent" :key="t.id"
                            class="border-b border-border/50 last:border-0"
                        >
                            <td class="px-3 py-2 whitespace-nowrap">{{ t.date }}</td>
                            <td class="px-3 py-2 max-w-[40ch]">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span class="block truncate">{{ t.description }}</span>
                                    </TooltipTrigger>
                                    <TooltipContent>{{ t.description }}</TooltipContent>
                                </Tooltip>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ t.category }}</td>
                            <td
                                class="px-3 py-2 whitespace-nowrap text-right"
                                :class="t.amount < 0 ? 'text-red-400' : 'text-emerald-400'"
                            >
                                {{ money(t.amount, t.currency) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- coach stub -->
            <Card class="row-span-2">
                <CardHeader><CardTitle>AI Finance Coach</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <p class="rounded-lg bg-muted p-4">
                        Consider reducing your spending on shopping to increase your savings.
                    </p>
                    <input
                        class="w-full rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none"
                        placeholder="Type a message…"
                    />
                </CardContent>
            </Card>
        </section>
    </AppLayout>
</template>
