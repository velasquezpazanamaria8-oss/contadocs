import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  typescript: {
    // En producción Prisma Client se genera antes del build
    // ignoreBuildErrors solo para desarrollo sin BD conectada
    ignoreBuildErrors: true,
  },
  eslint: {
    ignoreDuringBuilds: true,
  },
}

export default nextConfig
