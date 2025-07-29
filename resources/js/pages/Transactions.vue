<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// UI Components
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'
// Remove pagination import for now

// Icons
import { Plus, Search, MoreHorizontal, Edit, Trash2, Calendar, DollarSign, FileText } from 'lucide-vue-next'

// Types
interface Transaction {
    id: number
    date: string
    description: string
    amount: number
    currency: 'USD' | 'IDR'
    category: {
        id: number
        name: string
    }
}

interface Category {
    id: number
    name: string
}

interface PaginatedTransactions {
    data: Transaction[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
}

const props = defineProps<{
    transactions: PaginatedTransactions
    categories: Category[]
    filters: {
        year: number
        month: number | null
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transactions', href: '/transactions' },
]

// Filters
const currentYear = ref(props.filters.year)
const currentMonth = ref(props.filters.month)
const searchQuery = ref('')

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingTransaction = ref<Transaction | null>(null)

// Forms
const createForm = useForm({
    date: new Date().toISOString().split('T')[0],
    description: '',
    amount: 0,
    currency: 'USD' as 'USD' | 'IDR',
    category_id: '',
})

const editForm = useForm({
    date: '',
    description: '',
    amount: 0,
    currency: 'USD' as 'USD' | 'IDR',
    category_id: '',
})

// Computed
const yearOptions = computed(() => {
    const currentYearNum = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => currentYearNum - i)
})

const monthOptions = [
    { value: null, label: 'All Months' },
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
]

const filteredTransactions = computed(() => {
    if (!searchQuery.value) return props.transactions.data

    return props.transactions.data.filter(transaction =>
        transaction.description.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        transaction.category.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

// Helper functions
const formatMoney = (amount: number, currency: string) => {
    return new Intl.NumberFormat(currency === 'IDR' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: currency
    }).format(Math.abs(amount))
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getCategoryColor = (categoryName: string) => {
    const colors: Record<string, string> = {
        'Housing & Utilities': 'bg-blue-100 text-blue-800',
        'Food & Groceries': 'bg-orange-100 text-orange-800',
        'Transport & Travel': 'bg-cyan-100 text-cyan-800',
        'Health & Insurance': 'bg-red-100 text-red-800',
        'Shopping & Lifestyle': 'bg-purple-100 text-purple-800',
        'Savings & Investing': 'bg-green-100 text-green-800',
        'Income': 'bg-emerald-100 text-emerald-800',
    }
    return colors[categoryName] || 'bg-slate-100 text-slate-800'
}

const isIncome = (transaction: Transaction) => {
    return transaction.category.name === 'Income'
}

// Actions
const openCreateModal = () => {
    createForm.reset()
    createForm.date = new Date().toISOString().split('T')[0]
    showCreateModal.value = true
}

const openEditModal = (transaction: Transaction) => {
    editingTransaction.value = transaction
    editForm.date = transaction.date
    editForm.description = transaction.description
    editForm.amount = Math.abs(transaction.amount)
    editForm.currency = transaction.currency
    editForm.category_id = transaction.category.id.toString()
    showEditModal.value = true
}

const createTransaction = () => {
    createForm.post(route('transactions.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        }
    })
}

const updateTransaction = () => {
    if (!editingTransaction.value) return

    editForm.put(route('transactions.update', editingTransaction.value.id), {
        onSuccess: () => {
            showEditModal.value = false
            editingTransaction.value = null
            editForm.reset()
        }
    })
}

const deleteTransaction = (transaction: Transaction) => {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(route('transactions.destroy', transaction.id))
    }
}

const applyFilters = () => {
    router.get(route('transactions.index'), {
        year: currentYear.value,
        month: currentMonth.value,
    }, {
        preserveState: true,
        replace: true,
    })
}

// Watchers
watch([currentYear, currentMonth], applyFilters)
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs" class="px-2">
        <!-- Header with controls -->
        <div class="p-6 flex flex-col gap-4 px-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <FileText class="h-8 w-8 text-primary" />
                <div>
                    <h1 class="text-2xl font-bold">Transactions</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your financial transactions
                    </p>
                </div>
            </div>

            <Button @click="openCreateModal" class="shrink-0">
                <Plus class="h-4 w-4 mr-2" />
                Add Transaction
            </Button>
        </div>

        <!-- Filters and Search -->
        <Card class="mx-4 mb-6">
            <CardContent class="pt-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <!-- Search -->
                    <div class="relative md:col-span-2">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search transactions..."
                            class="pl-10"
                        />
                    </div>

                    <!-- Year Filter -->
                    <Select v-model="currentYear">
                        <SelectTrigger>
                            <SelectValue placeholder="Select year" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="year in yearOptions" :key="year" :value="year.toString()">
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Month Filter -->
                    <Select v-model="currentMonth">
                        <SelectTrigger>
                            <SelectValue placeholder="Select month" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="month in monthOptions" :key="month.value" :value="month.value?.toString() || null">
                                {{ month.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <!-- Transactions Table -->
        <Card class="mx-4 mb-6">
            <CardHeader>
                <CardTitle>
                    Transactions ({{ transactions.total }})
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-border bg-muted/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-medium">Date</th>
                                <th class="px-6 py-4 text-left text-sm font-medium">Description</th>
                                <th class="px-6 py-4 text-left text-sm font-medium">Category</th>
                                <th class="px-6 py-4 text-right text-sm font-medium">Amount</th>
                                <th class="px-6 py-4 text-center text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="transaction in filteredTransactions"
                                :key="transaction.id"
                                class="border-b border-border/50 hover:bg-muted/30 transition-colors"
                            >
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                        {{ formatDate(transaction.date) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs truncate text-sm font-medium">
                                        {{ transaction.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <Badge :class="getCategoryColor(transaction.category.name)">
                                        {{ transaction.category.name }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span
                                        class="text-sm font-medium"
                                        :class="isIncome(transaction) ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ isIncome(transaction) ? '+' : '-' }}{{ formatMoney(transaction.amount, transaction.currency) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="openEditModal(transaction)">
                                                <Edit class="h-4 w-4 mr-2" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="deleteTransaction(transaction)"
                                                class="text-red-600 focus:text-red-600"
                                            >
                                                <Trash2 class="h-4 w-4 mr-2" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty state -->
                    <div v-if="filteredTransactions.length === 0" class="py-16 text-center">
                        <DollarSign class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No transactions found</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Get started by adding your first transaction.
                        </p>
                        <Button @click="openCreateModal" class="mt-4">
                            <Plus class="h-4 w-4 mr-2" />
                            Add Transaction
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Simple Pagination -->
        <div v-if="transactions.last_page > 1" class="mx-4 mb-6 flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
                Showing {{ ((transactions.current_page - 1) * transactions.per_page) + 1 }} to {{ Math.min(transactions.current_page * transactions.per_page, transactions.total) }} of {{ transactions.total }} results
            </div>
            <div class="flex gap-2">
                <Button
                    v-for="link in transactions.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    size="sm"
                    @click="link.url && router.visit(link.url)"
                >
                    {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
                </Button>
            </div>
        </div>

        <!-- Create Transaction Modal -->
        <Dialog v-model:open="showCreateModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add New Transaction</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="createTransaction" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label for="create-date">Date</Label>
                            <Input
                                id="create-date"
                                v-model="createForm.date"
                                type="date"
                                required
                            />
                        </div>

                        <div>
                            <Label for="create-currency">Currency</Label>
                            <Select v-model="createForm.currency">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select currency" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="USD">USD</SelectItem>
                                    <SelectItem value="IDR">IDR</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div>
                        <Label for="create-description">Description</Label>
                        <Input
                            id="create-description"
                            v-model="createForm.description"
                            placeholder="Enter description"
                            required
                        />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label for="create-amount">Amount</Label>
                            <Input
                                id="create-amount"
                                v-model="createForm.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                            />
                        </div>

                        <div>
                            <Label for="create-category">Category</Label>
                            <Select v-model="createForm.category_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <Button type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Creating...' : 'Create Transaction' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="showCreateModal = false"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Transaction Modal -->
        <Dialog v-model:open="showEditModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Transaction</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="updateTransaction" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label for="edit-date">Date</Label>
                            <Input
                                id="edit-date"
                                v-model="editForm.date"
                                type="date"
                                required
                            />
                        </div>

                        <div>
                            <Label for="edit-currency">Currency</Label>
                            <Select v-model="editForm.currency">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select currency" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="USD">USD</SelectItem>
                                    <SelectItem value="IDR">IDR</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div>
                        <Label for="edit-description">Description</Label>
                        <Input
                            id="edit-description"
                            v-model="editForm.description"
                            placeholder="Enter description"
                            required
                        />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label for="edit-amount">Amount</Label>
                            <Input
                                id="edit-amount"
                                v-model="editForm.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                            />
                        </div>

                        <div>
                            <Label for="edit-category">Category</Label>
                            <Select v-model="editForm.category_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Updating...' : 'Update Transaction' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="showEditModal = false"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
