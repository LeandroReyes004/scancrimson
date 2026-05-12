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
    --bg:           #0a0a0f;
    --surface:      rgba(18,18,28,.85);
    --surface2:     rgba(255,255,255,.04);
    --border:       rgba(255,255,255,.08);
    --red:          #dc2020;
    --red-bright:   #ff3535;
    --text:         #e8e8f0;
    --muted:        #6b6b80;
    --glass:        rgba(255,255,255,.05);
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
      radial-gradient(ellipse 60% 40% at 20% 10%, rgba(220,32,32,.12) 0%, transparent 70%),
      radial-gradient(ellipse 50% 50% at 80% 80%, rgba(120,0,0,.08) 0%, transparent 70%);
  }
  .bg-grid {
    position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: .03;
    background-image: linear-gradient(var(--border) 1px, transparent 1px),
                      linear-gradient(90deg, var(--border) 1px, transparent 1px);
    background-size: 48px 48px;
  }

  /* ── HEADER ── */
  .site-header {
    position: sticky; top: 0; z-index: 100;
    background: rgba(10,10,15,.8);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
  }
  .header-inner {
    display: flex; align-items: center; justify-content: space-between;
    padding: .9rem 2rem; max-width: 1200px; margin: 0 auto;
  }
  .logo {
    display: flex; align-items: center; gap: .5rem;
    font-weight: 800; font-size: 1rem; letter-spacing: .05em;
    text-transform: uppercase; color: var(--text);
    text-decoration: none;
  }
  .logo-icon { color: var(--red); font-size: 1.1rem; }
  .logo-accent { color: var(--red); }

  .site-nav { display: flex; align-items: center; gap: .25rem; }
  .nav-link {
    padding: .45rem 1rem; border-radius: 8px;
    font-size: .875rem; font-weight: 500;
    color: var(--muted); text-decoration: none;
    transition: all .2s;
  }
  .nav-link:hover { color: var(--text); background: var(--surface2); }
  .nav-link.active {
    background: var(--red); color: #fff;
    box-shadow: 0 0 20px rgba(220,32,32,.3);
  }

  /* ── MAIN ── */
  .page-main {
    flex: 1; position: relative; z-index: 1;
    display: flex; flex-direction: column; justify-content: center;
    padding: 4rem 2rem;
  }
  .page-content { max-width: 860px; margin: 0 auto; width: 100%; }

  /* ── HERO ── */
  .hero-sub {
    font-size: .7rem; font-weight: 600; letter-spacing: .18em;
    text-transform: uppercase; color: var(--red); margin-bottom: .8rem;
  }
  .hero-title {
    font-size: clamp(2.4rem, 6vw, 4rem);
    font-weight: 900; line-height: 1.05;
    text-transform: uppercase; letter-spacing: -.02em;
    color: var(--text); margin-bottom: 2.5rem;
  }
  .hero-title span { color: var(--red-bright); }

  /* ── SEARCH CARD ── */
  .search-card {
    background: var(--surface);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 48px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.06);
  }

  .search-grid {
    display: grid;
    grid-template-columns: 1fr 140px 1fr auto;
    gap: 1rem; align-items: end;
  }
  @media (max-width: 700px) {
    .search-grid { grid-template-columns: 1fr; }
  }

  .field-label {
    display: block; font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .14em;
    color: var(--muted); margin-bottom: .5rem;
  }

  .field-input {
    width: 100%;
    background: rgba(255,255,255,.05);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    font-family: inherit; font-size: .9rem;
    padding: .7rem 1rem;
    outline: none; transition: border-color .2s, box-shadow .2s;
    appearance: none; -webkit-appearance: none;
  }
  .field-input:focus {
    border-color: var(--red);
    box-shadow: 0 0 0 3px rgba(220,32,32,.15);
  }
  select.field-input {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b6b80' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
    cursor: pointer;
  }
  select.field-input option { background: #1a1a28; color: var(--text); }

  .btn-search {
    padding: .72rem 1.6rem;
    background: var(--red);
    color: #fff; font-family: inherit;
    font-size: .9rem; font-weight: 700;
    border: none; border-radius: 10px;
    cursor: pointer; white-space: nowrap;
    transition: background .2s, transform .15s, box-shadow .2s;
    display: flex; align-items: center; gap: .5rem;
    box-shadow: 0 4px 20px rgba(220,32,32,.3);
  }
  .btn-search:hover {
    background: var(--red-bright);
    transform: translateY(-1px);
    box-shadow: 0 6px 30px rgba(220,32,32,.45);
  }
  .btn-search:active { transform: translateY(0); }
  .btn-search:disabled { opacity: .6; cursor: not-allowed; transform: none; }

  /* ── RESULTADOS ── */
  #resultados {
    margin-top: 1.5rem;
    background: var(--surface);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
  }
  #resultados.hidden { display: none; }

  .resultados-titulo {
    font-size: .75rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--muted);
    margin-bottom: 1rem; padding-bottom: .75rem;
    border-bottom: 1px solid var(--border);
  }
  .resultados-titulo span { color: var(--text); }

  .resultado-grid { display: flex; flex-direction: column; gap: .6rem; }

  .resultado-item {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: .9rem 1.1rem;
    transition: border-color .2s, background .2s;
  }
  .resultado-item:hover { border-color: rgba(220,32,32,.3); background: rgba(220,32,32,.05); }

  .resultado-etapa {
    font-size: .75rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--red-bright);
    margin-bottom: .2rem;
  }
  .resultado-nombre { font-size: .85rem; color: var(--muted); }

  .btn-download {
    padding: .45rem 1.1rem;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); text-decoration: none;
    border-radius: 8px; font-size: .82rem; font-weight: 600;
    white-space: nowrap; transition: all .2s;
  }
  .btn-download:hover {
    background: var(--red); border-color: var(--red); color: #fff;
    box-shadow: 0 4px 16px rgba(220,32,32,.3);
  }

  .no-resultado {
    text-align: center; color: var(--muted);
    padding: 2rem; font-size: .9rem;
  }
  .no-resultado strong { display: block; font-size: 1.5rem; margin-bottom: .5rem; color: var(--text); }

  /* ── LOADING ── */
  .loading-pulse { display: flex; flex-direction: column; gap: .5rem; padding: .5rem 0; }
  .pulse-bar {
    height: 14px; border-radius: 6px;
    background: linear-gradient(90deg, var(--surface2) 25%, rgba(255,255,255,.07) 50%, var(--surface2) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
  }
  .pulse-bar.short { width: 55%; }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

  /* ── FOOTER ── */
  .site-footer {
    text-align: center; padding: 1.5rem;
    color: var(--muted); font-size: .78rem;
    border-top: 1px solid var(--border);
    position: relative; z-index: 1;
  }

  /* ── TOAST ── */
  #_toast {
    position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
    background: #1a1a28; color: var(--text);
    border: 1px solid var(--red);
    padding: .6rem 1.4rem; border-radius: 10px;
    font-size: .875rem; z-index: 9999;
    box-shadow: 0 4px 24px rgba(0,0,0,.5);
    transition: opacity .3s; opacity: 0; pointer-events: none;
  }
</style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-grid"></div>

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

    <p class="hero-sub">Panel de gestión de scanlation</p>
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

        <div>
          <label class="field-label" style="opacity:0">Buscar</label>
          <button id="btn-buscar" class="btn-search" onclick="buscarCapitulo()">
            <span id="btn-text">Buscar</span>
            <span>→</span>
          </button>
        </div>

      </div>
    </div>

    <!-- RESULTADOS -->
    <div id="resultados" class="hidden"></div>

  </div>
</main>

<footer class="site-footer">
  © 2025 Crimson Scan · Todos los derechos reservados
</footer>

<div id="_toast"></div>

<script>
const ETAPA_LABELS = {
  "01. RAWs":                { label: "RAWs",              icon: "📦" },
  "02. Traducción":          { label: "Traducción",         icon: "🌐" },
  "03. Limpieza y Redibujo": { label: "Limpieza/Redibujo",  icon: "✏️" },
  "04. Typos":               { label: "Typos",              icon: "🔤" },
  "05. Control de Calidad":  { label: "Control de Calidad", icon: "✅" },
};

document.addEventListener('DOMContentLoaded', () => {
  cargarProyectos();
  // Pre-fill if coming from admin
  const params = new URLSearchParams(location.search);
  if (params.get('proyecto')) {
    document.getElementById('sel-proyecto').addEventListener('change', function once() {
      this.value = params.get('proyecto');
      this.removeEventListener('change', once);
    });
  }
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

function buscarCapitulo() {
  const proyecto = document.getElementById('sel-proyecto')?.value;
  const capitulo = document.getElementById('inp-capitulo')?.value;
  const etapa    = document.getElementById('sel-etapa')?.value || 'Todas';
  const area     = document.getElementById('resultados');

  if (!proyecto) { showToast('Selecciona un proyecto.'); return; }
  if (!capitulo) { showToast('Ingresa un número de capítulo.'); return; }

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
