-- ============================================================
-- ContaDocs — Actualización BD (versión Hostinger)
-- Ejecuta en phpMyAdmin > SQL
-- ============================================================

-- 1. Crear tabla planes
CREATE TABLE IF NOT EXISTS `planes` (
  `id` VARCHAR(36) PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `limite_empresas` INT NOT NULL DEFAULT 10,
  `dias_acceso` INT NOT NULL DEFAULT 30,
  `descripcion` VARCHAR(255),
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insertar planes base
INSERT IGNORE INTO `planes` (`id`,`nombre`,`precio`,`limite_empresas`,`dias_acceso`,`descripcion`) VALUES
(UUID(),'Básico',49.90,10,30,'Ideal para contadores independientes'),
(UUID(),'Profesional',99.90,25,30,'Para estudios en crecimiento'),
(UUID(),'Ilimitado',200.00,999999,30,'Sin límites para grandes estudios');

-- 3. Agregar columna plan_id a estudios
ALTER TABLE `estudios` ADD COLUMN IF NOT EXISTS `plan_id` VARCHAR(36) AFTER `plan`;

-- 4. Migrar datos existentes
UPDATE `estudios` e
SET e.plan_id = (
  SELECT p.id FROM planes p
  WHERE (e.plan='basico' AND p.nombre='Básico')
     OR (e.plan='profesional' AND p.nombre='Profesional')
     OR (e.plan='ilimitado' AND p.nombre='Ilimitado')
  LIMIT 1
)
WHERE e.plan_id IS NULL;

-- ¡Listo! La hora Lima se maneja desde PHP, no MySQL.
