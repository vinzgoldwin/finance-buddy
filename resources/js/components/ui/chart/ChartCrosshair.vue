<script setup lang="ts">
import type { BulletLegendItemInterface } from '@unovis/ts'
import { omit } from '@unovis/ts'
import { VisCrosshair, VisTooltip } from '@unovis/vue'
import { type Component, createApp } from 'vue'
import { ChartTooltip } from '.'

const props = withDefaults(defineProps<{
  colors: string[]
  index: string
  items: BulletLegendItemInterface[]
  customTooltip?: Component
}>(), {
  colors: () => [],
  items: () => [],
})

// Use weakmap to store reference to each datapoint for Tooltip
const wm = new WeakMap()
function template(d: any) {
  if (wm.has(d)) {
    return wm.get(d)
  }
  else {
    const componentDiv = document.createElement('div')
    const omittedData = Object.entries(omit(d, [props.index])).map(([key, value]) => {
      // Ensure props.items is an array before using array methods
      const itemsArray = Array.isArray(props.items) ? props.items : []
      
      // Find legend reference or create a default one
      const legendReference = itemsArray.find(i => i.name === key) || { 
        name: key, 
        color: props.colors[Object.keys(omit(d, [props.index])).indexOf(key)] || '#94a3b8' 
      }
      return { ...legendReference, value }
    })
    const TooltipComponent = props.customTooltip ?? ChartTooltip
    createApp(TooltipComponent, { title: d[props.index].toString(), data: omittedData }).mount(componentDiv)
    wm.set(d, componentDiv.innerHTML)
    return componentDiv.innerHTML
  }
}

function color(d: unknown, i: number) {
  return props.colors[i] ?? 'transparent'
}
</script>

<template>
  <VisTooltip :horizontal-shift="20" :vertical-shift="20" />
  <VisCrosshair :template="template" :color="color" />
</template>
