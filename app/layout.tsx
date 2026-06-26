import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'ContaDocs — Portal de documentos contables',
  description: 'Accede y descarga tus documentos contables de forma rápida y segura.',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es">
      <body style={{ fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" }}>
        {children}
      </body>
    </html>
  )
}
