import { NextRequest, NextResponse } from 'next/server'
import { jwtVerify } from 'jose'

const SECRET = new TextEncoder().encode(
  process.env.JWT_SECRET ?? 'contadocs-secret-key-cambiar-en-produccion'
)

const RUTAS_PUBLICAS = ['/login', '/api/auth/login']
const RUTAS_ROL: Record<string, string[]> = {
  superadmin: ['/admin'],
  contador:   ['/contador'],
  cliente:    ['/cliente'],
}

export async function middleware(req: NextRequest) {
  const { pathname } = req.nextUrl
  if (RUTAS_PUBLICAS.some(r => pathname.startsWith(r))) return NextResponse.next()
  if (pathname === '/') return NextResponse.redirect(new URL('/login', req.url))

  const token = req.cookies.get('session')?.value
  if (!token) return NextResponse.redirect(new URL('/login', req.url))

  try {
    const { payload } = await jwtVerify(token, SECRET)
    const rol = payload.rol as string
    const rutas = RUTAS_ROL[rol] ?? []
    const tieneAcceso = rutas.some(r => pathname.startsWith(r)) || pathname.startsWith('/api/')
    if (!tieneAcceso) return NextResponse.redirect(new URL('/login', req.url))
    return NextResponse.next()
  } catch {
    return NextResponse.redirect(new URL('/login', req.url))
  }
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico|uploads).*)'],
}
