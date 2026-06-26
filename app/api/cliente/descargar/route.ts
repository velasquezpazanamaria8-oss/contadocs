import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { obtenerSession } from '@/lib/auth/session'
import { readFile } from 'fs/promises'
import path from 'path'

export async function GET(req: NextRequest) {
  const session = await obtenerSession()
  if (!session || session.rol !== 'cliente')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const { searchParams } = new URL(req.url)
  const doc_id = searchParams.get('id')
  if (!doc_id) return NextResponse.json({ error: 'ID requerido' }, { status: 400 })

  const doc = await prisma.documento.findFirst({
    where: { id: doc_id, empresa_id: session.empresa_id }
  })
  if (!doc) return NextResponse.json({ error: 'Documento no encontrado' }, { status: 404 })

  // Registrar descarga
  await prisma.descargaLog.create({
    data: { documento_id: doc.id, empresa_id: session.empresa_id! }
  })

  // Leer y servir el archivo
  try {
    const filePath = path.join(process.cwd(), 'public', doc.storage_path)
    const buffer = await readFile(filePath)
    const ext = path.extname(doc.storage_path).toLowerCase()
    const contentType = ext === '.pdf' ? 'application/pdf'
      : ext === '.png' ? 'image/png'
      : ext === '.jpg' || ext === '.jpeg' ? 'image/jpeg'
      : 'application/octet-stream'
    return new NextResponse(buffer, {
      headers: {
        'Content-Type': contentType,
        'Content-Disposition': `attachment; filename="${encodeURIComponent(doc.nombre)}"`,
        'Cache-Control': 'no-store',
      }
    })
  } catch {
    return NextResponse.json({ error: 'Archivo no disponible' }, { status: 404 })
  }
}
