'use client'
import { useEffect, useState } from 'react'
import { Sidebar } from '@/components/layout/Sidebar'
import { Topbar } from '@/components/layout/Topbar'
import { MetricCard } from '@/components/layout/MetricCard'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Users, Upload, Eye, Plus, Search, FileText, TrendingUp } from 'lucide-react'

export default function ClientesPage() {
  const [empresas, setEmpresas] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [buscar, setBuscar] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ razon_social:'', ruc:'', email_acceso:'' })
  const [saving, setSaving] = useState(false)
  const [resultado, setResultado] = useState<any>(null)
  const [error, setError] = useState('')

  useEffect(() => { cargar() }, [])

  async function cargar() {
    setLoading(true)
    const r = await fetch('/api/contador/empresas')
    if (r.ok) setEmpresas(await r.json())
    setLoading(false)
  }

  async function crearEmpresa(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true); setError('')
    const r = await fetch('/api/contador/empresas', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form)
    })
    const data = await r.json()
    if (r.ok) { setResultado(data); cargar() }
    else setError(data.error ?? 'Error al crear')
    setSaving(false)
  }

  const filtradas = empresas.filter(e =>
    e.razon_social.toLowerCase().includes(buscar.toLowerCase()) ||
    e.ruc.includes(buscar)
  )

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar rol="contador" nombre="Mi estudio" plan="profesional"/>
      <div className="flex-1 flex flex-col">
        <Topbar titulo="Mis clientes" subtitulo="Gestiona las empresas de tu estudio"
          acciones={
            <Button size="sm" onClick={() => { setShowModal(true); setResultado(null); setError('') }}>
              <Plus size={14}/> Agregar empresa
            </Button>
          }
        />
        <main className="flex-1 p-6">
          <div className="grid grid-cols-3 gap-4 mb-6">
            <MetricCard label="Empresas activas" value={empresas.length}
              icon={<Users size={18} className="text-emerald-600"/>} iconBg="bg-emerald-50"/>
            <MetricCard label="Docs subidos este mes" value={
                empresas.reduce((s, e) => s + (e._count?.documentos ?? 0), 0)
              }
              icon={<FileText size={18} className="text-blue-600"/>} iconBg="bg-blue-50"/>
            <MetricCard label="Cupos disponibles" value={`7 / 25`}
              sub="Plan Profesional" subColor="text-purple-600"
              icon={<TrendingUp size={18} className="text-purple-600"/>} iconBg="bg-purple-50"/>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
              <div className="relative flex-1 max-w-xs">
                <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"/>
                <input value={buscar} onChange={e => setBuscar(e.target.value)}
                  placeholder="Buscar por nombre o RUC..."
                  className="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472]"/>
              </div>
              <span className="text-xs text-gray-400 ml-auto">{filtradas.length} empresas</span>
            </div>
            {loading ? (
              <div className="p-8 text-center text-sm text-gray-400">Cargando clientes...</div>
            ) : filtradas.length === 0 ? (
              <div className="p-12 text-center">
                <Users size={32} className="text-gray-200 mx-auto mb-3"/>
                <p className="text-sm font-medium text-gray-500">No hay empresas aún</p>
                <p className="text-xs text-gray-400 mt-1">Agrega tu primer cliente con el botón de arriba</p>
              </div>
            ) : (
              <table className="w-full text-sm">
                <thead className="bg-gray-50 border-b border-gray-100">
                  <tr>
                    {['Empresa','RUC','Documentos','Estado','Acciones'].map(h => (
                      <th key={h} className="text-left px-4 py-3 text-xs font-medium text-gray-500">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {filtradas.map(emp => (
                    <tr key={emp.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-4 py-3">
                        <p className="font-medium text-gray-900">{emp.razon_social}</p>
                        <p className="text-xs text-gray-400">{emp.email_acceso}</p>
                      </td>
                      <td className="px-4 py-3 font-mono text-xs text-gray-500">{emp.ruc}</td>
                      <td className="px-4 py-3">
                        <Badge variant={emp._count?.documentos > 0 ? 'green' : 'amber'}>
                          {emp._count?.documentos ?? 0} docs
                        </Badge>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={emp.activo ? 'green' : 'red'}>
                          {emp.activo ? 'Activo' : 'Inactivo'}
                        </Badge>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <button className="flex items-center gap-1 text-xs text-[#0ea472] hover:underline font-medium">
                            <Upload size={12}/> Subir docs
                          </button>
                          <span className="text-gray-200">|</span>
                          <button className="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
                            <Eye size={12}/> Ver
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </main>
      </div>

      {showModal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            {resultado ? (
              <div>
                <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                  <span className="text-emerald-600 text-lg">✓</span>
                </div>
                <h3 className="font-semibold text-gray-900 mb-1">Empresa creada</h3>
                <p className="text-sm text-gray-500 mb-4">Envíale estas credenciales a tu cliente:</p>
                <div className="bg-gray-50 rounded-lg p-4 font-mono text-sm space-y-1.5 border border-gray-200">
                  <p><span className="text-gray-400 font-sans">Email:</span> {resultado.empresa.email_acceso}</p>
                  <p><span className="text-gray-400 font-sans">Clave temporal:</span> <strong className="text-gray-900">{resultado.password_temporal}</strong></p>
                  <p><span className="text-gray-400 font-sans">Web:</span> contadocs.pe/login</p>
                </div>
                <Button className="w-full mt-4" onClick={() => { setShowModal(false); setForm({ razon_social:'',ruc:'',email_acceso:'' }) }}>
                  Listo
                </Button>
              </div>
            ) : (
              <form onSubmit={crearEmpresa}>
                <h3 className="font-semibold text-gray-900 mb-1">Agregar empresa cliente</h3>
                <p className="text-xs text-gray-400 mb-4">Se creará un acceso automáticamente para este cliente.</p>
                {error && (
                  <div className="bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-3">
                    <p className="text-xs text-red-700">{error}</p>
                  </div>
                )}
                <div className="space-y-3">
                  {[
                    { label:'Razón social', key:'razon_social', placeholder:'Inversiones Quispe SAC' },
                    { label:'RUC', key:'ruc', placeholder:'20501234567' },
                    { label:'Email del cliente', key:'email_acceso', placeholder:'gerencia@empresa.com' },
                  ].map(f => (
                    <div key={f.key}>
                      <label className="text-xs font-medium text-gray-700 block mb-1">{f.label}</label>
                      <input value={(form as any)[f.key]}
                        onChange={e => setForm(p => ({ ...p, [f.key]: e.target.value }))}
                        placeholder={f.placeholder} required
                        className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472]"/>
                    </div>
                  ))}
                </div>
                <div className="flex gap-2 mt-5">
                  <Button type="button" variant="secondary" className="flex-1" onClick={() => setShowModal(false)}>Cancelar</Button>
                  <Button type="submit" className="flex-1" loading={saving}>Crear acceso</Button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
