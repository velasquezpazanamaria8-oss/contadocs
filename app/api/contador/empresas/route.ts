import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { obtenerSession } from '@/lib/auth/session'
import { hashPassword, generarPasswordTemporal } from '@/lib/auth/password'
import { LIMITES_PLAN } from '@/types'

export async function GET() {
  const session = await obtenerSession()
  if (!session || session.rol !== 'contador')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const empresas = await prisma.empresaCliente.findMany({
    where: { estudio_id: session.estudio_id },
    include: { _count: { select: { documentos: true } } },
    orderBy: { razon_social: 'asc' }
  })
  return NextResponse.json(empresas)
}

export async function POST(req: NextRequest) {
  const session = await obtenerSession()
  if (!session || session.rol !== 'contador')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const { razon_social, ruc, email_acceso } = await req.json()
  if (!razon_social || !ruc || !email_acceso)
    return NextResponse.json({ error: 'Datos incompletos' }, { status: 400 })
  const estudio = await prisma.estudio.findUnique({
    where: { id: session.estudio_id },
    include: { _count: { select: { empresas: true } } }
  })
  if (!estudio) return NextResponse.json({ error: 'Estudio no encontrado' }, { status: 404 })
  const limite = LIMITES_PLAN[estudio.plan as keyof typeof LIMITES_PLAN]
  if (estudio._count.empresas >= limite)
    return NextResponse.json({ error: 'Límite de empresas alcanzado. Actualiza tu plan.' }, { status: 403 })
  const passTemp = generarPasswordTemporal()
  const passHash = await hashPassword(passTemp)
  const empresa = await prisma.empresaCliente.create({
    data: {
      estudio_id: session.estudio_id!,
      razon_social, ruc,
      email_acceso: email_acceso.toLowerCase().trim(),
      usuarios: {
        create: {
          email: email_acceso.toLowerCase().trim(),
          password: passHash,
          rol: 'cliente',
          estudio_id: session.estudio_id,
          primer_login: true,
        }
      }
    }
  })
  return NextResponse.json({ empresa, password_temporal: passTemp }, { status: 201 })
}
