<?php
require_once __DIR__ . '/../src/config.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando configuración de Base de Datos...\n\n";

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    echo "Conectando a: $dsn (Usuario: " . DB_USER . ")\n";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión exitosa a la base de datos.\n\n";

    $tables = [
        "subidas" => "
            CREATE TABLE IF NOT EXISTS subidas (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                proyecto   VARCHAR(100) NOT NULL,
                capitulo   VARCHAR(20)  NOT NULL,
                etapa      VARCHAR(60)  NOT NULL,
                archivo    VARCHAR(255) NOT NULL,
                usuario    VARCHAR(100) NOT NULL DEFAULT '',
                estado     VARCHAR(20)  NOT NULL DEFAULT 'Activo',
                creado     DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        "login_logs" => "
            CREATE TABLE IF NOT EXISTS login_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario VARCHAR(100) NOT NULL,
                ip VARCHAR(50) NOT NULL,
                error TEXT NOT NULL,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        "system_logs" => "
            CREATE TABLE IF NOT EXISTS system_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(50) NOT NULL,
                usuario VARCHAR(100) NOT NULL,
                ip VARCHAR(50) NOT NULL,
                mensaje TEXT NOT NULL,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];

    foreach ($tables as $name => $sql) {
        echo "Creando tabla '$name'...\n";
        try {
            $pdo->exec($sql);
            echo "✅ Tabla '$name' creada o ya existía.\n";
        } catch (PDOException $e) {
            echo "❌ ERROR creando tabla '$name': " . $e->getMessage() . "\n";
        }
    }

    echo "\nConfiguración completada. Si ves errores arriba, significa que el usuario de la base de datos no tiene permisos para crear tablas o hay un problema de sintaxis.";

} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN A LA BASE DE DATOS: " . $e->getMessage() . "\n";
}
?>
