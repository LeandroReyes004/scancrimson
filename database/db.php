<?php
require_once __DIR__ . '/../config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
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

    // Asegurar que las columnas para bloqueo de fuerza bruta existan (en caso de que la tabla ya existiera antes)
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN intentos INT DEFAULT 0, ADD COLUMN bloqueado_hasta DATETIME NULL");
    } catch (PDOException $e) {
        // Ignorar si las columnas ya existen
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
