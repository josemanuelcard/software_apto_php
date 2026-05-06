-- Migración para agregar tabla de Tarifas de Manillas
-- Ejecutar esta migración para crear la tabla y datos iniciales

-- ==========================================================
-- TABLA: manillas_tarifas (precios de manillas por cantidad)
-- ==========================================================
CREATE TABLE IF NOT EXISTS manillas_tarifas (
  id_tarifa INT AUTO_INCREMENT PRIMARY KEY,
  personas_desde INT NOT NULL,
  personas_hasta INT,
  precio DECIMAL(10,2) NOT NULL,
  activo BOOLEAN DEFAULT TRUE,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_rango (personas_desde, personas_hasta)
);

-- Datos iniciales (ejemplo)
INSERT INTO manillas_tarifas (personas_desde, personas_hasta, precio, activo)
VALUES
  (1, 7, 70000, TRUE),
  (8, NULL, 90000, TRUE)
ON DUPLICATE KEY UPDATE
  precio = VALUES(precio),
  actualizado_en = CURRENT_TIMESTAMP;

