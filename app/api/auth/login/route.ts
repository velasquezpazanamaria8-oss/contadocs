import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { verificarPassword } from '@/lib/auth/password'
import { crearSession } from '@/lib/auth/session'

export async function POST(req: NextRequest) {
  try {
    const { email, password } = await req.json()
    if (!email || !password)
      return NextResponse.json({ error: 'Email y contraseña requeridos' }, { status: 400 })

    const usuario = await prisma.usuario.findUnique({
      where: { email: email.toLowerCase().trim() },
      include: { estudio: { select: { plan: true, estado: true } } }
    })

    if (!usuario || !usuario.activo)
      return NextResponse.json({ error: 'Credenciales incorrectas' }, { status: 401 })

    const ok = await verificarPassword(password, usuario.password)
    if (!ok)
      return NextResponse.json({ error: 'Credenciales incorrectas' }, { status: 401 })

    if (usuario.estudio && usuario.estudio.estado !== 'activo')
      return NextResponse.json({ error: 'Acceso suspendido. Contacta a tu contador.' }, { status: 403 })

    await crearSession({
      id: usuario.id,
      email: usuario.email,
      rol: usuario.rol,
      estudio_id: usuario.estudio_id ?? undefined,
      empresa_id: usuario.empresa_id ?? undefined,
      primer_login: usuario.primer_login,
    })

    return NextResponse.json({ ok: true, rol: usuario.rol, primer_login: usuario.primer_login })
  } catch (err) {
    console.error(err)
    return NextResponse.json({ error: 'Error del servidor' }, { status: 500 })
  }
}
