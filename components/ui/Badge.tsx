import { ReactNode } from 'react'

interface BadgeProps {
  children: ReactNode
  variant?: 'green' | 'red' | 'amber' | 'blue' | 'purple' | 'gray'
}

const styles = {
  green:  'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
  red:    'bg-red-50 text-red-700 ring-1 ring-red-200',
  amber:  'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
  blue:   'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
  purple: 'bg-purple-50 text-purple-700 ring-1 ring-purple-200',
  gray:   'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
}

export function Badge({ children, variant = 'gray' }: BadgeProps) {
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${styles[variant]}`}>
      {children}
    </span>
  )
}
