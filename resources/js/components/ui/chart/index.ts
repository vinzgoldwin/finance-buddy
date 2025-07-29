export { default as ChartCrosshair } from './ChartCrosshair.vue'
export { default as ChartLegend } from './ChartLegend.vue'
export { default as ChartSingleTooltip } from './ChartSingleTooltip.vue'
export { default as ChartTooltip } from './ChartTooltip.vue'

export function defaultColors (count: number = 6): string[] {
    if (count < 1) return []

    const palette = ['var(--foreground)']

    for (let i = 1; i < count; i++) {
        const accentIndex = ((i - 1) % 5) + 1
        palette.push(`var(--chart-${accentIndex})`)
    }

    return palette
}

export * from './interface'
