'use client'
import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { FileText, Mail, Lock, Eye, EyeOff } from 'lucide-react'

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPass, setShowPass] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError('')
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      })
      const data = await res.json()
      if (!res.ok) { setError(data.error ?? 'Credenciales incorrectas'); return }
      if (data.primer_login) { router.push('/cambiar-password'); return }
      const rutas: Record<string, string> = {
        superadmin: '/admin/dashboard',
        contador:   '/contador/clientes',
        cliente:    '/cliente/documentos',
      }
      router.push(rutas[data.rol] ?? '/')
    } catch {
      setError('Error de conexión. Intenta de nuevo.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
      <div className="w-full max-w-sm">

        {/* Logo */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#0ea472] shadow-lg mb-4">
            <FileText size={22} className="text-white"/>
          </div>
          <h1 className="text-xl font-bold text-gray-900">ContaDocs</h1>
          <p className="text-sm text-gray-500 mt-1">Accede a tus documentos contables</p>
        </div>

        {/* Card */}
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <form onSubmit={handleLogin} className="flex flex-col gap-4">

            {error && (
              <div className="bg-red-50 border border-red-200 rounded-lg px-3 py-2.5 flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"/>
                <p className="text-xs text-red-700">{error}</p>
              </div>
            )}

            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-gray-700">Correo electrónico</label>
              <div className="relative">
                <Mail size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"/>
                <input
                  type="email"
                  value={email}
                  onChange={e => setEmail(e.target.value)}
                  placeholder="tucorreo@email.com"
                  required
                  className="w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472] transition-all placeholder:text-gray-400"
                />
              </div>
            </div>

            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-gray-700">Contraseña</label>
              <div className="relative">
                <Lock size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"/>
                <input
                  type={showPass ? 'text' : 'password'}
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  placeholder="Tu contraseña"
                  required
                  className="w-full pl-9 pr-10 py-2.5 text-sm rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0ea472]/20 focus:border-[#0ea472] transition-all placeholder:text-gray-400"
                />
                <button type="button" onClick={() => setShowPass(!showPass)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  {showPass ? <EyeOff size={15}/> : <Eye size={15}/>}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-2.5 rounded-lg bg-[#0ea472] hover:bg-[#0b8a5f] text-white text-sm font-medium transition-colors disabled:opacity-60 flex items-center justify-center gap-2 mt-1"
            >
              {loading && (
                <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              )}
              {loading ? 'Ingresando...' : 'Ingresar'}
            </button>
          </form>
        </div>

        <p className="text-center text-xs text-gray-400 mt-6">
          ¿Problemas para ingresar? Contacta a tu contador.
        </p>
      </div>
    </div>
  )
}
