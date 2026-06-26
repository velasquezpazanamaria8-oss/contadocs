export type Rol = 'superadmin' | 'contador' | 'cliente'
export type Plan = 'basico' | 'profesional' | 'ilimitado'
export type EstadoCuenta = 'activo' | 'vencido' | 'suspendido'

export const LIMITES_PLAN = {
  basico: 10,
  profesional: 25,
  ilimitado: 999999,
}

export const PRECIO_PLAN: Record<Plan, number> = {
  basico: 49.90,
  profesional: 99.90,
  ilimitado: 200.00,
}
