import { InputHTMLAttributes, forwardRef } from 'react'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  icon?: React.ReactNode
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, icon, className = '', ...props }, ref) => {
    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label className="text-sm font-medium text-gray-700">{label}</label>
        )}
        <div className="relative">
          {icon && (
            <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
              {icon}
            </div>
          )}
          <input
            ref={ref}
            className={`w-full rounded-lg border text-sm transition-colors
              ${error ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white'}
              ${icon ? 'pl-10' : 'pl-3'} pr-3 py-2.5
              placeholder:text-gray-400
              focus:outline-none focus:ring-2 focus:ring-[#0ea472]/30 focus:border-[#0ea472]
              ${className}`}
            {...props}
          />
        </div>
        {error && <p className="text-xs text-red-600">{error}</p>}
      </div>
    )
  }
)
Input.displayName = 'Input'
