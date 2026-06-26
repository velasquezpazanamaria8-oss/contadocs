-- ============================================================
-- ContaDocs — Script SQL para MySQL Hostinger
-- Ejecuta esto en hPanel > phpMyAdmin > tu base de datos
-- ============================================================

CREATE TABLE IF NOT EXISTS `estudios` (
  `id` VARCHAR(36) PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `ruc` VARCHAR(20) UNIQUE NOT NULL,
  `email_admin` VARCHAR(255) NOT NULL,
  `plan` ENUM('basico','profesional','ilimitado') NOT NULL DEFAULT 'basico',
  `estado` ENUM('activo','vencido','suspendido') NOT NULL DEFAULT 'activo',
  `vence_en` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` VARCHAR(36) PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('superadmin','contador','cliente') NOT NULL DEFAULT 'cliente',
  `nombre` VARCHAR(255),
  `primer_login` TINYINT(1) NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `estudio_id` VARCHAR(36),
  `empresa_id` VARCHAR(36),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`estudio_id`) REFERENCES `estudios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `empresas_cliente` (
  `id` VARCHAR(36) PRIMARY KEY,
  `estudio_id` VARCHAR(36) NOT NULL,
  `razon_social` VARCHAR(255) NOT NULL,
  `ruc` VARCHAR(20) NOT NULL,
  `email_acceso` VARCHAR(255) UNIQUE NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`estudio_id`) REFERENCES `estudios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `usuarios` ADD CONSTRAINT fk_empresa
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas_cliente`(`id`) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` VARCHAR(36) PRIMARY KEY,
  `estudio_id` VARCHAR(36) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `icono` VARCHAR(50) DEFAULT 'file',
  `color` VARCHAR(20) DEFAULT '#E6F1FB',
  `color_texto` VARCHAR(20) DEFAULT '#0C447C',
  `descripcion` VARCHAR(255),
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`estudio_id`) REFERENCES `estudios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `documentos` (
  `id` VARCHAR(36) PRIMARY KEY,
  `empresa_id` VARCHAR(36) NOT NULL,
  `categoria_id` VARCHAR(36) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `storage_path` VARCHAR(500) NOT NULL,
  `periodo` VARCHAR(10) NOT NULL,
  `tamanio` INT,
  `subido_por` VARCHAR(36) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas_cliente`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`),
  FOREIGN KEY (`subido_por`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `descargas_log` (
  `id` VARCHAR(36) PRIMARY KEY,
  `documento_id` VARCHAR(36) NOT NULL,
  `empresa_id` VARCHAR(36) NOT NULL,
  `descargado_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`documento_id`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas_cliente`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SUPERADMIN INICIAL
-- Email: admin@contadocs.pe
-- Clave: Admin2025#   (cámbiala en tu primer login)
-- ============================================================
INSERT IGNORE INTO `usuarios` (`id`, `email`, `password`, `rol`, `nombre`, `primer_login`, `activo`)
VALUES (
  UUID(),
  'admin@contadocs.pe',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'superadmin',
  'Administrador',
  0,
  1
);

-- ============================================================
-- CATEGORÍAS DE EJEMPLO (para nuevos estudios)
-- ============================================================
-- Nota: Las categorías reales las crea cada contador desde su panel
