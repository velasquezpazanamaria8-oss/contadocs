'use client'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  LayoutDashboard, Building2, Users, Upload,
  FolderOpen, History, Settings, LogOut, FileText
} from 'lucide-react'

interface NavItem {
  href: string
  label: string
  icon: React.ReactNode
}

interface SidebarProps {
  rol: 'superadmin' | 'contador' | 'cliente'
  nombre?: string
  plan?: string
}

const navAdmin: NavItem[] = [
  { href: '/admin/dashboard', label: 'Dashboard', icon: <LayoutDashboard size={16}/> },
  { href: '/admin/estudios',  label: 'Estudios',  icon: <Building2 size={16}/> },
  { href: '/admin/pagos',     label: 'Pagos',     icon: <History size={16}/> },
]

const navContador: NavItem[] = [
  { href: '/contador/clientes',   label: 'Mis clientes',  icon: <Users size={16}/> },
  { href: '/contador/subir',      label: 'Subir docs',    icon: <Upload size={16}/> },
  { href: '/contador/categorias', label: 'Categorías',    icon: <FolderOpen size={16}/> },
  { href: '/contador/descargas',  label: 'Descargas',     icon: <History size={16}/> },
  { href: '/contador/cuenta',     label: 'Mi cuenta',     icon: <Settings size={16}/> },
]

const navCliente: NavItem[] = [
  { href: '/cliente/documentos', label: 'Mis documentos', icon: <FileText size={16}/> },
  { href: '/cliente/historial',  label: 'Historial',      icon: <History size={16}/> },
]

const planColors: Record<string, string> = {
  basico:       'bg-gray-100 text-gray-600',
  profesional:  'bg-blue-50 text-blue-700',
  ilimitado:    'bg-purple-50 text-purple-700',
}
const planLabels: Record<string, string> = {
  basico: 'Básico', profesional: 'Profesional', ilimitado: 'Ilimitado',
}

export function Sidebar({ rol, nombre, plan }: SidebarProps) {
  const pathname = usePathname()
  const items = rol === 'superadmin' ? navAdmin : rol === 'contador' ? navContador : navCliente

  return (
    <aside className="w-56 shrink-0 bg-white border-r border-gray-200 flex flex-col min-h-screen">
      {/* Logo */}
      <div className="px-5 py-4 border-b border-gray-100">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-lg bg-[#0ea472] flex items-center justify-center">
            <FileText size={14} className="text-white"/>
          </div>
          <span className="font-bold text-gray-900 text-sm tracking-tight">ContaDocs</span>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 px-3 py-4 flex flex-col gap-0.5">
        {items.map((item) => {
          const active = pathname === item.href
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors
                ${active
                  ? 'bg-[#e8f8f2] text-[#0ea472] font-medium'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}`}
            >
              <span className={active ? 'text-[#0ea472]' : 'text-gray-400'}>{item.icon}</span>
              {item.label}
            </Link>
          )
        })}
      </nav>

      {/* Footer */}
      <div className="px-3 py-4 border-t border-gray-100">
        {nombre && (
          <div className="px-3 py-2 mb-2">
            <p className="text-xs font-medium text-gray-900 truncate">{nombre}</p>
            {plan && (
              <span className={`inline-block mt-0.5 text-xs px-1.5 py-0.5 rounded font-medium ${planColors[plan] ?? planColors.basico}`}>
                {planLabels[plan] ?? plan}
              </span>
            )}
          </div>
        )}
        <form action="/api/auth/logout" method="POST">
          <button
            type="submit"
            className="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors"
          >
            <LogOut size={16}/> Cerrar sesión
          </button>
        </form>
      </div>
    </aside>
  )
}
