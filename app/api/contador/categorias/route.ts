import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { obtenerSession } from '@/lib/auth/session'

export async function GET() {
  const session = await obtenerSession()
  if (!session || session.rol !== 'contador')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const cats = await prisma.categoria.findMany({
    where: { estudio_id: session.estudio_id },
    include: { _count: { select: { documentos: true } } },
    orderBy: [{ orden: 'asc' }, { nombre: 'asc' }]
  })
  return NextResponse.json(cats)
}

export async function POST(req: NextRequest) {
  const session = await obtenerSession()
  if (!session || session.rol !== 'contador')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const { nombre, icono, color, color_texto, descripcion } = await req.json()
  if (!nombre) return NextResponse.json({ error: 'Nombre requerido' }, { status: 400 })
  const max = await prisma.categoria.aggregate({
    where: { estudio_id: session.estudio_id }, _max: { orden: true }
  })
  const cat = await prisma.categoria.create({
    data: {
      estudio_id: session.estudio_id!,
      nombre, icono: icono ?? 'file',
      color: color ?? '#E6F1FB',
      color_texto: color_texto ?? '#0C447C',
      descripcion,
      orden: (max._max.orden ?? 0) + 1,
    }
  })
  return NextResponse.json(cat, { status: 201 })
}
