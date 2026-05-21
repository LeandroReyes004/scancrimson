<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

$user = auth_get_user();
if (!$user || $user['rol'] !== 'admin') {
    header('Location: login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuración · Crimson Scan</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0a0a0e; --surface: rgba(255,255,255,.04); --border: rgba(255,255,255,.08);
    --text: #f0f0f4; --muted: #6e6e82; --muted2: #9898b0;
    --red: #dc2020; --red-bright: #ff3535; --green: #10b981;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; padding: 1.5rem; }
  h2 { font-size: 1rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--muted2); margin-bottom: 1rem; }
  h2 span { color: var(--red-bright); }
  .section { margin-bottom: 2rem; }
  .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
  .field { margin-bottom: 1.2rem; }
  .field label { display: block; font-size: .72rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: .4rem; }
  .field input, .field select {
    width: 100%; background: rgba(255,255,255,.06); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); padding: .65rem 1rem; font-size: .88rem;
    font-family: inherit; outline: none; transition: border-color .15s;
  }
  .field input:focus, .field select:focus { border-color: var(--red); }
  .field .hint { font-size: .75rem; color: var(--muted); margin-top: .35rem; }
  .btn { background: var(--red); border: none; border-radius: 8px; color: #fff; cursor: pointer; font-family: inherit; font-size: .85rem; font-weight: 600; padding: .6rem 1.4rem; transition: background .15s; }
  .btn:hover { background: #b81a1a; }
  .btn-ghost { background: rgba(255,255,255,.06); border: 1px solid var(--border); color: var(--muted2); }
  .btn-ghost:hover { background: rgba(255,255,255,.1); color: var(--text); }
  .row-btns { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
  .badge-ok  { color: var(--green); font-size: .8rem; }
  .badge-err { color: var(--red-bright); font-size: .8rem; }
  .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
  .top-bar .title { font-size: 1.1rem; font-weight: 700; }
  .top-bar .title span { color: var(--red-bright); }
  .back-link { color: var(--muted); font-size: .82rem; text-decoration: none; }
  .back-link:hover { color: var(--text); }
  .permisos-table { width: 100%; border-collapse: collapse; }
  .permisos-table th { padding: .5rem .75rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .1em; color: var(--muted); border-bottom: 1px solid var(--border); text-align: left; }
  .permisos-table td { padding: .65rem .75rem; border-bottom: 1px solid var(--border); }
  .permisos-table tr:last-child td { border-bottom: none; }
  .permisos-table select { background: rgba(255,255,255,.06); border: 1px solid var(--border); border-radius: 6px; color: var(--text); padding: 3px 8px; font-size: .82rem; cursor: pointer; }
  .toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 999; display: flex; flex-direction: column; gap: .5rem; }
  .toast { background: #1e1e2e; border: 1px solid var(--border); border-radius: 8px; padding: .6rem 1rem; font-size: .85rem; animation: slideIn .2s ease; }
  .toast.ok  { border-color: var(--green); }
  .toast.err { border-color: var(--red); }
  @keyframes slideIn { from { transform: translateX(20px); opacity: 0; } }
</style>
</head>
<body>

<div class="top-bar">
  <div class="title">CRIMSON <span>CONFIG</span></div>
  <a href="admin.php" class="back-link">← Volver al panel</a>
</div>

<!-- Notificaciones Discord -->
<div class="section">
  <h2>Notificaciones <span>Discord</span></h2>
  <div class="panel">
    <div class="field">
      <label>Webhook para subidas de archivos</label>
      <input type="url" id="webhook-subidas" placeholder="https://discord.com/api/webhooks/...">
      <div class="hint">Se notificará a este canal cada vez que un staff suba un archivo. Crea el webhook en Discord: Canal → Editar → Integraciones → Webhooks.</div>
    </div>
    <div class="row-btns">
      <button class="btn" onclick="guardarWebhook()">Guardar</button>
      <button class="btn btn-ghost" onclick="testWebhook()">Probar webhook</button>
      <span id="webhook-status"></span>
    </div>
  </div>
</div>

<!-- Permisos de comandos del bot -->
<div class="section">
  <h2>Permisos de <span>Comandos Bot</span></h2>
  <div class="panel">
    <table class="permisos-table" id="permisos-table">
      <thead>
        <tr><th>Comando</th><th>Descripción</th><th>¿Quién puede usarlo?</th></tr>
      </thead>
      <tbody id="permisos-body">
        <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:1.5rem">Cargando...</td></tr>
      </tbody>
    </table>
    <div style="margin-top:1rem">
      <button class="btn" onclick="guardarPermisos()">Guardar permisos</button>
    </div>
  </div>
</div>

<!-- Canal de alertas del bot -->
<div class="section">
  <h2>Canal de <span>Alertas Bot</span></h2>
  <div class="panel">
    <div class="field">
      <label>Canal ID para alertas de tareas</label>
      <input type="text" id="canal-alertas" placeholder="123456789012345678">
      <div class="hint">El bot enviará alertas de tareas retrasadas y recordatorios diarios a este canal. Obtén el ID haciendo clic derecho en el canal en Discord (modo desarrollador activado).</div>
    </div>
    <button class="btn" onclick="guardarCanalAlertas()">Guardar</button>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
const CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';

const COMANDOS = [
  { cmd: 'orden',       label: 'cd!orden',         desc: 'Asignar tarea a un staff' },
  { cmd: 'nueva_obra',  label: 'cd!nueva_obra',     desc: 'Crear nueva serie en Drive' },
  { cmd: 'importar',    label: 'cd!importar',       desc: 'Importar capítulos a una serie' },
  { cmd: 'cancelar',    label: 'cd!cancelar',       desc: 'Cancelar tareas de un staff' },
  { cmd: 'extender',    label: 'cd!extender',       desc: 'Extender plazo de tareas' },
  { cmd: 'ver_usuarios',label: 'cd!ver_usuarios',   desc: 'Ver staff registrado en BD' },
  { cmd: 'aviso_staff', label: 'cd!aviso_staff',    desc: 'Enviar aviso a todos' },
  { cmd: 'tareas',      label: 'cd!tareas @usuario',desc: 'Ver tareas de otro miembro' },
  { cmd: 'reportar',    label: 'cd!reportar',       desc: 'Registrar error a un staff' },
];

const NIVELES = [
  { value: 'admin',      label: 'Solo Admin/Lider' },
  { value: 'supervisor', label: 'Admin + Supervisor' },
  { value: 'all',        label: 'Todo el staff' },
];

let configActual = {};

function toast(msg, type = 'ok') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = '.3s'; setTimeout(() => t.remove(), 300); }, 3500);
}

async function api(action, post = null) {
  const url = 'api.php?action=' + action;
  const opts = { credentials: 'same-origin' };
  if (post) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    for (const [k, v] of Object.entries(post)) fd.append(k, v);
    opts.method = 'POST'; opts.body = fd;
  }
  const r = await fetch(url, opts);
  return r.json();
}

async function cargarConfig() {
  const res = await api('getConfigSistema');
  if (!res.exito) return;
  configActual = res.config || {};

  document.getElementById('webhook-subidas').value = configActual['discord_webhook_subidas'] || '';
  document.getElementById('canal-alertas').value   = configActual['canal_alertas'] || '';

  // Renderizar tabla de permisos
  const tbody = document.getElementById('permisos-body');
  tbody.innerHTML = COMANDOS.map(c => {
    const val = configActual['cmd_perm_' + c.cmd] || 'admin';
    const opts = NIVELES.map(n => `<option value="${n.value}" ${n.value === val ? 'selected' : ''}>${n.label}</option>`).join('');
    return `<tr>
      <td style="font-weight:600;font-family:monospace;font-size:.82rem">${c.label}</td>
      <td style="color:var(--muted2)">${c.desc}</td>
      <td><select id="perm-${c.cmd}">${opts}</select></td>
    </tr>`;
  }).join('');
}

async function guardarWebhook() {
  const val = document.getElementById('webhook-subidas').value.trim();
  const res = await api('setConfigSistema', { clave: 'discord_webhook_subidas', valor: val });
  if (res.exito) toast('Webhook guardado');
  else toast(res.mensaje || 'Error', 'err');
}

async function testWebhook() {
  const webhook = document.getElementById('webhook-subidas').value.trim();
  const status  = document.getElementById('webhook-status');
  if (!webhook) { toast('Primero guarda el webhook', 'err'); return; }
  status.textContent = 'Enviando...';
  status.className   = '';

  const payload = JSON.stringify({ content: '✅ **Test desde Crimson Scan Panel** — Webhook configurado correctamente.' });
  try {
    const r = await fetch(webhook, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: payload });
    if (r.ok || r.status === 204) {
      status.textContent = '✓ Webhook funcionando';
      status.className   = 'badge-ok';
    } else {
      status.textContent = '✗ Error HTTP ' + r.status;
      status.className   = 'badge-err';
    }
  } catch (e) {
    status.textContent = '✗ Error de red';
    status.className   = 'badge-err';
  }
}

async function guardarCanalAlertas() {
  const val = document.getElementById('canal-alertas').value.trim();
  const res = await api('setConfigSistema', { clave: 'canal_alertas', valor: val });
  if (res.exito) toast('Canal guardado');
  else toast(res.mensaje || 'Error', 'err');
}

async function guardarPermisos() {
  const promesas = COMANDOS.map(c => {
    const val = document.getElementById('perm-' + c.cmd)?.value || 'admin';
    return api('setConfigSistema', { clave: 'cmd_perm_' + c.cmd, valor: val });
  });
  await Promise.all(promesas);
  toast('Permisos guardados');
}

cargarConfig();
</script>
</body>
</html>
