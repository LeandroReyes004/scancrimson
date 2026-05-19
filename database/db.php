<?php
require_once __DIR__ . '/../config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $port = defined('DB_PORT') ? DB_PORT : '3306';
    $dsn  = 'mysql:host=' . DB_HOST . ';port=' . $port
          . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            usuario         VARCHAR(50) NOT NULL UNIQUE,
            password        VARCHAR(255) NOT NULL,
            rol             ENUM('admin','staff') DEFAULT 'staff',
            activo          TINYINT(1) DEFAULT 1,
            intentos        INT DEFAULT 0,
            bloqueado_hasta DATETIME NULL,
            creado          DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Asegurar que las columnas para bloqueo de fuerza bruta existan
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN intentos INT DEFAULT 0, ADD COLUMN bloqueado_hasta DATETIME NULL");
    } catch (PDOException $e) {
        // Ignorar si las columnas ya existen
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS proyectos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                nombre_upper VARCHAR(100) NOT NULL UNIQUE,
                estado VARCHAR(20) DEFAULT 'activo',
                carpeta_drive_id VARCHAR(100) NULL,
                creado DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS capitulos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                proyecto_id INT NOT NULL,
                numero FLOAT NOT NULL,
                estado_raw TINYINT(1) DEFAULT 0,
                estado_trad TINYINT(1) DEFAULT 0,
                estado_clean TINYINT(1) DEFAULT 0,
                estado_type TINYINT(1) DEFAULT 0,
                estado_proof TINYINT(1) DEFAULT 0,
                estado_general ENUM('Pendiente', 'En proceso', 'Retrasado', 'Publicado') DEFAULT 'Pendiente',
                UNIQUE KEY proj_cap (proyecto_id, numero)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS staff_discord (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discord_id VARCHAR(50) NOT NULL UNIQUE,
                usuario_form VARCHAR(100) NULL,
                nombre_display VARCHAR(100) NULL,
                rol VARCHAR(50) DEFAULT 'Staff',
                activo TINYINT(1) DEFAULT 1,
                puntos_mes INT DEFAULT 0,
                creado DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        try {
            $pdo->exec("ALTER TABLE staff_discord ADD COLUMN rol VARCHAR(50) DEFAULT 'Staff'");
        } catch (PDOException $e) { /* columna ya existe */ }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tareas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discord_id VARCHAR(50) NOT NULL,
                obra VARCHAR(100) NOT NULL,
                cap VARCHAR(50) NOT NULL,
                rol VARCHAR(50) NOT NULL,
                estado VARCHAR(50) DEFAULT 'activa',
                limite DATETIME NULL,
                creado DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (PDOException $e) {
        // Log error silently if user lacks privileges
    }

    // Seed: crear admin por defecto si la tabla está vacía
    $count = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('crimson2026', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, 'admin')")
            ->execute(['admin', $hash]);
    }

    return $pdo;
}
