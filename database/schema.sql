-- Esquema de base de datos para Crimson Scan
-- Compatible con MySQL / MariaDB (Hostinger)

-- Estructura de tabla para `usuarios`
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin', 'staff') DEFAULT 'staff',
  `activo` TINYINT(1) DEFAULT 1,
  `creado` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador inicial (Contraseña: crimson2026)
-- IMPORTANTE: Cambiar contraseña después del primer login
INSERT IGNORE INTO `usuarios` (`usuario`, `password`, `rol`) 
VALUES ('admin', 'crimson2026', 'admin');
-- Nota: El hash de arriba es un ejemplo, el sistema genera uno válido automáticamente si la tabla está vacía.
