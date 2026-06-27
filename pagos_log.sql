-- Ejecuta esto en phpMyAdmin > SQL
-- Crea la tabla de historial de pagos

CREATE TABLE IF NOT EXISTS `pagos_log` (
  `id`             VARCHAR(36)    PRIMARY KEY,
  `estudio_id`     VARCHAR(36)    NOT NULL,
  `monto`          DECIMAL(10,2)  NOT NULL,
  `metodo`         VARCHAR(50)    NOT NULL,
  `referencia`     VARCHAR(100)   DEFAULT NULL,
  `dias_activados` INT            NOT NULL DEFAULT 30,
  `vence_hasta`    DATETIME       NOT NULL,
  `created_at`     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_estudio` (`estudio_id`),
  INDEX `idx_fecha`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
