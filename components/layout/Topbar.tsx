import { Bell } from 'lucide-react'

interface TopbarProps {
  titulo: string
  subtitulo?: string
  acciones?: React.ReactNode
}

export function Topbar({ titulo, subtitulo, acciones }: TopbarProps) {
  return (
    <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <div>
        <h1 className="text-base font-semibold text-gray-900">{titulo}</h1>
        {subtitulo && <p className="text-xs text-gray-500 mt-0.5">{subtitulo}</p>}
      </div>
      <div className="flex items-center gap-3">
        {acciones}
        <button className="relative p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
          <Bell size={18}/>
        </button>
      </div>
    </header>
  )
}
