import { NextResponse } from 'next/server'
import { cerrarSession } from '@/lib/auth/session'

export async function POST() {
  await cerrarSession()
  return NextResponse.redirect(new URL('/login', process.env.NEXT_PUBLIC_SITE_URL ?? 'http://localhost:3000'))
}
