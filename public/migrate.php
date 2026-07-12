<?php
require_once __DIR__ . '/../src/config.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Iniciando migración de registros antiguos a la nueva tabla system_logs...\n\n";

    // Migrar login_logs
    $stmt = $pdo->query("SELECT * FROM login_logs");
    $old_logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $countLogins = 0;
    foreach ($old_logins as $l) {
        // Verificar si ya existe
        $check = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE tipo='LOGIN_ERROR' AND usuario=? AND fecha=?");
        $check->execute([$l['usuario'], $l['fecha']]);
        if ($check->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO system_logs (tipo, usuario, ip, mensaje, fecha) VALUES ('LOGIN_ERROR', ?, ?, ?, ?)")
                ->execute([$l['usuario'], $l['ip'], $l['error'], $l['fecha']]);
            $countLogins++;
        }
    }
    echo "✅ Se migraron $countLogins errores de login antiguos.\n";

    // Migrar subidas
    $stmt = $pdo->query("SELECT * FROM subidas");
    $old_subidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $countSubidas = 0;
    foreach ($old_subidas as $s) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE tipo='UPLOAD_EXITO' AND usuario=? AND fecha=?");
        $check->execute([$s['usuario'], $s['creado']]);
        if ($check->fetchColumn() == 0) {
            $msg = "Subida exitosa: {$s['proyecto']} - Cap {$s['capitulo']} ({$s['etapa']})";
            $pdo->prepare("INSERT INTO system_logs (tipo, usuario, ip, mensaje, fecha) VALUES ('UPLOAD_EXITO', ?, '?', ?, ?)")
                ->execute([$s['usuario'], $msg, $s['creado']]);
            $countSubidas++;
        }
    }
    echo "✅ Se migraron $countSubidas subidas exitosas antiguas.\n";
    
    echo "\nMigración completada con éxito. Ya puedes ver todo en la página de Logs.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
