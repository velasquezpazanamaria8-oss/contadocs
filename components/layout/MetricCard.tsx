import { ReactNode } from 'react'

interface MetricCardProps {
  label: string
  value: string | number
  sub?: string
  subColor?: string
  icon?: ReactNode
  iconBg?: string
}

export function MetricCard({ label, value, sub, subColor = 'text-gray-400', icon, iconBg = 'bg-gray-100' }: MetricCardProps) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
      {icon && (
        <div className={`w-10 h-10 rounded-lg ${iconBg} flex items-center justify-center shrink-0`}>
          {icon}
        </div>
      )}
      <div>
        <p className="text-xs text-gray-500 mb-1">{label}</p>
        <p className="text-2xl font-bold text-gray-900 leading-none">{value}</p>
        {sub && <p className={`text-xs mt-1.5 ${subColor}`}>{sub}</p>}
      </div>
    </div>
  )
}
