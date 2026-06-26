import { NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { obtenerSession } from '@/lib/auth/session'

export async function GET(req: Request) {
  const session = await obtenerSession()
  if (!session || session.rol !== 'cliente')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const { searchParams } = new URL(req.url)
  const periodo = searchParams.get('periodo')
  const categoria_id = searchParams.get('categoria_id')
  const docs = await prisma.documento.findMany({
    where: {
      empresa_id: session.empresa_id,
      ...(periodo ? { periodo } : {}),
      ...(categoria_id ? { categoria_id } : {}),
    },
    include: {
      categoria: true,
      descargas: {
        where: { empresa_id: session.empresa_id },
        orderBy: { descargado_at: 'desc' },
        take: 1,
      }
    },
    orderBy: { created_at: 'desc' }
  })
  return NextResponse.json(docs)
}
