import { cookies } from 'next/headers'
import { SignJWT, jwtVerify, JWTPayload } from 'jose'

const SECRET = new TextEncoder().encode(
  process.env.JWT_SECRET ?? 'contadocs-secret-key-cambiar-en-produccion'
)

export interface SessionPayload extends JWTPayload {
  id: string
  email: string
  rol: string
  estudio_id?: string
  empresa_id?: string
  primer_login: boolean
}

export async function crearSession(payload: SessionPayload) {
  const token = await new SignJWT(payload as JWTPayload)
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime('7d')
    .sign(SECRET)

  const cookieStore = await cookies()
  cookieStore.set('session', token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 7,
    path: '/',
  })
}

export async function obtenerSession(): Promise<SessionPayload | null> {
  try {
    const cookieStore = await cookies()
    const token = cookieStore.get('session')?.value
    if (!token) return null
    const { payload } = await jwtVerify(token, SECRET)
    return payload as unknown as SessionPayload
  } catch {
    return null
  }
}

export async function cerrarSession() {
  const cookieStore = await cookies()
  cookieStore.delete('session')
}
