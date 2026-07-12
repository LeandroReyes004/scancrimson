<?php
require_once __DIR__ . '/../src/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check users
    $stmt = $pdo->query("SELECT id, usuario, rol FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "--- USUARIOS EN LA BASE DE DATOS ---\n";
    if ($usuarios) {
        foreach ($usuarios as $u) {
            echo "ID: {$u['id']} | Usuario: {$u['usuario']} | Rol: {$u['rol']}\n";
        }
    } else {
        echo "NO HAY USUARIOS (Tabla vacía o no creada)\n";
    }

    echo "\n--- ERRORES DE LOGIN (Últimos 10) ---\n";
    $stmt = $pdo->query("SELECT * FROM login_logs ORDER BY fecha DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($logs) {
        foreach ($logs as $l) {
            echo "Fecha: {$l['fecha']} | IP: {$l['ip']} | Intento: {$l['usuario']} | Error: {$l['error']}\n";
        }
    } else {
        echo "No hay errores registrados.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
