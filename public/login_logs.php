<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/database/db.php';

$user = auth_get_user();
if (!$user || $user['rol'] !== 'admin') {
    header('Location: login.php'); exit;
}

$logs = [];
try {
    $db = getDB();
    // Obtener los logs combinados del sistema (logins, subidas, errores)
    $stmt = $db->query("SELECT * FROM system_logs ORDER BY fecha DESC LIMIT 200");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignorar si la tabla no existe aún
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logs de Login · Crimson Scan</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<title>Crimson - Logs del Sistema</title>
<style>
  :root {
    --bg: #0a0a0e; --surface: rgba(255,255,255,.04); --border: rgba(255,255,255,.08);
    --text: #f0f0f4; --muted: #6e6e82; --muted2: #9898b0;
    --red: #dc2020; --red-bright: #ff3535; --fg: #f0f0f4;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--fg); font-family: 'Inter', sans-serif; margin: 0; }
  .header { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
  .logo { font-size: 1.25rem; font-weight: 800; letter-spacing: 0.1em; color: var(--fg); }
  .logo span { color: var(--red-bright); }
  .container { padding: 2rem; max-width: 1200px; margin: 0 auto; }
  table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: 8px; overflow: hidden; }
  th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
  th { background: rgba(255,255,255,0.02); font-weight: 600; color: var(--muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
  tr:hover { background: rgba(255,255,255,0.02); }
  .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
  
  .badge.LOGIN_ERROR { background: rgba(239,68,68,0.2); color: #f87171; }
  .badge.LOGIN_EXITO { background: rgba(34,197,94,0.2); color: #4ade80; }
  .badge.UPLOAD_ERROR { background: rgba(234,179,8,0.2); color: #facc15; }
  .badge.UPLOAD_EXITO { background: rgba(59,130,246,0.2); color: #60a5fa; }

  .btn-back { color: var(--muted); text-decoration: none; font-size: 0.875rem; }
  .btn-back:hover { color: var(--fg); }
  .empty-state { text-align: center; padding: 3rem; color: var(--muted); background: var(--surface); border-radius: 8px; }
</style>
</head>
<body>

<div class="header">
    <div class="logo">CRIMSON <span>LOGS DEL SISTEMA</span></div>
    <a href="admin.php" class="btn-back">← Volver al panel</a>
</div>

<div class="container">
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <em>No hay registros del sistema aún.</em>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Mensaje / Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="color:var(--muted); font-size:0.875rem; white-space:nowrap;">
                            <?= htmlspecialchars($log['fecha']) ?>
                        </td>
                        <td>
                            <span class="badge <?= htmlspecialchars($log['tipo'] ?? 'LOGIN_ERROR') ?>">
                                <?= htmlspecialchars($log['tipo'] ?? 'ERROR') ?>
                            </span>
                        </td>
                        <td style="font-weight:600;">
                            <?= htmlspecialchars($log['usuario']) ?>
                        </td>
                        <td style="color:var(--muted); font-size:0.875rem;">
                            <?= htmlspecialchars($log['ip']) ?>
                        </td>
                        <td style="color: #cbd5e1;">
                            <?= htmlspecialchars($log['mensaje'] ?? $log['error'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
