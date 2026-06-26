'use client'
import { useEffect, useState } from 'react'
import { Sidebar } from '@/components/layout/Sidebar'
import { Topbar } from '@/components/layout/Topbar'
import { Badge } from '@/components/ui/Badge'
import { Download, FileText, Search, Filter } from 'lucide-react'

function formatBytes(b: number) {
  if (b < 1024) return b + ' B'
  if (b < 1048576) return (b/1024).toFixed(0) + ' KB'
  return (b/1048576).toFixed(1) + ' MB'
}

function formatFecha(f: string) {
  return new Date(f).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' })
}

export default function DocumentosPage() {
  const [docs, setDocs] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [periodo, setPeriodo] = useState('')
  const [descargando, setDescargando] = useState<string|null>(null)

  useEffect(() => { cargar() }, [periodo])

  async function cargar() {
    setLoading(true)
    const params = periodo ? `?periodo=${periodo}` : ''
    const r = await fetch(`/api/cliente/documentos${params}`)
    if (r.ok) setDocs(await r.json())
    setLoading(false)
  }

  async function descargar(doc: any) {
    setDescargando(doc.id)
    const r = await fetch(`/api/cliente/descargar?id=${doc.id}`)
    if (r.ok) {
      const blob = await r.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url; a.download = doc.nombre; a.click()
      URL.revokeObjectURL(url)
    }
    setDescargando(null)
  }

  const periodos = [...new Set(docs.map(d => d.periodo))].sort().reverse()

  const coloresCat: Record<string, { bg: string; text: string }> = {}
  docs.forEach(d => {
    if (d.categoria && !coloresCat[d.categoria.id]) {
      coloresCat[d.categoria.id] = { bg: d.categoria.color, text: d.categoria.color_texto }
    }
  })

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar rol="cliente" nombre="Mi portal"/>
      <div className="flex-1 flex flex-col">
        <Topbar titulo="Mis documentos" subtitulo="Documentos preparados por tu contador"/>
        <main className="flex-1 p-6">

          {/* Filtros */}
          <div className="flex items-center gap-3 mb-6">
            <div className="flex items-center gap-2 text-sm text-gray-500">
              <Filter size={14}/>
              <span>Filtrar por período:</span>
            </div>
            <select value={periodo} onChange={e => setPeriodo(e.target.value)}
              className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472]">
              <option value="">Todos los períodos</option>
              {periodos.map(p => (
                <option key={p} value={p}>{p}</option>
              ))}
            </select>
            <span className="ml-auto text-xs text-gray-400">{docs.length} documentos</span>
          </div>

          {loading ? (
            <div className="flex items-center justify-center h-40">
              <div className="flex items-center gap-2 text-gray-400 text-sm">
                <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Cargando documentos...
              </div>
            </div>
          ) : docs.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-64 text-center">
              <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                <FileText size={28} className="text-gray-300"/>
              </div>
              <p className="text-sm font-medium text-gray-500">No hay documentos disponibles</p>
              <p className="text-xs text-gray-400 mt-1">Tu contador aún no ha subido documentos para este período</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-3">
              {docs.map(doc => {
                const yaDescargado = doc.descargas?.length > 0
                const colors = doc.categoria ? coloresCat[doc.categoria.id] : null
                return (
                  <div key={doc.id}
                    className="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4 hover:border-gray-300 transition-colors group">
                    <div className="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                      <FileText size={18} className="text-red-500"/>
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-0.5">
                        <p className="text-sm font-medium text-gray-900 truncate">{doc.nombre}</p>
                        {!yaDescargado && (
                          <span className="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            Nuevo
                          </span>
                        )}
                      </div>
                      <div className="flex items-center gap-3 text-xs text-gray-400">
                        {doc.categoria && (
                          <span className="px-1.5 py-0.5 rounded text-xs font-medium"
                            style={{ background: colors?.bg ?? '#f3f4f6', color: colors?.text ?? '#4b5563' }}>
                            {doc.categoria.nombre}
                          </span>
                        )}
                        <span>{doc.periodo}</span>
                        {doc.tamanio && <span>{formatBytes(doc.tamanio)}</span>}
                        <span>Subido el {formatFecha(doc.created_at)}</span>
                      </div>
                    </div>
                    <button
                      onClick={() => descargar(doc)}
                      disabled={descargando === doc.id}
                      className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                        bg-[#0ea472] hover:bg-[#0b8a5f] text-white transition-colors
                        disabled:opacity-60 shrink-0">
                      {descargando === doc.id ? (
                        <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                      ) : <Download size={14}/>}
                      {descargando === doc.id ? 'Descargando...' : 'Descargar'}
                    </button>
                  </div>
                )
              })}
            </div>
          )}
        </main>
      </div>
    </div>
  )
}
