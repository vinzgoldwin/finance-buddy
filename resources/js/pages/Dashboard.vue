<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, } from '@/components/ui/card'
import { Line, Doughnut } from 'vue-chartjs'
import { CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, ArcElement, Legend, Chart as ChartJS } from 'chart.js'
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Title, Tooltip, Legend)

// 👇 Inertia props ----------------------------------------------------------
interface Metrics { income: number; expenses: number; netPct: number }
const props = defineProps<{
    metrics: Metrics
    monthly: Array<{ month: string; income: number; expenses: number }>
    categories: Array<{ label: string; value: number }>
    recent: Array<{ id: number; date: string; description: string; category: string; amount: number }>
}>()

// Formatters
const money = (v: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v)

const lineData = computed(() => {
    return {
        labels: props.monthly.map(m => m.month),
        datasets: [
            {
                label: 'Income',
                data : props.monthly.map(m => m.income),
                tension: 0.4,
            },
            {
                label: 'Expenses',
                data : props.monthly.map(m => m.expenses),
                tension: 0.4,
            },
        ],
    }
})

const donutData = computed(() => ({
    labels  : props.categories.map(c => c.label),
    datasets: [{ data: props.categories.map(c => c.value) }],
}))
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]" class="px-4">
        <section class="grid gap-6 md:grid-cols-3 xl:grid-cols-4">
            <!-- Metric cards ------------------------------------------------------>
            <Card class="col-span-full sm:col-span-1">
                <CardHeader>
                    <CardDescription>Total Income</CardDescription>
                    <CardTitle class="text-3xl">{{ money(metrics.income) }}</CardTitle>
                </CardHeader>
            </Card>

            <Card class="col-span-full sm:col-span-1">
                <CardHeader>
                    <CardDescription>Total Expenses</CardDescription>
                    <CardTitle class="text-3xl">{{ money(metrics.expenses) }}</CardTitle>
                </CardHeader>
            </Card>

            <Card class="col-span-full sm:col-span-1">
                <CardHeader>
                    <CardDescription>Net Balance</CardDescription>
                    <CardTitle class="text-3xl text-emerald-400">{{ metrics.netPct }}%</CardTitle>
                </CardHeader>
            </Card>

            <!-- Donut ------------------------------------------------------------->
            <Card class="row-span-2">
                <CardHeader><CardTitle>Spending Categories</CardTitle></CardHeader>
                <CardContent class="h-72 flex items-center justify-center">
                    <Doughnut :data="donutData" :options="{ plugins: { legend: { position:'right' } } }" />
                </CardContent>
            </Card>

            <!-- Line chart -------------------------------------------------------->
            <Card class="col-span-full xl:col-span-3">
                <CardHeader><CardTitle>Income & Expenses</CardTitle></CardHeader>
                <CardContent class="h-72">
                    <Line :data="lineData" :options="{ responsive: true, maintainAspectRatio: false }" />
                </CardContent>
            </Card>

            <!-- Recent transactions --------------------------------------------->
            <Card class="col-span-full xl:col-span-3">
                <CardHeader><CardTitle>Recent Transactions</CardTitle></CardHeader>
                <CardContent class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-border">
                        <tr class="text-left text-muted-foreground">
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3">Description</th>
                            <th class="py-2 px-3">Category</th>
                            <th class="py-2 px-3 text-right">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="t in recent"
                            :key="t.id"
                            class="border-b border-border/50 last:border-0"
                        >
                            <td class="py-2 px-3">{{ t.date }}</td>
                            <td class="py-2 px-3">{{ t.description }}</td>
                            <td class="py-2 px-3">{{ t.category }}</td>
                            <td
                                class="py-2 px-3 text-right"
                                :class="t.amount < 0 ? 'text-red-400' : 'text-emerald-400'"
                            >
                                {{ money(t.amount) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card class="row-span-2">
                <CardHeader><CardTitle>AI Finance Coach</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <p class="p-4 rounded-lg bg-muted">
                        Consider reducing your spending on shopping to increase your
                        savings.
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
