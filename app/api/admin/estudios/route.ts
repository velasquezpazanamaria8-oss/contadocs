import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { obtenerSession } from '@/lib/auth/session'
import { hashPassword, generarPasswordTemporal } from '@/lib/auth/password'

export async function GET() {
  const session = await obtenerSession()
  if (!session || session.rol !== 'superadmin')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const estudios = await prisma.estudio.findMany({
    include: {
      _count: { select: { empresas: true } },
      usuarios: { where: { rol: 'contador' }, select: { id: true, email: true } }
    },
    orderBy: { created_at: 'desc' }
  })
  return NextResponse.json(estudios)
}

export async function POST(req: NextRequest) {
  const session = await obtenerSession()
  if (!session || session.rol !== 'superadmin')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const { nombre, ruc, email_admin, plan } = await req.json()
  if (!nombre || !ruc || !email_admin || !plan)
    return NextResponse.json({ error: 'Datos incompletos' }, { status: 400 })
  const passTemp = generarPasswordTemporal()
  const passHash = await hashPassword(passTemp)
  const vence = new Date()
  vence.setMonth(vence.getMonth() + 1)
  const estudio = await prisma.estudio.create({
    data: {
      nombre, ruc, email_admin, plan: plan as any, estado: 'activo', vence_en: vence,
      usuarios: {
        create: {
          email: email_admin.toLowerCase().trim(),
          password: passHash,
          rol: 'contador',
          primer_login: true,
        }
      }
    }
  })
  // Actualizar el estudio_id del usuario creado
  await prisma.usuario.updateMany({
    where: { email: email_admin.toLowerCase().trim(), estudio_id: null },
    data: { estudio_id: estudio.id }
  })
  return NextResponse.json({ estudio, password_temporal: passTemp }, { status: 201 })
}
