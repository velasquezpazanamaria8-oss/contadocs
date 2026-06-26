import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/prisma'
import { obtenerSession } from '@/lib/auth/session'
import { writeFile, mkdir } from 'fs/promises'
import path from 'path'

export async function POST(req: NextRequest) {
  const session = await obtenerSession()
  if (!session || session.rol !== 'contador')
    return NextResponse.json({ error: 'No autorizado' }, { status: 401 })
  const form = await req.formData()
  const archivo = form.get('archivo') as File
  const empresa_id = form.get('empresa_id') as string
  const categoria_id = form.get('categoria_id') as string
  const periodo = form.get('periodo') as string
  const nombre = form.get('nombre') as string
  if (!archivo || !empresa_id || !categoria_id || !periodo)
    return NextResponse.json({ error: 'Datos incompletos' }, { status: 400 })
  const empresa = await prisma.empresaCliente.findFirst({
    where: { id: empresa_id, estudio_id: session.estudio_id }
  })
  if (!empresa) return NextResponse.json({ error: 'Empresa no encontrada' }, { status: 404 })
  const buffer = Buffer.from(await archivo.arrayBuffer())
  const carpeta = path.join(process.cwd(), 'public', 'uploads', session.estudio_id!, empresa_id, periodo)
  await mkdir(carpeta, { recursive: true })
  const nombreArchivo = `${Date.now()}_${archivo.name.replace(/[^a-zA-Z0-9._-]/g, '_')}`
  await writeFile(path.join(carpeta, nombreArchivo), buffer)
  const storagePath = `/uploads/${session.estudio_id}/${empresa_id}/${periodo}/${nombreArchivo}`
  const doc = await prisma.documento.create({
    data: {
      empresa_id, categoria_id,
      nombre: nombre || archivo.name,
      storage_path: storagePath,
      periodo, tamanio: archivo.size,
      subido_por: session.id,
    }
  })
  return NextResponse.json(doc, { status: 201 })
}
