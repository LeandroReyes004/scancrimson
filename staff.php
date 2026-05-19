<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// Solo admin
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    http_response_code(403);
    echo '<p style="color:#dc2020;font-family:sans-serif;padding:2rem">Acceso denegado.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Discord · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:         #0a0a0e;
    --surface:    rgba(255,255,255,.04);
    --border:     rgba(255,255,255,.08);
    --text:       #f0f0f4;
    --muted:      #6e6e82;
    --muted2:     #9898b0;
    --red:        #dc2020;
    --red-bright: #ff3535;
    --green:      #10b981;
    --amber:      #f59e0b;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    padding: 1.5rem;
  }

  h2 {
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--muted2);
    margin-bottom: 1rem;
  }
  h2 span { color: var(--red-bright); }

  .section { margin-bottom: 2.5rem; }

  .panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
  }

  .table-scroll { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  thead th {
    padding: .6rem 1rem;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
    text-align: left;
    background: rgba(255,255,255,.02);
  }
  tbody td {
    padding: .7rem 1rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
  }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover { background: rgba(255,255,255,.03); }

  .badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
  }
  .badge-active   { background: rgba(16,185,129,.15); color: var(--green); border: 1px solid var(--green); }
  .badge-inactive { background: rgba(110,110,130,.15); color: var(--muted2); border: 1px solid var(--muted); }
  .badge-retrasado{ background: rgba(220,32,32,.15); color: var(--red-bright); border: 1px solid var(--red); }
  .badge-ok       { background: rgba(16,185,129,.15); color: var(--green); border: 1px solid var(--green); }

  .act-btn {
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    cursor: pointer;
    font-size: .78rem;
    padding: 4px 12px;
    transition: background .15s;
  }
  .act-btn:hover { background: rgba(255,255,255,.1); }
  .act-btn.danger { border-color: var(--red); color: var(--red-bright); }
  .act-btn.danger:hover { background: rgba(220,32,32,.15); }

  .spinner {
    display: inline-block;
    width: 18px; height: 18px;
    border: 2px solid var(--border);
    border-top-color: var(--red);
    border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .empty { text-align: center; padding: 2rem; color: var(--muted); }

  .ranking-controls {
    display: flex;
    gap: .75rem;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
  }
  .ranking-controls select {
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    padding: 4px 10px;
    font-size: .85rem;
    cursor: pointer;
  }
  .ranking-controls select option { background: #1a1a2e; }

  .podio {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
  }
  .podio-card {
    flex: 1;
    background: rgba(255,255,255,.04);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
  }
  .podio-card.gold   { border-color: #f59e0b66; }
  .podio-card.silver { border-color: #94a3b866; }
  .podio-card.bronze { border-color: #cd7c3966; }
  .podio-pos  { font-size: 1.4rem; margin-bottom: .25rem; }
  .podio-name { font-weight: 600; font-size: .85rem; }
  .podio-pts  { color: var(--red-bright); font-size: 1.1rem; font-weight: 700; margin-top: .25rem; }

  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }
  .stat-mini {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
  }
  .stat-mini-val { font-size: 1.6rem; font-weight: 700; color: var(--red-bright); }
  .stat-mini-lbl { font-size: .7rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-top: .25rem; }

  .toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 999; display: flex; flex-direction: column; gap: .5rem; }
  .toast {
    background: #1e1e2e;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: .6rem 1rem;
    font-size: .85rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    animation: slideIn .2s ease;
  }
  .toast.ok  { border-color: var(--green); }
  .toast.err { border-color: var(--red); }
  @keyframes slideIn { from { transform: translateX(20px); opacity: 0; } }
</style>
</head>
<body>

<!-- Stats globales -->
<div class="stats-row" id="stats-globales">
  <div class="stat-mini"><div class="stat-mini-val" id="gs-staff">—</div><div class="stat-mini-lbl">Staff activo</div></div>
  <div class="stat-mini"><div class="stat-mini-val" id="gs-proyectos">—</div><div class="stat-mini-lbl">Proyectos activos</div></div>
  <div class="stat-mini"><div class="stat-mini-val" id="gs-capitulos">—</div><div class="stat-mini-lbl">Capítulos</div></div>
  <div class="stat-mini"><div class="stat-mini-val" id="gs-terminados">—</div><div class="stat-mini-lbl">Terminados</div></div>
  <div class="stat-mini"><div class="stat-mini-val" id="gs-tareas">—</div><div class="stat-mini-lbl">Tareas activas</div></div>
  <div class="stat-mini"><div class="stat-mini-val" id="gs-tasa">—</div><div class="stat-mini-lbl">Tasa entrega</div></div>
</div>

<!-- Tabla de miembros -->
<div class="section">
  <h2>Miembros de <span>Staff Discord</span></h2>
  <div class="panel">
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>Nombre Discord</th>
            <th>Usuario Form</th>
            <th>Puntos (mes)</th>
            <th>Estado</th>
            <th>Registro</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="staff-body">
          <tr><td colspan="6" class="empty"><span class="spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Ranking mensual -->
<div class="section">
  <h2>Ranking <span>Mensual</span></h2>
  <div class="panel">
    <div class="ranking-controls">
      <label style="color:var(--muted2)">Mes:</label>
      <select id="sel-mes">
        <?php
        $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $mesActual = (int)date('n');
        foreach ($meses as $i => $m) {
            $sel = ($i + 1 === $mesActual) ? 'selected' : '';
            echo "<option value=\"" . ($i+1) . "\" $sel>$m</option>";
        }
        ?>
      </select>
      <label style="color:var(--muted2)">Año:</label>
      <select id="sel-anio">
        <?php
        $anio = (int)date('Y');
        for ($y = $anio; $y >= $anio - 2; $y--) {
            echo "<option value=\"$y\">$y</option>";
        }
        ?>
      </select>
      <button class="act-btn" onclick="cargarRanking()">Ver ranking</button>
    </div>
    <div id="podio-container"></div>
    <div class="table-scroll">
      <table>
        <thead>
          <tr><th>#</th><th>Nombre</th><th>Usuario Form</th><th>Puntos</th></tr>
        </thead>
        <tbody id="ranking-body">
          <tr><td colspan="4" class="empty"><span class="spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tareas activas -->
<div class="section">
  <h2>Tareas <span>Activas</span></h2>
  <div class="panel">
    <div class="table-scroll">
      <table>
        <thead>
          <tr><th>Staff</th><th>Obra</th><th>Cap.</th><th>Rol</th><th>Límite</th><th>Estado</th></tr>
        </thead>
        <tbody id="tareas-body">
          <tr><td colspan="6" class="empty"><span class="spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Últimos errores -->
<div class="section">
  <h2>Historial de <span>Errores</span></h2>
  <div class="panel">
    <div class="table-scroll">
      <table>
        <thead>
          <tr><th>Staff</th><th>Descripción</th><th>Fecha</th></tr>
        </thead>
        <tbody id="errores-body">
          <tr><td colspan="3" class="empty"><span class="spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
const CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';

function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toast(msg, type = 'ok') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = '.3s'; setTimeout(() => t.remove(), 300); }, 3500);
}

async function api(action, params = {}) {
  const url = 'api.php?action=' + action + (params.qs ? '&' + params.qs : '');
  const opts = { credentials: 'same-origin' };
  if (params.post) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    for (const [k, v] of Object.entries(params.post)) fd.append(k, v);
    opts.method = 'POST';
    opts.body   = fd;
  }
  try {
    const r = await fetch(url, opts);
    return await r.json();
  } catch (e) {
    return { exito: false, mensaje: 'Error de red' };
  }
}

async function cargarStats() {
  const res = await api('estadisticasGlobales');
  if (!res.exito) return;
  const s = res.data;
  document.getElementById('gs-staff').textContent     = s.total_staff;
  document.getElementById('gs-proyectos').textContent = s.total_proyectos;
  document.getElementById('gs-capitulos').textContent = s.total_capitulos;
  document.getElementById('gs-terminados').textContent= s.terminados;
  document.getElementById('gs-tareas').textContent    = s.activas;
  document.getElementById('gs-tasa').textContent      = s.tasa_entrega + '%';
}

async function cargarStaff() {
  const tbody = document.getElementById('staff-body');
  tbody.innerHTML = '<tr><td colspan="6" class="empty"><span class="spinner"></span></td></tr>';
  const res = await api('listarStaff');
  if (!res.exito || !res.data.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="empty">Sin miembros registrados.</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.map(m => {
    const activo = parseInt(m.activo);
    const fecha  = (m.creado || '').substring(0, 10);
    return `<tr>
      <td style="font-weight:600">${esc(m.nombre_display)}</td>
      <td style="color:var(--muted2)">${esc(m.usuario_form)}</td>
      <td style="color:var(--red-bright);font-weight:700">${m.puntos_mes}</td>
      <td><span class="badge ${activo ? 'badge-active' : 'badge-inactive'}">${activo ? 'Activo' : 'Inactivo'}</span></td>
      <td style="color:var(--muted);font-size:.8rem">${fecha}</td>
      <td>
        <button class="act-btn ${activo ? 'danger' : ''}" onclick="toggleStaff('${esc(m.discord_id)}', ${activo ? 0 : 1}, this)">
          ${activo ? 'Desactivar' : 'Activar'}
        </button>
      </td>
    </tr>`;
  }).join('');
}

async function toggleStaff(discordId, nuevoActivo, btn) {
  btn.disabled = true;
  const res = await api('toggleStaff', { post: { discord_id: discordId, activo: nuevoActivo } });
  if (res.exito) {
    toast(nuevoActivo ? 'Miembro activado' : 'Miembro desactivado');
    cargarStaff();
  } else {
    toast(res.mensaje || 'Error', 'err');
    btn.disabled = false;
  }
}

async function cargarRanking() {
  const mes  = document.getElementById('sel-mes').value;
  const anio = document.getElementById('sel-anio').value;
  const tbody = document.getElementById('ranking-body');
  const podio = document.getElementById('podio-container');
  tbody.innerHTML = '<tr><td colspan="4" class="empty"><span class="spinner"></span></td></tr>';
  podio.innerHTML = '';

  const res = await api('rankingMes', { qs: 'mes=' + mes + '&anio=' + anio });
  if (!res.exito || !res.data.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="empty">Sin datos para este período.</td></tr>';
    return;
  }

  // Podio top 3
  const top3 = res.data.slice(0, 3);
  const cls  = ['gold', 'silver', 'bronze'];
  const emoj = ['🥇', '🥈', '🥉'];
  if (top3.length) {
    podio.innerHTML = '<div class="podio">' + top3.map((m, i) => `
      <div class="podio-card ${cls[i]}">
        <div class="podio-pos">${emoj[i]}</div>
        <div class="podio-name">${esc(m.nombre_display)}</div>
        <div class="podio-pts">${m.puntos} pts</div>
      </div>
    `).join('') + '</div>';
  }

  tbody.innerHTML = res.data.map((m, i) => `
    <tr>
      <td style="color:var(--muted);font-weight:700">#${i + 1}</td>
      <td style="font-weight:600">${esc(m.nombre_display)}</td>
      <td style="color:var(--muted2)">${esc(m.usuario_form)}</td>
      <td style="color:var(--red-bright);font-weight:700">${m.puntos}</td>
    </tr>
  `).join('');
}

async function cargarTareas() {
  const tbody = document.getElementById('tareas-body');
  const res   = await api('tareasActivas');
  if (!res.exito || !res.data.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="empty">No hay tareas activas.</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.map(t => {
    const horas    = parseInt(t.horas_restantes);
    const retrasada= horas < 0;
    return `<tr>
      <td style="font-weight:600">${esc(t.nombre_display)}</td>
      <td>${esc(t.obra)}</td>
      <td>#${esc(t.cap)}</td>
      <td><span class="badge badge-inactive">${esc(t.rol)}</span></td>
      <td style="font-size:.8rem;color:${retrasada ? 'var(--red-bright)' : 'var(--muted2)'}">${(t.limite || '').substring(0, 16)}</td>
      <td><span class="badge ${retrasada ? 'badge-retrasado' : 'badge-ok'}">${retrasada ? 'Retrasada' : 'A tiempo'}</span></td>
    </tr>`;
  }).join('');
}

async function cargarErrores() {
  const tbody = document.getElementById('errores-body');
  const res   = await api('erroresStaff');
  if (!res.exito || !res.data.length) {
    tbody.innerHTML = '<tr><td colspan="3" class="empty">Sin errores registrados.</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.map(e => `
    <tr>
      <td style="font-weight:600">${esc(e.nombre_display)}</td>
      <td>${esc(e.descripcion || e.tipo || '—')}</td>
      <td style="color:var(--muted);font-size:.8rem">${(e.fecha || '').substring(0, 16)}</td>
    </tr>
  `).join('');
}

// Cargar todo al iniciar
(async () => {
  await Promise.all([cargarStats(), cargarStaff(), cargarRanking(), cargarTareas(), cargarErrores()]);
})();
</script>
</body>
</html>
