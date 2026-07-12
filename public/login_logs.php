<?php
require_once __DIR__ . '/../src/auth.php';

$user = auth_get_user();
if (!$user || $user['rol'] !== 'admin') {
    header('Location: login.php'); exit;
}

$logFile = __DIR__ . '/../src/login_errors.log';
$logs = file_exists($logFile) ? file_get_contents($logFile) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logs de Login · Crimson Scan</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0a0a0e; --surface: rgba(255,255,255,.04); --border: rgba(255,255,255,.08);
    --text: #f0f0f4; --muted: #6e6e82; --muted2: #9898b0;
    --red: #dc2020; --red-bright: #ff3535;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; padding: 1.5rem; }
  .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
  .top-bar .title { font-size: 1.1rem; font-weight: 700; text-transform: uppercase; }
  .top-bar .title span { color: var(--red-bright); }
  .back-link { color: var(--muted); font-size: .82rem; text-decoration: none; }
  .back-link:hover { color: var(--text); }
  .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
  pre {
    background: #000;
    color: #0f0;
    padding: 1rem;
    border-radius: 8px;
    overflow-x: auto;
    font-family: monospace;
    font-size: 0.85rem;
    line-height: 1.5;
    white-space: pre-wrap;
    min-height: 200px;
  }
  .empty { color: var(--muted); font-style: italic; }
</style>
</head>
<body>

<div class="top-bar">
  <div class="title">CRIMSON <span>LOGS DE LOGIN</span></div>
  <a href="admin.php" class="back-link">← Volver al panel</a>
</div>

<div class="panel">
  <?php if ($logs): ?>
    <pre><?php echo htmlspecialchars($logs); ?></pre>
  <?php else: ?>
    <div class="empty">No hay errores de inicio de sesión registrados aún.</div>
  <?php endif; ?>
</div>

</body>
</html>
