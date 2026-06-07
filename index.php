<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crimson Scan — Buscador</title>
<meta name="description" content="Panel de gestión de scanlation · Encuentra tu capítulo">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:           #09080f;
    --surface:      rgba(18,14,28,.88);
    --surface2:     rgba(255,255,255,.04);
    --border:       rgba(255,255,255,.08);
    --red:          #DC143C;
    --red-bright:   #FF2851;
    --red-dim:      #7A0022;
    --red-glow:     rgba(220,20,60,.18);
    --text:         #eae8f2;
    --muted:        #6b6880;
    --glass-border: rgba(255,255,255,.10);
  }

  html, body { height: 100%; }

  body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
  }

  /* ── FONDO ── */
  .bg-glow {
    position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background:
      radial-gradient(ellipse 70% 50% at 15% 5%, rgba(220,20,60,.14) 0%, transparent 65%),
      radial-gradient(ellipse 50% 60% at 85% 85%, rgba(122,0,34,.08) 0%, transparent 65%);
  }
  .bg-grid {
    position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: .025;
    background-image: linear-gradient(var(--border) 1px, transparent 1px),
                      linear-gradient(90deg, var(--border) 1px, transparent 1px);
    background-size: 48px 48px;
  }
  .bg-noise {
    position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: .03;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 180px;
  }

  /* ── HEADER ── */
  .site-header {
    position: sticky; top: 0; z-index: 100;
    background: rgba(9,8,15,.82);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
  }
  .header-inner {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 1.5rem; max-width: 1200px; margin: 0 auto;
  }
  .logo {
    display: flex; align-items: center; gap: .5rem;
    font-weight: 800; font-size: .95rem; letter-spacing: .06em;
    text-transform: uppercase; color: var(--text);
    text-decoration: none;
  }
  .logo-icon { color: var(--red); font-size: 1.1rem; }
  .logo-accent { color: var(--red); }

  .site-nav { display: flex; align-items: center; gap: .25rem; }
  .nav-link {
    padding: .4rem .85rem; border-radius: 8px;
    font-size: .85rem; font-weight: 500;
    color: var(--muted); text-decoration: none;
    transition: all .2s;
  }
  .nav-link:hover { color: var(--text); background: var(--surface2); }
  .nav-link.active {
    background: var(--red); color: #fff;
    box-shadow: 0 0 20px rgba(220,20,60,.3);
  }

  /* ── MAIN ── */
  .page-main {
    flex: 1; position: relative; z-index: 1;
    display: flex; flex-direction: column; justify-content: center;
    padding: clamp(2rem, 6vw, 4.5rem) 1.5rem;
  }
  .page-content { max-width: 880px; margin: 0 auto; width: 100%; }

  /* ── HERO ── */
  .hero-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .63rem; font-weight: 700; letter-spacing: .2em;
    text-transform: uppercase; color: var(--red);
    background: rgba(220,20,60,.08);
    border: 1px solid rgba(220,20,60,.22);
    border-radius: 100px; padding: .28rem .8rem;
    margin-bottom: .9rem;
  }
  .hero-title {
    font-size: clamp(2rem, 7vw, 4rem);
    font-weight: 900; line-height: 1.05;
    text-transform: uppercase; letter-spacing: -.02em;
    color: var(--text); margin-bottom: 2rem;
  }
  .hero-title span {
    background: linear-gradient(135deg, var(--red) 0%, var(--red-bright) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* ── SEARCH CARD ── */
  .search-card {
    background: var(--surface);
    border: 1px solid var(--glass-border);
    border-radius: 18px;
    padding: 1.75rem;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    box-shadow:
      0 4px 6px rgba(0,0,0,.2),
      0 16px 40px rgba(0,0,0,.35),
      inset 0 1px 0 rgba(255,255,255,.07);
  }

  .search-grid {
    display: grid;
    grid-template-columns: 1fr 130px 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .field-label {
    display: block; font-size: .62rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .14em;
    color: var(--muted); margin-bottom: .45rem;
  }

  .field-input {
    width: 100%;
    background: rgba(255,255,255,.05);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    font-family: inherit; font-size: .9rem;
    padding: 0 1rem;
    height: 48px;
    outline: none; transition: border-color .2s, box-shadow .2s;
    appearance: none; -webkit-appearance: none;
  }
  .field-input:focus {
    border-color: var(--red);
    box-shadow: 0 0 0 3px var(--red-glow);
  }
  .field-input::placeholder { color: var(--muted); }
  select.field-input {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b6880' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
    cursor: pointer;
  }
  select.field-input option { background: #1a1428; color: var(--text); }

  .search-action { display: flex; justify-content: flex-end; }

  .btn-search {
    height: 48px; padding: 0 2rem;
    background: var(--red);
    color: #fff; font-family: inherit;
    font-size: .9rem; font-weight: 700;
    border: none; border-radius: 10px;
    cursor: pointer; white-space: nowrap;
    transition: background .2s, transform .15s, box-shadow .2s;
    display: flex; align-items: center; gap: .5rem;
    box-shadow: 0 4px 20px rgba(220,20,60,.3);
  }
  .btn-search:hover {
    background: var(--red-bright);
    transform: translateY(-1px);
    box-shadow: 0 6px 28px rgba(220,20,60,.45);
  }
  .btn-search:active { transform: translateY(0); }
  .btn-search:disabled { opacity: .6; cursor: not-allowed; transform: none; }

  /* ── MOBILE ── */
  @media (max-width: 680px) {
    .header-inner { padding: .75rem 1rem; }
    .page-main { padding: 2rem 1.25rem; }
    .hero-title { margin-bottom: 1.5rem; }
    .search-card { padding: 1.25rem; border-radius: 14px; }
    .search-grid { grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: .75rem; }
    .search-grid > div:first-child { grid-column: 1 / -1; }
    .search-action { justify-content: stretch; }
    .btn-search { width: 100%; justify-content: center; height: 52px; font-size: 1rem; }
  }
  @media (max-width: 400px) {
    .search-grid { grid-template-columns: 1fr; }
    .search-grid > div:first-child { grid-column: auto; }
  }

  /* ── RESULTADOS ── */
  #resultados {
    margin-top: 1.25rem;
    background: var(--surface);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    animation: fadeUp .3s ease;
  }
  #resultados.hidden { display: none; }
  /* ── PANEL BD ── */
  #panel-bd {
    margin-top: .75rem;
    background: var(--surface);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    animation: fadeUp .3s ease;
  }
  #panel-bd.hidden { display: none; }
  .bd-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .85rem;
    font-size: .68rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--muted);
  }
  .bd-header a { font-size: .75rem; color: var(--red); text-decoration: none; font-weight: 600; text-transform: none; letter-spacing: 0; }
  .bd-header a:hover { color: var(--red-bright); }
  .asig-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .5rem; }
  .asig-chip {
    background: rgba(255,255,255,.04); border: 1px solid var(--border);
    border-radius: 9px; padding: .55rem .8rem;
  }
  .asig-chip .asig-rol { font-size: .6rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: 3px; }
  .asig-chip .asig-nombre { font-size: .84rem; font-weight: 600; color: var(--text); }
  .asig-chip.filled { border-color: rgba(16,185,129,.3); background: rgba(16,185,129,.06); }
  .asig-chip.filled .asig-nombre { color: #10b981; }
  .asig-chip.empty .asig-nombre { color: var(--muted); font-weight: 400; }
  /* Asignar inline */
  .asignar-form {
    margin-top: .85rem; padding-top: .85rem; border-top: 1px solid var(--border);
    display: flex; gap: .6rem; flex-wrap: wrap; align-items: flex-end;
  }
  .asignar-form select, .asignar-form input {
    background: rgba(255,255,255,.06); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); padding: .5rem .8rem;
    font-size: .83rem; font-family: inherit; outline: none;
    transition: border-color .15s;
  }
  .asignar-form select:focus, .asignar-form input:focus { border-color: var(--red); }
  .asignar-form select option { background: #1a0a1e; }
  .btn-asignar {
    padding: .5rem 1.1rem; background: var(--red); border: none; border-radius: 8px;
    color: #fff; font-family: inherit; font-size: .83rem; font-weight: 600;
    cursor: pointer; white-space: nowrap; transition: background .15s;
  }
  .btn-asignar:hover { background: var(--red-bright); }
  .asignar-msg { font-size: .78rem; margin-top: .4rem; width: 100%; }

  .resultados-titulo {
    font-size: .68rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--muted);
    margin-bottom: 1rem; padding-bottom: .75rem;
    border-bottom: 1px solid var(--border);
  }
  .resultados-titulo span { color: var(--text); }

  .resultado-grid { display: flex; flex-direction: column; gap: .5rem; }

  .resultado-item {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-left: 3px solid var(--red-dim);
    border-radius: 10px;
    padding: .85rem 1.1rem;
    transition: border-left-color .2s, background .2s;
  }
  .resultado-item:hover { border-left-color: var(--red); background: rgba(220,20,60,.04); }

  .resultado-etapa {
    font-size: .68rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--red);
    margin-bottom: .15rem;
  }
  .resultado-nombre { font-size: .82rem; color: var(--muted); }

  .btn-download {
    padding: .45rem 1rem; flex-shrink: 0;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); text-decoration: none;
    border-radius: 8px; font-size: .8rem; font-weight: 600;
    white-space: nowrap; transition: all .2s;
  }
  .btn-download:hover {
    background: var(--red); border-color: var(--red); color: #fff;
    box-shadow: 0 4px 16px rgba(220,20,60,.3);
  }

  .no-resultado {
    text-align: center; color: var(--muted);
    padding: 2.5rem 1rem; font-size: .9rem;
  }
  .no-resultado strong { display: block; font-size: 1.75rem; margin-bottom: .5rem; }

  /* ── LOADING ── */
  .loading-pulse { display: flex; flex-direction: column; gap: .5rem; padding: .25rem 0; }
  .pulse-bar {
    height: 52px; border-radius: 10px;
    background: linear-gradient(90deg, var(--surface2) 25%, rgba(255,255,255,.07) 50%, var(--surface2) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
  }
  .pulse-bar.short { width: 60%; height: 44px; }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

  /* ── FOOTER ── */
  .site-footer {
    text-align: center; padding: 1.25rem 1.5rem;
    color: var(--muted); font-size: .75rem;
    border-top: 1px solid var(--border);
    position: relative; z-index: 1;
  }

  /* ── TOAST ── */
  #_toast {
    position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
    background: rgba(18,14,28,.95); color: var(--text);
    border: 1px solid rgba(220,20,60,.4);
    padding: .6rem 1.4rem; border-radius: 10px;
    font-size: .875rem; z-index: 9999;
    box-shadow: 0 4px 24px rgba(0,0,0,.5);
    transition: opacity .3s; opacity: 0; pointer-events: none;
    backdrop-filter: blur(12px);
    white-space: nowrap;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
</style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-grid"></div>
<div class="bg-noise"></div>

<!-- ─── HEADER ─── -->
<header class="site-header">
  <div class="header-inner">
    <a href="index.php" class="logo">
      <span class="logo-icon">⚔</span>
      CRIMSON <span class="logo-accent">SCAN</span>
    </a>
    <nav class="site-nav">
      <a href="index.php" class="nav-link active">Buscar</a>
      <a href="subir.php" class="nav-link">Subir</a>
      <a href="admin.php" class="nav-link">Admin</a>
    </nav>
  </div>
</header>

<!-- ─── CONTENIDO ─── -->
<main class="page-main">
  <div class="page-content">

    <p class="hero-pill">▸ Panel de gestión de scanlation</p>
    <h1 class="hero-title">Encuentra tu <span>capítulo</span></h1>

    <!-- BUSCADOR -->
    <div class="search-card">
      <div class="search-grid">

        <div>
          <label class="field-label" for="sel-proyecto">Proyecto</label>
          <select id="sel-proyecto" class="field-input">
            <option value="">Cargando proyectos...</option>
          </select>
        </div>

        <div>
          <label class="field-label" for="inp-capitulo">Capítulo</label>
          <input id="inp-capitulo" type="number" min="0" class="field-input" placeholder="Ej: 5">
        </div>

        <div>
          <label class="field-label" for="sel-etapa">Etapa</label>
          <select id="sel-etapa" class="field-input">
            <option value="Todas">Todas las etapas</option>
            <option value="01. RAWs">01. RAWs</option>
            <option value="02. Traducción">02. Traducción</option>
            <option value="03. Limpieza y Redibujo">03. Limpieza y Redibujo</option>
            <option value="04. Typos">04. Typos</option>
            <option value="05. Control de Calidad">05. Control de Calidad</option>
          </select>
        </div>

      </div>
      <div class="search-action">
        <button id="btn-buscar" class="btn-search" onclick="buscarCapitulo()">
          <span id="btn-text">Buscar</span>
          <span>→</span>
        </button>
      </div>
    </div>

    <!-- RESULTADOS Drive -->
    <div id="resultados" class="hidden"></div>

    <!-- PANEL BD: asignaciones + asignar -->
    <div id="panel-bd" class="hidden">
      <div class="bd-header">
        <span>◎ Asignaciones en la BD</span>
        <a id="link-creditos" href="creditos.php" target="_blank">✦ Generar hoja de créditos →</a>
      </div>
      <div class="asig-grid" id="asig-grid"></div>

      <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
      <div class="asignar-form" id="asignar-form">
        <select id="asgn-rol" title="Rol">
          <option value="Traductor">Traductor</option>
          <option value="Limpiador">Limpiador/Redrawer</option>
          <option value="Typesetter">Typesetter</option>
          <option value="QC">QC / Proof</option>
        </select>
        <select id="asgn-staff" title="Staff" style="min-width:160px">
          <option value="">Cargando staff…</option>
        </select>
        <input id="asgn-limite" type="datetime-local" title="Fecha límite (opcional)" style="width:180px">
        <button class="btn-asignar" onclick="asignarTareaIndex()">⊕ Asignar</button>
        <div id="asgn-msg" class="asignar-msg"></div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<footer class="site-footer">
  © 2025 Crimson Scan · Todos los derechos reservados
</footer>

<div id="_toast"></div>

<script>
window._csrf = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
const ETAPA_LABELS = {
  "01. RAWs":                { label: "RAWs",              icon: "📦" },
  "02. Traducción":          { label: "Traducción",         icon: "🌐" },
  "03. Limpieza y Redibujo": { label: "Limpieza/Redibujo",  icon: "✏️" },
  "04. Typos":               { label: "Typos",              icon: "🔤" },
  "05. Control de Calidad":  { label: "Control de Calidad", icon: "✅" },
};

document.addEventListener('DOMContentLoaded', () => {
  cargarProyectos();
  cargarStaffIndex();
});

function cargarProyectos() {
  fetch('api.php?action=proyectos')
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('sel-proyecto');
      if (!data.exito || !data.datos.length) {
        sel.innerHTML = '<option value="">Sin proyectos disponibles</option>';
        return;
      }
      sel.innerHTML = '<option value="">— Selecciona un proyecto —</option>' +
        data.datos.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');
      // Auto-select from URL param
      const pUrl = new URLSearchParams(location.search).get('proyecto');
      if (pUrl) sel.value = pUrl;
    })
    .catch(() => {
      const sel = document.getElementById('sel-proyecto');
      if (sel) sel.innerHTML = '<option value="">Error al cargar</option>';
    });
}

let _lastManga = '';
let _lastCap   = '';

function buscarCapitulo() {
  const proyecto = document.getElementById('sel-proyecto')?.value;
  const capitulo = document.getElementById('inp-capitulo')?.value;
  const etapa    = document.getElementById('sel-etapa')?.value || 'Todas';
  const area     = document.getElementById('resultados');

  if (!proyecto) { showToast('Selecciona un proyecto.'); return; }
  if (!capitulo) { showToast('Ingresa un número de capítulo.'); return; }

  _lastManga = proyecto;
  _lastCap   = capitulo;

  area.classList.remove('hidden');
  area.innerHTML = '<div class="loading-pulse"><div class="pulse-bar"></div><div class="pulse-bar short"></div></div>';

  const btn = document.getElementById('btn-buscar');
  const txt = document.getElementById('btn-text');
  if (btn) { btn.disabled = true; if (txt) txt.textContent = 'Buscando...'; }

  fetch(`api.php?action=enlaces&proyecto=${encodeURIComponent(proyecto)}&capitulo=${encodeURIComponent(capitulo)}&etapa=${encodeURIComponent(etapa)}`)
    .then(r => r.json())
    .then(data => renderResultados(data, proyecto, capitulo))
    .catch(() => { area.innerHTML = '<div class="no-resultado"><strong>⚠️</strong>Error de conexión.</div>'; })
    .finally(() => { if (btn) { btn.disabled = false; if (txt) txt.textContent = 'Buscar'; } });

  // Siempre cargar panel BD
  cargarPanelBD(proyecto, capitulo);
}

async function cargarPanelBD(manga, cap) {
  const panel = document.getElementById('panel-bd');
  if (!panel) return;
  panel.classList.remove('hidden');

  // Actualizar link a créditos
  const lc = document.getElementById('link-creditos');
  if (lc) lc.href = `creditos.php?manga=${encodeURIComponent(manga)}&cap=${encodeURIComponent(cap)}`;

  const grid = document.getElementById('asig-grid');
  grid.innerHTML = '<div style="color:var(--muted);font-size:.8rem">Cargando…</div>';

  const res = await fetch(`api.php?action=capituloAsignaciones&manga=${encodeURIComponent(manga)}&cap=${encodeURIComponent(cap)}`)
    .then(r=>r.json()).catch(()=>null);

  if (!res?.exito) { grid.innerHTML = '<div style="color:var(--muted);font-size:.8rem">Sin datos en BD.</div>'; return; }

  const a = res.asig;
  const roles = [
    { key:'trad',  label:'Traductor/A' },
    { key:'clean', label:'Cleaner' },
    { key:'type',  label:'Typesetter' },
    { key:'proof', label:'QC/Proof' },
  ];
  grid.innerHTML = roles.map(r => {
    const filled = !!a[r.key];
    return `<div class="asig-chip ${filled?'filled':'empty'}">
      <div class="asig-rol">${r.label}</div>
      <div class="asig-nombre">${esc(a[r.key] || '— Sin asignar')}</div>
    </div>`;
  }).join('');
}

async function asignarTareaIndex() {
  const rol        = document.getElementById('asgn-rol')?.value;
  const discord_id = document.getElementById('asgn-staff')?.value;
  const limite     = document.getElementById('asgn-limite')?.value || '';
  const msgEl      = document.getElementById('asgn-msg');

  if (!_lastManga || !_lastCap) { showToast('Busca un capítulo primero.'); return; }
  if (!discord_id) { showToast('Selecciona un miembro del staff.'); return; }

  const fd = new FormData();
  fd.append('csrf_token', window._csrf || '');
  fd.append('manga',      _lastManga);
  fd.append('cap',        _lastCap);
  fd.append('rol',        rol);
  fd.append('discord_id', discord_id);
  if (limite) fd.append('limite', limite.replace('T',' ') + ':00');

  const res = await fetch('api.php?action=asignarTarea', {method:'POST',body:fd})
    .then(r=>r.json()).catch(()=>null);

  if (res?.exito) {
    msgEl.innerHTML = '<span style="color:#10b981">✓ Tarea asignada</span>';
    await cargarPanelBD(_lastManga, _lastCap);
  } else {
    msgEl.innerHTML = `<span style="color:#ff5555">${esc(res?.mensaje || 'Error')}</span>`;
  }
  setTimeout(() => { msgEl.innerHTML = ''; }, 4000);
}

async function cargarStaffIndex() {
  const sel = document.getElementById('asgn-staff');
  if (!sel) return;
  const res = await fetch('api.php?action=listarStaff').then(r=>r.json()).catch(()=>null);
  if (res?.exito && res.datos?.length) {
    sel.innerHTML = '<option value="">— Staff —</option>' +
      res.datos.map(s => {
        const n = s.nombre_display || s.usuario_form || s.discord_id;
        return `<option value="${esc(s.discord_id)}">${esc(n)}</option>`;
      }).join('');
  } else {
    sel.innerHTML = '<option value="">Sin staff</option>';
  }
}

function renderResultados(data, proyecto, capitulo) {
  const area = document.getElementById('resultados');
  if (!data.exito) {
    area.innerHTML = `<div class="no-resultado"><strong>⚠️</strong>${esc(data.mensaje)}</div>`;
    return;
  }
  const enlaces = data.datos || {};
  const keys = Object.keys(enlaces);
  if (!keys.length) {
    area.innerHTML = `<div class="no-resultado"><strong>🔍</strong>No se encontró el capítulo <b>${esc(capitulo)}</b> en <b>${esc(proyecto)}</b>.</div>`;
    return;
  }
  const items = keys.map(etapa => {
    const info = ETAPA_LABELS[etapa] || { label: etapa, icon: "📁" };
    const enlace = enlaces[etapa];
    return `
      <div class="resultado-item">
        <div>
          <div class="resultado-etapa">${info.icon} ${info.label}</div>
          <div class="resultado-nombre">${esc(enlace.nombre)}</div>
        </div>
        <a href="${enlace.url}" class="btn-download" target="_blank" rel="noopener">⬇ Descargar</a>
      </div>`;
  }).join('');
  area.innerHTML = `
    <p class="resultados-titulo"><span>${esc(proyecto)}</span> · Cap. ${esc(capitulo)}</p>
    <div class="resultado-grid">${items}</div>`;
}

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg) {
  const t = document.getElementById('_toast');
  t.textContent = msg; t.style.opacity = '1';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.opacity = '0'; }, 2500);
}

document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && document.activeElement?.id === 'inp-capitulo') buscarCapitulo();
});
</script>
</body>
</html>
