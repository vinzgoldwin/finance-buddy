export interface ToastProps {
  id: string
  title?: string
  description?: string
  type?: 'default' | 'destructive' | 'success' | 'warning' | 'info'
  open?: boolean
  duration?: number
  onOpenChange?: (open: boolean) => void
}