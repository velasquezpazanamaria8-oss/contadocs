'use client'
import { useEffect, useState } from 'react'
import { Sidebar } from '@/components/layout/Sidebar'
import { Topbar } from '@/components/layout/Topbar'
import { MetricCard } from '@/components/layout/MetricCard'
import { Badge } from '@/components/ui/Badge'
import { Building2, Users, DollarSign, AlertCircle, Plus } from 'lucide-react'
import { Button } from '@/components/ui/Button'

const PRECIO: Record<string,number> = { basico:49.90, profesional:99.90, ilimitado:200 }
const PLAN_LABEL: Record<string,string> = { basico:'Básico', profesional:'Profesional', ilimitado:'Ilimitado' }

export default function AdminDashboard() {
  const [estudios, setEstudios] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [form, setForm] = useState({ nombre:'', ruc:'', email_admin:'', plan:'basico' })
  const [saving, setSaving] = useState(false)
  const [resultado, setResultado] = useState<any>(null)

  useEffect(() => { cargar() }, [])

  async function cargar() {
    setLoading(true)
    const r = await fetch('/api/admin/estudios')
    setEstudios(await r.json())
    setLoading(false)
  }

  async function crearEstudio(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    const r = await fetch('/api/admin/estudios', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form)
    })
    const data = await r.json()
    if (r.ok) { setResultado(data); cargar() }
    setSaving(false)
  }

  const activos = estudios.filter(e => e.estado === 'activo').length
  const vencidos = estudios.filter(e => e.estado === 'vencido').length
  const ingresos = estudios
    .filter(e => e.estado === 'activo')
    .reduce((sum, e) => sum + (PRECIO[e.plan] ?? 0), 0)

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar rol="superadmin" nombre="Administrador"/>
      <div className="flex-1 flex flex-col">
        <Topbar
          titulo="Dashboard"
          subtitulo="Resumen general del sistema"
          acciones={
            <Button size="sm" onClick={() => { setShowModal(true); setResultado(null) }}>
              <Plus size={14}/> Nuevo estudio
            </Button>
          }
        />
        <main className="flex-1 p-6">
          <div className="grid grid-cols-4 gap-4 mb-6">
            <MetricCard label="Estudios activos" value={activos} sub="+3 este mes" subColor="text-emerald-600" icon={<Building2 size={18} className="text-emerald-600"/>} iconBg="bg-emerald-50"/>
            <MetricCard label="Ingreso mensual" value={`S/ ${ingresos.toFixed(2)}`} icon={<DollarSign size={18} className="text-blue-600"/>} iconBg="bg-blue-50"/>
            <MetricCard label="Total estudios" value={estudios.length} icon={<Users size={18} className="text-purple-600"/>} iconBg="bg-purple-50"/>
            <MetricCard label="Vencidos" value={vencidos} sub={vencidos > 0 ? "Requieren cobro" : "Todo al día"} subColor={vencidos > 0 ? "text-red-500" : "text-emerald-500"} icon={<AlertCircle size={18} className={vencidos > 0 ? "text-red-500" : "text-gray-400"}/>} iconBg={vencidos > 0 ? "bg-red-50" : "bg-gray-100"}/>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="text-sm font-semibold text-gray-900">Estudios registrados</h3>
              <span className="text-xs text-gray-400">{estudios.length} total</span>
            </div>
            {loading ? (
              <div className="p-8 text-center text-sm text-gray-400">Cargando...</div>
            ) : (
              <table className="w-full text-sm">
                <thead className="bg-gray-50 border-b border-gray-100">
                  <tr>
                    {['Estudio','RUC','Plan','Empresas','Vence','Estado',''].map(h => (
                      <th key={h} className="text-left px-4 py-3 text-xs font-medium text-gray-500">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {estudios.map(e => (
                    <tr key={e.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-4 py-3 font-medium text-gray-900">{e.nombre}</td>
                      <td className="px-4 py-3 text-gray-500 font-mono text-xs">{e.ruc}</td>
                      <td className="px-4 py-3">
                        <Badge variant={e.plan==='basico'?'gray':e.plan==='profesional'?'blue':'purple'}>
                          {PLAN_LABEL[e.plan]}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 text-gray-600">{e._count?.empresas ?? 0}</td>
                      <td className="px-4 py-3 text-gray-500 text-xs">
                        {e.vence_en ? new Date(e.vence_en).toLocaleDateString('es-PE') : '—'}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={e.estado==='activo'?'green':e.estado==='vencido'?'red':'amber'}>
                          {e.estado}
                        </Badge>
                      </td>
                      <td className="px-4 py-3">
                        <button className="text-xs text-blue-600 hover:underline">Editar</button>
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
                <h3 className="font-semibold text-gray-900 mb-1">Estudio creado</h3>
                <p className="text-sm text-gray-500 mb-4">Comparte estas credenciales por WhatsApp:</p>
                <div className="bg-gray-50 rounded-lg p-4 font-mono text-sm space-y-1 border border-gray-200">
                  <p><span className="text-gray-500">Email:</span> {resultado.estudio.email_admin}</p>
                  <p><span className="text-gray-500">Clave:</span> <strong>{resultado.password_temporal}</strong></p>
                  <p><span className="text-gray-500">Web:</span> {process.env.NEXT_PUBLIC_SITE_URL ?? 'contadocs.com'}/login</p>
                </div>
                <Button className="w-full mt-4" onClick={() => { setShowModal(false); setForm({ nombre:'',ruc:'',email_admin:'',plan:'basico' }) }}>
                  Cerrar
                </Button>
              </div>
            ) : (
              <form onSubmit={crearEstudio}>
                <h3 className="font-semibold text-gray-900 mb-4">Nuevo estudio contable</h3>
                <div className="space-y-3">
                  {[
                    { label:'Nombre del estudio', key:'nombre', placeholder:'Estudio Rodríguez y Asoc.' },
                    { label:'RUC', key:'ruc', placeholder:'20512345678' },
                    { label:'Email del contador', key:'email_admin', placeholder:'contador@email.com' },
                  ].map(f => (
                    <div key={f.key}>
                      <label className="text-xs font-medium text-gray-700 block mb-1">{f.label}</label>
                      <input value={(form as any)[f.key]} onChange={e => setForm(p => ({ ...p, [f.key]: e.target.value }))}
                        placeholder={f.placeholder} required
                        className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472]"/>
                    </div>
                  ))}
                  <div>
                    <label className="text-xs font-medium text-gray-700 block mb-1">Plan</label>
                    <select value={form.plan} onChange={e => setForm(p => ({ ...p, plan: e.target.value }))}
                      className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472]">
                      <option value="basico">Básico — S/49.90/mes (hasta 10 empresas)</option>
                      <option value="profesional">Profesional — S/99.90/mes (hasta 25 empresas)</option>
                      <option value="ilimitado">Ilimitado — S/200/mes (sin límite)</option>
                    </select>
                  </div>
                </div>
                <div className="flex gap-2 mt-5">
                  <Button type="button" variant="secondary" className="flex-1" onClick={() => setShowModal(false)}>Cancelar</Button>
                  <Button type="submit" className="flex-1" loading={saving}>Crear y activar</Button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
