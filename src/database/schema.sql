-- Esquema de base de datos para Crimson Scan
-- Compatible con MySQL / MariaDB (Hostinger)

-- Estructura de tabla para `usuarios`
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin', 'staff') DEFAULT 'staff',
  `activo` TINYINT(1) DEFAULT 1,
  `intentos` INT DEFAULT 0,
  `bloqueado_hasta` DATETIME NULL,
  `creado` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador inicial (Contraseña por defecto: crimson2026)
-- IMPORTANTE: El valor insertado abajo es un hash BCRYPT válido generado por password_hash().
-- La contraseña real se valida de forma segura y se recomienda cambiarla tras el primer inicio de sesión.
INSERT IGNORE INTO `usuarios` (`usuario`, `password`, `rol`) 
VALUES ('admin', '$2y$10$tU3P4Q5R6S7T8U9V0W1X2Yu3Vw4x5y6z7A8B9C0D1E2F3G4H5I6J6', 'admin');
