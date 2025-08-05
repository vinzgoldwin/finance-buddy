<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { Info } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
  spendingLimit: {
    amount: number
    interval: string
    spent: number
    currency: 'USD' | 'IDR'
    dateRange: string
  }
  currency: 'USD' | 'IDR'
}>()

const emit = defineEmits<{
  (e: 'update:spendingLimit', value: { amount: number; interval: string; spent: number; currency: 'USD' | 'IDR'; dateRange: string }): void
}>()

// Format money
const money = (v: number, curr: 'USD' | 'IDR' = props.spendingLimit.currency) => {
  return new Intl.NumberFormat(curr === 'IDR' ? 'id-ID' : 'en-US', {
    style: 'currency',
    currency: curr
  }).format(v)
}

const intervalLabels = {
  daily: 'Daily',
  weekly: 'Weekly',
  monthly: 'Monthly'
}

// Dialog state
const isDialogOpen = ref(false)

// Form state
const formAmount = ref(props.spendingLimit.amount)
const formInterval = ref(props.spendingLimit.interval)
const formCurrency = ref(props.spendingLimit.currency)

// Computed values
const percentage = computed(() => {
  if (props.spendingLimit.amount <= 0) return 0
  return Math.min(100, (props.spendingLimit.spent / props.spendingLimit.amount) * 100)
})

const progressBarClass = computed(() => {
  if (percentage.value < 50) return 'bg-green-500'
  if (percentage.value < 75) return 'bg-yellow-500'
  return 'bg-red-500'
})

// Methods
const openDialog = () => {
  // Reset form to current values when opening dialog
  formAmount.value = props.spendingLimit.amount
  formInterval.value = props.spendingLimit.interval
  formCurrency.value = props.spendingLimit.currency
  isDialogOpen.value = true
}

const saveLimit = () => {
  router.post(route('spending-limit.store'), {
    amount: formAmount.value,
    interval: formInterval.value,
    currency: formCurrency.value
  }, {
    onSuccess: () => {
      emit('update:spendingLimit', {
        amount: formAmount.value,
        interval: formInterval.value,
        spent: props.spendingLimit.spent,
        currency: formCurrency.value,
        dateRange: props.spendingLimit.dateRange
      })
      isDialogOpen.value = false
    }
  })
}
</script>

<template>
  <Card class="w-full">
    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
      <CardTitle class="text-sm font-medium flex items-center gap-2">
        {{ intervalLabels[spendingLimit.interval] }} Spending Limit
        <Tooltip>
          <TooltipTrigger as-child>
            <Info class="h-4 w-4 text-muted-foreground cursor-help" />
          </TooltipTrigger>
          <TooltipContent>
            <p>Spending calculated for: {{ spendingLimit.dateRange }}</p>
          </TooltipContent>
        </Tooltip>
      </CardTitle>
      <Button variant="link" class="h-auto p-0 text-sm text-muted-foreground" @click="openDialog">
        Modify
      </Button>
    </CardHeader>
    <CardContent>
      <div class="flex justify-between text-sm mb-1">
        <span class="text-muted-foreground">Spent: {{ money(spendingLimit.spent) }}</span>
        <span class="text-muted-foreground">Limit: {{ money(spendingLimit.amount) }}</span>
      </div>
      <div class="w-full rounded-full bg-secondary h-2">
        <div 
          class="h-2 rounded-full transition-all duration-300" 
          :class="progressBarClass"
          :style="{ width: percentage + '%' }"
        ></div>
      </div>
    </CardContent>
  </Card>

  <Dialog v-model:open="isDialogOpen">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>Update Spending Limit</DialogTitle>
      </DialogHeader>
      <div class="grid gap-4 py-4">
        <div class="grid grid-cols-4 items-center gap-4">
          <label for="amount" class="text-right">Amount</label>
          <Input
            id="amount"
            v-model.number="formAmount"
            type="number"
            min="0"
            step="0.01"
            class="col-span-3"
          />
        </div>
        <div class="grid grid-cols-4 items-center gap-4">
          <label for="interval" class="text-right">Interval</label>
          <Select v-model="formInterval">
            <SelectTrigger class="col-span-3">
              <SelectValue placeholder="Select interval" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="daily">Daily</SelectItem>
              <SelectItem value="weekly">Weekly</SelectItem>
              <SelectItem value="monthly">Monthly</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="grid grid-cols-4 items-center gap-4">
          <label for="currency" class="text-right">Currency</label>
          <Select v-model="formCurrency">
            <SelectTrigger class="col-span-3">
              <SelectValue placeholder="Select currency" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="IDR">IDR</SelectItem>
              <SelectItem value="USD">USD</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
        <Button @click="saveLimit">Save</Button>
      </div>
    </DialogContent>
  </Dialog>
</template>