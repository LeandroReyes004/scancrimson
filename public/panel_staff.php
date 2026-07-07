<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config.php';

$user = auth_get_user();
if (!$user) { header('Location: login.php'); exit; }
if ($user['rol'] === 'admin') { header('Location: admin.php'); exit; }

$csrf_token = csrf_token_generate();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Panel Staff · Crimson Scan</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Bebas+Neue&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #080810; --surface: #13131f; --surface2: rgba(255,255,255,.04);
    --border: rgba(255,255,255,.08); --text: #f0f0f4; --muted: #6e6e82; --muted2: #9898b0;
    --red: #dc2020; --red-bright: #ff3535; --red-glow: rgba(220,32,32,.2);
    --green: #10b981; --amber: #f59e0b; --blue: #3b82f6;
    --tab-h: 64px; --header-h: 56px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
  html, body { height: 100%; overflow: hidden; }
  body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; }

  /* ── HEADER ── */
  .header {
    position: fixed; top: 0; left: 0; right: 0; height: var(--header-h); z-index: 100;
    background: rgba(8,8,16,.92); border-bottom: 1px solid var(--border);
    backdrop-filter: blur(20px);
    display: flex; align-items: center; justify-content: space-between; padding: 0 1rem;
  }
  .header-logo { font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: .08em; }
  .header-logo span { color: var(--red-bright); }
  .header-right { display: flex; align-items: center; gap: .75rem; }
  .header-user { font-size: 0.95rem; color: var(--muted2); text-transform: capitalize; }
  .header-user strong { color: var(--text); }
  .logout-btn {
    background: rgba(220,32,32,.12); border: 1px solid rgba(220,32,32,.3);
    border-radius: 8px; color: var(--red-bright); font-size: .75rem; font-family: inherit;
    padding: 5px 10px; cursor: pointer; transition: background .15s;
  }
  .logout-btn:hover { background: rgba(220,32,32,.25); }

  /* ── CONTENT AREA ── */
  .content {
    position: fixed; top: var(--header-h); bottom: var(--tab-h); left: 0; right: 0;
    overflow-y: auto; -webkit-overflow-scrolling: touch;
  }
  .tab-pane { display: none; padding: 1.25rem 1rem; min-height: 100%; }
  .tab-pane.active { display: block; }

  /* ── BOTTOM TAB BAR ── */
  .tab-bar {
    position: fixed; bottom: 0; left: 0; right: 0; height: var(--tab-h); z-index: 100;
    background: rgba(13,13,25,.96); border-top: 1px solid var(--border);
    backdrop-filter: blur(20px);
    display: flex; justify-content: space-evenly; align-items: center;
  }
  .tab-item {
    flex: 1; max-width: 80px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 3px; cursor: pointer; color: var(--muted); transition: color .15s;
    font-size: .68rem; font-weight: 500; letter-spacing: .03em; padding: .4rem 0;
    border: none; background: none; font-family: inherit;
  }
  .tab-item.active { color: var(--red-bright); }
  .tab-item .tab-icon { font-size: 1.3rem; line-height: 1; }
  .tab-item .tab-dot {
    width: 4px; height: 4px; border-radius: 50%; background: var(--red-bright);
    margin-top: 2px; opacity: 0;
  }
  .tab-item.active .tab-dot { opacity: 1; }

  /* ── CARDS ── */
  .card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    overflow: hidden; margin-bottom: 1rem;
  }
  .card-header {
    padding: .9rem 1.1rem; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: .6rem;
  }
  .card-header .icon { font-size: 1.1rem; }
  .card-header .title { font-weight: 600; font-size: .88rem; }
  .card-header .badge-count {
    margin-left: auto; background: var(--red); color: #fff;
    border-radius: 12px; font-size: .68rem; font-weight: 700; padding: 2px 8px;
  }
  .card-body { padding: 1rem 1.1rem; }

  /* ── FORM ELEMENTS ── */
  .field { margin-bottom: 1rem; }
  .field label { display: block; font-size: .7rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: .4rem; }
  .field select, .field input[type=text], .field input[type=number] {
    width: 100%; background: rgba(255,255,255,.05); border: 1px solid var(--border);
    border-radius: 10px; color: var(--text); padding: .75rem 1rem;
    font-family: inherit; font-size: .9rem; outline: none; appearance: none;
    transition: border-color .15s;
  }
  .field select:focus, .field input:focus { border-color: var(--red); }
  .field select option { background: #1a1a2e; }

  /* ── FILE DROP ZONE ── */
  .drop-zone {
    border: 2px dashed var(--border); border-radius: 12px; padding: 2rem 1rem;
    text-align: center; cursor: pointer; transition: border-color .2s, background .2s;
    position: relative;
  }
  .drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--red); background: rgba(220,32,32,.05);
  }
  .drop-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
  .drop-zone .dz-icon { font-size: 2rem; margin-bottom: .5rem; }
  .drop-zone .dz-text { color: var(--muted); font-size: .85rem; }
  .drop-zone .dz-file { color: var(--text); font-size: .9rem; font-weight: 600; }

  /* ── PROGRESS BAR ── */
  .progress-wrap { background: rgba(255,255,255,.05); border-radius: 8px; height: 8px; overflow: hidden; margin: .75rem 0; display: none; }
  .progress-bar { height: 100%; background: var(--red); transition: width .3s; border-radius: 8px; }

  /* ── BUTTONS ── */
  .btn-primary {
    width: 100%; background: var(--red); border: none; border-radius: 12px;
    color: #fff; font-family: inherit; font-size: .95rem; font-weight: 600;
    padding: .9rem; cursor: pointer; transition: background .15s, transform .1s;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
  }
  .btn-primary:active { transform: scale(.98); }
  .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
  .btn-primary:hover:not(:disabled) { background: #b81a1a; }

  .btn-sm {
    background: rgba(255,255,255,.06); border: 1px solid var(--border); border-radius: 8px;
    color: var(--text); font-family: inherit; font-size: .78rem; padding: 5px 12px; cursor: pointer;
    transition: background .15s;
  }
  .btn-sm:hover { background: rgba(255,255,255,.12); }
  .btn-sm.ok { border-color: var(--green); color: var(--green); }

  /* ── TASK CARDS ── */
  .task-item {
    padding: 1rem; border-bottom: 1px solid var(--border);
    display: flex; flex-direction: column; gap: .5rem;
  }
  .task-item:last-child { border-bottom: none; }
  .task-header { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
  .task-obra { font-weight: 600; font-size: .9rem; }
  .task-cap { color: var(--muted2); font-size: .82rem; }
  .task-rol { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: .72rem; font-weight: 700; }
  .task-deadline { font-size: .78rem; }
  .task-deadline.urgent { color: var(--red-bright); }
  .task-deadline.ok     { color: var(--green); }
  .task-deadline.warn   { color: var(--amber); }

  /* ── RANKING ── */
  .ranking-hero {
    text-align: center; padding: 1.5rem 1rem;
    border-bottom: 1px solid var(--border);
  }
  .ranking-pts { font-family: 'Bebas Neue', sans-serif; font-size: 4rem; color: var(--red-bright); line-height: 1; }
  .ranking-pts-label { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; margin-top: .25rem; }
  .ranking-pos { margin-top: .75rem; color: var(--muted2); font-size: .88rem; }
  .ranking-pos strong { color: var(--text); font-size: 1.1rem; }
  .ranking-row { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; border-bottom: 1px solid var(--border); }
  .ranking-row:last-child { border-bottom: none; }
  .ranking-num { width: 28px; text-align: center; font-weight: 700; color: var(--muted); font-size: .88rem; }
  .ranking-name { flex: 1; font-weight: 500; }
  .ranking-pts-sm { color: var(--red-bright); font-weight: 700; font-size: .9rem; }
  .ranking-me { background: rgba(220,32,32,.08); }

  /* ── EMPTY STATE ── */
  .empty { text-align: center; padding: 2.5rem 1rem; color: var(--muted); }
  .empty .empty-icon { font-size: 2.5rem; margin-bottom: .75rem; }

  /* ── TOAST ── */
  .toast-container { position: fixed; top: calc(var(--header-h) + .75rem); right: .75rem; z-index: 999; display: flex; flex-direction: column; gap: .5rem; }
  .toast { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: .6rem 1rem; font-size: .82rem; max-width: 280px; animation: toastIn .2s ease; }
  .toast.ok  { border-color: var(--green); }
  .toast.err { border-color: var(--red); }
  @keyframes toastIn { from { transform: translateX(20px); opacity: 0; } }

  /* ── SPINNER ── */
  .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.2); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── ROL COLORS ── */
  .rol-Traductor   { background: rgba(59,130,246,.15);  color: #3b82f6; }
  .rol-Limpiador   { background: rgba(139,92,246,.15);  color: #8b5cf6; }
  .rol-Typesetter  { background: rgba(245,158,11,.15);  color: #f59e0b; }
  .rol-QC          { background: rgba(16,185,129,.15);  color: #10b981; }
  .rol-Proofreader { background: rgba(16,185,129,.15);  color: #10b981; }
  .rol-Cleaner     { background: rgba(139,92,246,.15);  color: #8b5cf6; }
  .rol-Typer       { background: rgba(245,158,11,.15);  color: #f59e0b; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="header-logo">CRIMSON <span>SCAN</span></div>
  <div class="header-right">
    <div class="header-user"><strong><?= htmlspecialchars($user['usuario']) ?></strong></div>
    <?php if (strpos($user['usuario'], 'Preview') !== false): ?>
    <a href="login.php?dev_preview=admin2026" class="logout-btn" style="background:rgba(245,158,11,.15);border-color:transparent;color:#f59e0b;text-decoration:none;margin-right:8px;" title="Volver a Modo Admin">👑 Modo Admin</a>
    <?php endif; ?>
    <button class="logout-btn" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.12);color:var(--muted2)" onclick="abrirCambiarPass()">🔑</button>
    <form action="logout.php" method="post" style="margin:0">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <button type="submit" class="logout-btn">Salir</button>
    </form>
  </div>
</header>

<!-- MODAL cambiar contraseña -->
<div id="modal-pass" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:1rem">
  <div style="background:#13131f;border:1px solid var(--border);border-radius:16px;padding:1.5rem;width:100%;max-width:340px">
    <div style="font-weight:700;margin-bottom:1.2rem">🔑 Cambiar contraseña</div>
    <div class="field-group">
      <label class="field-label">Contraseña actual</label>
      <input type="password" id="pass-actual" class="field-input" placeholder="••••••">
    </div>
    <div class="field-group">
      <label class="field-label">Nueva contraseña</label>
      <input type="password" id="pass-nueva" class="field-input" placeholder="••••••">
    </div>
    <div class="field-group" style="margin-bottom:1rem">
      <label class="field-label">Repetir nueva</label>
      <input type="password" id="pass-nueva2" class="field-input" placeholder="••••••">
    </div>
    <div id="pass-msg" style="font-size:.8rem;margin-bottom:.8rem;min-height:1.2rem"></div>
    <div style="display:flex;gap:.75rem">
      <button class="upload-btn" onclick="guardarPass()">Guardar</button>
      <button class="upload-btn" style="background:rgba(255,255,255,.06);color:var(--muted2)" onclick="cerrarCambiarPass()">Cancelar</button>
    </div>
  </div>
</div>

<div id="modal-filtros" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:1rem">
  <div style="background:#13131f;border:1px solid var(--border);border-radius:16px;padding:1.5rem;width:100%;max-width:340px; display:flex; flex-direction:column; max-height:80vh;">
    <div style="font-weight:700;margin-bottom:1.2rem;display:flex;justify-content:space-between">
      <span>⚙️ Filtros del Mercado</span>
      <span style="cursor:pointer;color:var(--muted)" onclick="document.getElementById('modal-filtros').style.display='none'">✕</span>
    </div>
    <div style="margin-bottom:1rem">
      <div style="font-size:0.9rem; color:var(--muted); margin-bottom:0.5rem">Selecciona los proyectos en los que deseas trabajar (los demás se ocultarán):</div>
      <div style="display:flex; gap:10px; margin-bottom:10px;">
        <button class="btn-sm" style="flex:1" onclick="toggleFiltros(true)">Marcar Todos</button>
        <button class="btn-sm" style="flex:1" onclick="toggleFiltros(false)">Ninguno</button>
      </div>
      <div id="filtros-proyectos-list" style="display:flex; flex-direction:column; gap:8px; max-height:40vh; overflow-y:auto; padding-right:5px; border-top:1px solid var(--border); padding-top:10px;">
        <!-- dinamico -->
      </div>
    </div>
    <div style="display:flex;gap:.75rem; margin-top:auto;">
      <button class="upload-btn" onclick="aplicarFiltros()">Aplicar y Guardar</button>
    </div>
  </div>
</div>

<!-- CONTENT -->
<main class="content">

  <!-- DISPONIBLES (Mercado de Tareas) -->
  <div class="tab-pane" id="tab-mercado">
    <div class="flex-center" style="justify-content:space-between; margin-bottom:.5rem;">
      <h2 style="font-size:1.1rem; color:var(--text);"><span style="color:var(--red-bright)">⚑</span> Disponibles</h2>
      <div style="display:flex; gap:0.5rem;">
        <button class="btn btn-ghost btn-sm" onclick="abrirFiltrosMercado()" style="font-size:1.1rem" title="Filtrar Proyectos">⚙️</button>
        <button class="btn btn-ghost btn-sm" onclick="cargarMercado()" style="font-size:1.1rem" title="Recargar">↺</button>
      </div>
    </div>
    <div class="hint" style="margin-bottom:1rem; font-size:0.85rem;">Toma capítulos disponibles. Dependiendo de tu rol, algunas opciones pueden estar bloqueadas hasta que se completen etapas anteriores.</div>
    <div id="mercado-list" style="display:flex; flex-direction:column; gap:10px;">
      <div class="empty"><span class="spinner"></span></div>
    </div>
  </div>

  <!-- TAB: SUBIR -->
  <div class="tab-pane active" id="tab-subir">
    <div class="card">
      <div class="card-header">
        <span class="icon">📤</span>
        <span class="title">Subir archivo</span>
      </div>
      <div class="card-body">
        <div class="field">
          <label>Proyecto</label>
          <select id="sel-proyecto"><option value="">Cargando...</option></select>
        </div>
        <div class="field">
          <label>Capítulo</label>
          <input type="number" id="inp-cap" placeholder="Ej: 12" min="1" step="1">
        </div>
        <div class="field">
          <label>Etapa</label>
          <select id="sel-etapa">
            <option value="01. RAWs">01. RAWs</option>
            <option value="02. Traducción">02. Traducción</option>
            <option value="03. Limpieza y Redibujo">03. Limpieza y Redibujo</option>
            <option value="04. Typos">04. Typos</option>
            <option value="05. Control de Calidad">05. Control de Calidad</option>
            <option value="06. Listo para Subir">06. Listo para Subir</option>
          </select>
        </div>
        <div class="field">
          <label>Archivo</label>
          <div class="drop-zone" id="drop-zone">
            <input type="file" id="file-input" accept=".zip,.rar,.7z,.cbz,.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.odt,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,application/x-7z-compressed">
            <div class="dz-icon">📁</div>
            <div class="dz-text" id="dz-label">Toca para seleccionar archivo</div>
          </div>
        </div>
        <div class="progress-wrap" id="progress-wrap">
          <div class="progress-bar" id="progress-bar" style="width:0%"></div>
        </div>
        <div id="upload-status" style="font-size:.8rem;color:var(--muted);margin-bottom:.75rem;min-height:1.2rem"></div>
        <button class="btn-primary" id="btn-subir" onclick="subirArchivo()" disabled>
          <span id="btn-subir-txt">Selecciona un archivo</span>
        </button>
      </div>
    </div>
  </div>

  <!-- TAB: TAREAS -->
  <div class="tab-pane" id="tab-tareas">
    <div class="card" id="card-tareas">
      <div class="card-header">
        <span class="icon">📋</span>
        <span class="title">Mis tareas activas</span>
        <span class="badge-count" id="tareas-count" style="display:none">0</span>
      </div>
      <div id="tareas-list">
        <div class="empty"><div class="empty-icon"><span class="spinner"></span></div></div>
      </div>
    </div>
  </div>

  <!-- TAB: BUSCAR -->
  <div class="tab-pane" id="tab-buscar">
    <div class="card">
      <div class="card-header">
        <span class="icon">🔍</span>
        <span class="title">Buscar capítulo</span>
      </div>
      <div class="card-body">
        <div class="field">
          <label>Proyecto</label>
          <select id="buscar-proyecto"><option value="">Cargando...</option></select>
        </div>
        <div class="field">
          <label>Capítulo</label>
          <input type="number" id="buscar-cap" placeholder="Ej: 12" min="1" step="1">
        </div>
        <div class="field">
          <label>Etapa</label>
          <select id="buscar-etapa">
            <option value="Todas">Todas las etapas</option>
            <option value="01. RAWs">01. RAWs</option>
            <option value="02. Traducción">02. Traducción</option>
            <option value="03. Limpieza y Redibujo">03. Limpieza y Redibujo</option>
            <option value="04. Typos">04. Typos</option>
            <option value="05. Control de Calidad">05. Control de Calidad</option>
          </select>
        </div>
        <button class="btn-primary" id="btn-buscar-staff" onclick="buscarCapituloStaff()">
          <span id="btn-buscar-txt">🔍 Buscar</span>
        </button>
        <div id="buscar-resultados" style="margin-top:1rem"></div>
      </div>
    </div>
  </div>

  <!-- TAB: CRÉDITOS -->
  <div class="tab-pane" id="tab-creditos">
    <div class="card">
      <div class="card-header"><span class="icon">✦</span><span class="title">Hoja de Créditos</span></div>
      <div class="card-body">

        <!-- Manga + cap -->
        <div class="field">
          <label>Manga</label>
          <select id="cr-manga" onchange="cargarAsignacionesCredito()">
            <option value="">Seleccionar…</option>
          </select>
        </div>
        <div class="field">
          <label>Capítulo</label>
          <div style="display:flex;gap:.5rem">
            <input type="text" id="cr-cap" placeholder="Ej: 01" style="flex:1">
            <button class="btn-sm" onclick="cargarAsignacionesCredito()" style="flex-shrink:0;padding:.5rem .9rem">Cargar BD</button>
          </div>
        </div>

        <!-- Nombres -->
        <div style="height:1px;background:var(--border);margin:.75rem 0"></div>
        <div style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);text-align:right;margin-bottom:.25rem">px</div>

        <?php foreach([
          ['cr-trad',  'Traductor/A',       'Nombre del traductor'],
          ['cr-type',  'Typesetter',         'Nombre del typesetter'],
          ['cr-clean', 'Cleaner/Redrawer',   'Nombre del cleaner'],
          ['cr-qc',    'Quality Checker',    'STAFF'],
          ['cr-apoyo', 'Staff de Apoyo',     "ESCLAVOS CRIMSON'S"],
        ] as [$id, $lbl, $ph]): ?>
        <div class="field">
          <label><?= $lbl ?></label>
          <div style="display:flex;gap:.5rem;align-items:center">
            <input type="text" id="<?= $id ?>" placeholder="<?= htmlspecialchars($ph) ?>"
              <?= in_array($id, ['cr-qc','cr-apoyo']) ? 'value="'.htmlspecialchars($ph).'"' : '' ?>
              oninput="renderCredito()" style="flex:1;width:auto!important">
            <input type="number" id="sz-<?= $id ?>" value="28" min="8" max="120"
              oninput="renderCredito()"
              style="width:58px;text-align:center;padding:.6rem .3rem;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:.82rem;font-family:monospace;outline:none;-moz-appearance:textfield">
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Color -->
        <div class="field" style="margin-top:.75rem">
          <label>Color del texto</label>
          <div style="display:flex;align-items:center;gap:.6rem">
            <input type="color" id="cr-color" value="#ff2484" oninput="renderCredito()"
              style="width:40px;height:36px;border:1px solid var(--border);border-radius:8px;background:none;cursor:pointer;padding:2px;flex-shrink:0">
            <span style="font-size:.8rem;color:var(--muted2);font-family:monospace">#ff2484</span>
          </div>
        </div>

        <!-- Hint -->
        <p id="cr-hint" style="font-size:.74rem;color:var(--muted);margin:.5rem 0 .75rem;line-height:1.4">
          Cargando imagen…
        </p>

        <!-- Canvas -->
        <canvas id="cr-canvas" style="width:100%;border-radius:10px;border:1px solid var(--border);display:block;touch-action:none"></canvas>
        <p style="font-size:.7rem;color:var(--muted);margin-top:.4rem;text-align:center">Arrastra los textos para posicionarlos</p>

        <!-- Descargar -->
        <button class="btn-primary" style="margin-top:.9rem" onclick="descargarCredito()">⬇ Descargar PNG</button>
      </div>
    </div>
  </div>

  <!-- TAB: EQUIPO -->
  <div class="tab-pane" id="tab-equipo">
    <div class="card">
      <div class="card-header">
        <span class="icon">⚔</span>
        <span class="title">Nuestro Equipo</span>
        <button class="btn-sm" style="margin-left:auto;padding:4px 10px" onclick="cargarEquipo()">↺</button>
      </div>
      <div class="card-body" id="equipo-list" style="padding-top:.25rem">
        <div class="empty"><span class="spinner"></span></div>
      </div>
    </div>
  </div>

  <!-- TAB: RANKING -->
  <div class="tab-pane" id="tab-ranking">
    <div class="card">
      <div class="ranking-hero">
        <div class="ranking-pts" id="rank-pts">—</div>
        <div class="ranking-pts-label">puntos este mes</div>
        <div class="ranking-pos" id="rank-pos"></div>
      </div>
      <div id="rank-top5">
        <div class="empty"><span class="spinner"></span></div>
      </div>
    </div>
  </div>

</main>

<!-- BOTTOM TAB BAR -->
<nav class="tab-bar">
  <button class="tab-item" onclick="switchTab('mercado', this); cargarMercado();">
    <span class="tab-icon">⚑</span>
    <span>Disponibles</span>
    <span class="tab-dot"></span>
  </button>
  <button class="tab-item active" onclick="switchTab('subir', this)">
    <span class="tab-icon">📤</span>
    <span>Subir</span>
    <span class="tab-dot"></span>
  </button>
  <button class="tab-item" onclick="switchTab('tareas', this)">
    <span class="tab-icon">📋</span>
    <span>Tareas</span>
    <span class="tab-dot"></span>
  </button>
  <button class="tab-item" onclick="switchTab('ranking', this)">
    <span class="tab-icon">🏅</span>
    <span>Ranking</span>
    <span class="tab-dot"></span>
  </button>
  <button class="tab-item" onclick="switchTab('buscar', this)">
    <span class="tab-icon">🔍</span>
    <span>Buscar</span>
    <span class="tab-dot"></span>
  </button>
</nav>

<div class="toast-container" id="toast-container"></div>

<script>
const CSRF   = '<?= htmlspecialchars($csrf_token) ?>';
const USUARIO = '<?= htmlspecialchars($user['usuario']) ?>';

// ── Utilidades ──────────────────────────────────────────────────────────────

function toast(msg, type = 'ok') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='.3s'; setTimeout(()=>t.remove(),300); }, 3500);
}

async function api(action, post = null, qs = '') {
  const url  = 'api.php?action=' + action + (qs ? '&' + qs : '');
  const opts = { credentials: 'same-origin' };
  if (post) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    for (const [k, v] of Object.entries(post)) fd.append(k, v);
    opts.method = 'POST'; opts.body = fd;
  }
  try { return await (await fetch(url, opts)).json(); }
  catch { return { exito: false }; }
}

// ── Tabs ─────────────────────────────────────────────────────────────────────

function switchTab(name, btn) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
  if (name === 'tareas')   cargarTareas();
  if (name === 'ranking')  cargarRanking();
  if (name === 'buscar')   cargarProyectosBuscar();
  if (name === 'creditos') initCreditos();
  if (name === 'equipo')   cargarEquipo();
}

// ── Proyectos ────────────────────────────────────────────────────────────────

async function cargarProyectos() {
  const res = await api('proyectos');
  const sel = document.getElementById('sel-proyecto');
  if (res.exito && res.datos.length) {
    sel.innerHTML = '<option value="">Seleccionar...</option>' +
      res.datos.map(p => `<option value="${p}">${p}</option>`).join('');
  } else {
    sel.innerHTML = '<option value="">Sin proyectos</option>';
  }
}

// ── Upload ───────────────────────────────────────────────────────────────────

const fileInput = document.getElementById('file-input');
let selectedFile = null;

fileInput.addEventListener('change', () => {
  selectedFile = fileInput.files[0] || null;
  const lbl = document.getElementById('dz-label');
  const btn = document.getElementById('btn-subir');
  const btxt = document.getElementById('btn-subir-txt');
  if (selectedFile) {
    lbl.innerHTML = `<span class="dz-file">📎 ${selectedFile.name}</span>`;
    btn.disabled = false;
    btxt.textContent = 'Subir archivo';
  } else {
    lbl.textContent = 'Toca para seleccionar archivo';
    btn.disabled = true;
    btxt.textContent = 'Selecciona un archivo';
  }
});

const dropZone = document.getElementById('drop-zone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('drag-over');
  const f = e.dataTransfer.files[0];
  if (f) { selectedFile = f; document.getElementById('dz-label').innerHTML = `<span class="dz-file">📎 ${f.name}</span>`; document.getElementById('btn-subir').disabled = false; document.getElementById('btn-subir-txt').textContent = 'Subir archivo'; }
});

async function subirArchivo() {
  const proyecto = document.getElementById('sel-proyecto').value;
  const cap      = document.getElementById('inp-cap').value.trim();
  const etapa    = document.getElementById('sel-etapa').value;
  if (!proyecto) { toast('Selecciona un proyecto', 'err'); return; }
  if (!cap)      { toast('Ingresa el número de capítulo', 'err'); return; }
  if (!selectedFile) { toast('Selecciona un archivo', 'err'); return; }

  const btn  = document.getElementById('btn-subir');
  const btxt = document.getElementById('btn-subir-txt');
  const prog = document.getElementById('progress-wrap');
  const bar  = document.getElementById('progress-bar');
  const stat = document.getElementById('upload-status');

  btn.disabled = true;
  btxt.innerHTML = '<span class="spinner"></span> Iniciando...';
  prog.style.display = 'block';
  bar.style.width = '5%';
  stat.textContent = 'Obteniendo URL de subida...';

  try {
    // Paso 1: initUpload
    const initRes = await fetch('upload_api.php?action=initUpload', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token: CSRF,
        action: 'initUpload',
        proyecto, capitulo: cap, etapa,
        filename: selectedFile.name,
        mimeType: selectedFile.type || 'application/octet-stream',
        fileSize: selectedFile.size
      })
    });
    const init = await initRes.json();
    if (!init.exito) { throw new Error(init.mensaje || 'Error al iniciar subida'); }

    bar.style.width = '20%';
    stat.textContent = 'Subiendo a Drive...';

    // Paso 2: PUT a Drive
    await new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('PUT', init.uploadUrl);
      xhr.setRequestHeader('Content-Type', selectedFile.type || 'application/octet-stream');
      let bytesSent = 0;
      xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
          bytesSent = e.loaded;
          bar.style.width = (20 + Math.round((e.loaded / e.total) * 75)) + '%';
        }
      };
      xhr.upload.onload = () => { bytesSent = selectedFile.size; };
      xhr.onload  = () => xhr.status < 400 ? resolve() : reject(new Error('HTTP ' + xhr.status));
      // Drive a veces no devuelve CORS headers en la respuesta → onerror aunque el archivo sí llegó.
      // Si todos los bytes fueron enviados, el archivo está en Drive → tratar como éxito.
      xhr.onerror = () => bytesSent >= selectedFile.size ? resolve() : reject(new Error('Error de red'));
      xhr.send(selectedFile);
    });

    bar.style.width = '95%';
    stat.textContent = 'Registrando...';

    // Paso 3: registrar en BD y notificar Discord
    const regRes = await fetch('upload_api.php?action=registrarSubida', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token: CSRF,
        action: 'registrarSubida',
        proyecto, capitulo: cap, etapa,
        filename: selectedFile.name
      })
    });
    const reg = await regRes.json();
    if (!reg.exito) throw new Error(reg.mensaje || 'Error al registrar');

    bar.style.width = '100%';
    stat.textContent = '';
    toast('¡Archivo subido correctamente!');

    // Reset form
    setTimeout(() => {
      selectedFile = null;
      fileInput.value = '';
      document.getElementById('dz-label').textContent = 'Toca para seleccionar archivo';
      document.getElementById('inp-cap').value = '';
      btn.disabled = true;
      btxt.textContent = 'Selecciona un archivo';
      prog.style.display = 'none';
      bar.style.width = '0%';
    }, 1500);

  } catch (e) {
    bar.style.width = '0%';
    prog.style.display = 'none';
    stat.textContent = '';
    btn.disabled = false;
    btxt.textContent = 'Reintentar';
    toast(e.message || 'Error al subir', 'err');
  }
}

// ── Tareas ───────────────────────────────────────────────────────────────────

const ROL_COLORS = {
  Traductor: '#3b82f6', Limpiador: '#8b5cf6', Typesetter: '#f59e0b',
  QC: '#10b981', Proofreader: '#10b981', Cleaner: '#8b5cf6', Typer: '#f59e0b'
};

async function cargarTareas() {
  const list = document.getElementById('tareas-list');
  list.innerHTML = '<div class="empty"><span class="spinner"></span></div>';
  const res = await api('misTareas');
  if (!res.exito) {
    list.innerHTML = '<div class="empty"><div class="empty-icon">❌</div><div>Error al cargar tareas.</div></div>';
    return;
  }
  if (res.vinculado === false) {
    document.getElementById('tareas-count').style.display = 'none';
    list.innerHTML = `<div class="empty"><div class="empty-icon">🔗</div>
      <div style="text-align:center">No estás vinculado con Discord.<br>
      <span style="font-size:.8rem;color:var(--muted)">Usa <b>cd!mi_usuario tu_apodo</b> en el servidor de Discord para vincular tu cuenta.</span></div></div>`;
    return;
  }
  if (!res.data.length) {
    document.getElementById('tareas-count').style.display = 'none';
    list.innerHTML = '<div class="empty"><div class="empty-icon" style="color:var(--muted); opacity:0.7;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div><div style="margin-top: 10px; color: var(--muted); font-weight: 500;">No tienes tareas activas por ahora. ¡Buen trabajo!</div></div>';
    return;
  }
  const cnt = document.getElementById('tareas-count');
  cnt.textContent = res.data.length;
  cnt.style.display = 'inline-block';

  const ahora = Date.now();
  list.innerHTML = res.data.map(t => {
    const limite = new Date(t.limite);
    const diff   = (limite - ahora) / 3600000;
    const clsTime = diff < 0 ? 'urgent' : diff <= 24 ? 'warn' : 'ok';
    const txtTime = diff < 0
      ? `🔴 Vencida hace ${Math.abs(Math.round(diff))}h`
      : diff <= 24 ? `⚠️ Vence en ${Math.round(diff)}h`
      : `✅ Vence ${limite.toLocaleDateString('es', {day:'numeric',month:'short'})}`;
    const color = ROL_COLORS[t.rol] || '#6e6e82';
    const btnExt = t.extension_solicitada == 1 
      ? `<span style="font-size:0.75rem; color:var(--muted)">Extensión pedida</span>`
      : `<button class="btn-sm" style="background:transparent; border:1px solid var(--border); color:var(--text)" onclick="solicitarExtension('${t.id}', this)">Extender</button>`;
      
    const btnCanc = t.cancelacion_solicitada == 1
      ? `<button class="btn-sm err" style="background:transparent; color:var(--muted); border:1px solid rgba(255,255,255,0.1);" disabled>Cancelación Pendiente</button>`
      : `<button class="btn-sm err" style="background:transparent; color:var(--red); border:1px solid rgba(255,0,0,0.3);" onclick="cancelarTarea('${t.id}', this)">Cancelar</button>`;
    
    return `<div class="task-item">
      <div class="task-header">
        <div>
          <div class="task-obra">${t.obra}</div>
          <div class="task-cap">Capítulo #${t.cap}</div>
        </div>
        <span class="task-rol rol-${t.rol}" style="background:${color}22;color:${color}">${t.rol}</span>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem; margin-top:.5rem;">
        <div class="task-deadline ${clsTime}">${txtTime}</div>
      </div>
      <div style="display:flex; gap:0.5rem; margin-top:0.7rem; justify-content:flex-end;">
        ${btnCanc}
        ${btnExt}
        <button class="btn-sm ok" onclick="entregarTarea('${t.id}', this)">Entregar</button>
      </div>
    </div>`;
  }).join('');
}

async function solicitarExtension(tareaId, btn) {
  if (!confirm('¿Seguro que necesitas más tiempo? Se enviará un aviso a los líderes.')) return;
  btn.disabled = true; btn.textContent = '...';
  const res = await api('solicitarExtension', { tarea_id: tareaId });
  if (res.exito) { toast(res.mensaje || 'Extensión solicitada.'); cargarTareas(); }
  else { toast(res.mensaje || 'Error al solicitar.', 'err'); btn.disabled = false; }
}

async function cancelarTarea(tareaId, btn) {
  if (!tareaId || tareaId === 'undefined' || tareaId === '') {
      // Dejamos pasar para que puedan cancelar las tareas fantasma
  }
  if (!confirm('¿Seguro que quieres abandonar la tarea ID ' + tareaId + '? (Esto avisará a los líderes)')) return;
  btn.disabled = true; btn.textContent = '...';
  const res = await api('cancelarTarea', { tarea_id: tareaId }, 'tarea_id=' + tareaId);
  if (res.exito) { toast(res.mensaje || 'Solicitud enviada.'); cargarTareas(); }
  else { toast(res.mensaje || 'Error al cancelar.', 'err'); btn.disabled = false; }
}

window.mercadoCache = [];
window.proyectosOcultos = JSON.parse(localStorage.getItem('crimson_filtros_mercado_ocultos')) || [];

async function cargarMercado() {
  const list = document.getElementById('mercado-list');
  if (!list) return;
  list.innerHTML = '<div class="empty"><span class="spinner"></span></div>';
  const res = await api('getMercadoTareas');
  if (!res.exito) {
    list.innerHTML = '<div class="empty"><div class="empty-icon">❌</div><div>Error al cargar el mercado.</div></div>';
    return;
  }
  if (!res.datos || !res.datos.length) {
    list.innerHTML = '<div class="empty"><div class="empty-icon">🛌</div><div>No hay capítulos disponibles por ahora.</div></div>';
    return;
  }

  window.mercadoCache = res.datos;
  renderMercado();
}

function renderMercado() {
  const list = document.getElementById('mercado-list');
  if (!window.mercadoCache) return;

  // Filtrar terminados y proyectos ocultos
  const disponibles = window.mercadoCache.filter(c => {
    // Filtro por proyecto oculto por el usuario
    if (window.proyectosOcultos.includes(c.proyecto_nombre)) return false;

    const trad_listo = parseInt(c.estado_trad) === 1 || !!c.trad_fecha;
    const clean_listo = parseInt(c.estado_clean) === 1 || !!c.clean_fecha;
    const type_listo = parseInt(c.estado_type) === 1 || !!c.type_fecha;
    
    const isCompleted = (c.estado_general === 'Terminado' || c.estado_general === 'Publicado') || 
                        (trad_listo && clean_listo && type_listo);
    return !isCompleted;
  });
  
  if (!disponibles.length) {
    list.innerHTML = '<div class="empty"><div class="empty-icon">🛌</div><div>No hay tareas disponibles.</div></div>';
    return;
  }

  const grouped = {};
  disponibles.forEach(c => {
    if (!grouped[c.proyecto_nombre]) grouped[c.proyecto_nombre] = [];
    grouped[c.proyecto_nombre].push(c);
  });

  list.innerHTML = Object.entries(grouped).map(([proyecto, capitulos]) => {
    const capsHTML = capitulos.map(c => {
      const raw_listo = parseInt(c.estado_raw) === 1;
      const trad_listo = parseInt(c.estado_trad) === 1 || !!c.trad_fecha;
      const clean_listo = parseInt(c.estado_clean) === 1 || !!c.clean_fecha;
      const type_listo = parseInt(c.estado_type) === 1 || !!c.type_fecha;
      
      let progress = [];
      if (raw_listo) progress.push('Raw ✅');
      if (trad_listo) progress.push('Trad ✅');
      if (clean_listo) progress.push('Clean ✅');
      if (type_listo) progress.push('Type ✅');

      let btnTrad = '';
      if (raw_listo && !trad_listo) {
        if (c.asignaciones && c.asignaciones.trad) btnTrad = `<span class="badge" style="background:#3b82f6; opacity:0.8; padding:0.4rem 0.8rem; border-radius:4px; font-size:0.8rem">Traducción ⏳ ${c.asignaciones.trad}</span>`;
        else btnTrad = `<button class="btn-sm" style="background:#3b82f6" onclick="tomarMercadoTarea(${c.id}, '${c.proyecto_nombre}', '${c.numero}', 'Traductor', this)">Tomar Traducción</button>`;
      }

      let btnClean = '';
      if (raw_listo && !clean_listo) {
        if (c.asignaciones && c.asignaciones.clean) btnClean = `<span class="badge" style="background:#8b5cf6; opacity:0.8; padding:0.4rem 0.8rem; border-radius:4px; font-size:0.8rem">Limpieza ⏳ ${c.asignaciones.clean}</span>`;
        else btnClean = `<button class="btn-sm" style="background:#8b5cf6" onclick="tomarMercadoTarea(${c.id}, '${c.proyecto_nombre}', '${c.numero}', 'Cleaner', this)">Tomar Limpieza</button>`;
      }

      let btnType = '';
      if (trad_listo && clean_listo && !type_listo) {
        if (c.asignaciones && c.asignaciones.type) btnType = `<span class="badge" style="background:#f59e0b; opacity:0.8; padding:0.4rem 0.8rem; border-radius:4px; font-size:0.8rem">Typeo ⏳ ${c.asignaciones.type}</span>`;
        else btnType = `<button class="btn-sm" style="background:#f59e0b" onclick="tomarMercadoTarea(${c.id}, '${c.proyecto_nombre}', '${c.numero}', 'Typer', this)">Tomar Typeo</button>`;
      }
      
      if (!btnTrad && !trad_listo && !raw_listo) btnTrad = `<button class="btn-sm" disabled style="opacity:0.5; cursor:not-allowed" title="Faltan los RAWs">Traducción 🔒</button>`;
      if (!btnClean && !clean_listo && !raw_listo) btnClean = `<button class="btn-sm" disabled style="opacity:0.5; cursor:not-allowed" title="Faltan los RAWs">Limpieza 🔒</button>`;
      if (!btnType && !type_listo && (!trad_listo || !clean_listo)) btnType = `<button class="btn-sm" disabled style="opacity:0.5; cursor:not-allowed" title="Falta Traducción o Limpieza">Typeo 🔒</button>`;

      return `<div style="padding:0.8rem; background:rgba(255,255,255,0.03); border-radius:8px; display:flex; flex-direction:column; gap:0.5rem; border:1px solid var(--border)">
        <div style="font-weight:bold; font-size:1.05rem"><span style="color:var(--red-bright)">Capítulo #${c.numero}</span></div>
        <div style="font-size:0.8rem; color:var(--muted)">Progreso: ${progress.join(' | ') || 'Nada iniciado'}</div>
        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.2rem;">
          ${btnTrad} ${btnClean} ${btnType}
        </div>
      </div>`;
    }).join('');

    return `<details class="card" style="margin-bottom:0; padding:0; overflow:hidden;">
      <summary style="font-weight:bold; font-size:1.1rem; cursor:pointer; outline:none; user-select:none; padding:1rem; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02)">
        <span>${proyecto} <span style="color:var(--muted); font-size:0.9rem; font-weight:normal">(${capitulos.length} disponibles)</span></span>
        <span style="color:var(--muted); font-size:0.8rem;">▼ / ▲</span>
      </summary>
      <div style="padding:1rem; display:flex; flex-direction:column; gap:10px; border-top:1px solid var(--border);">
        ${capsHTML}
      </div>
    </details>`;
  }).join('');
}

function abrirFiltrosMercado() {
  if (!window.mercadoCache) return;
  const list = document.getElementById('filtros-proyectos-list');
  const uniqueProyectos = [...new Set(window.mercadoCache.map(c => c.proyecto_nombre))].sort();
  
  if (uniqueProyectos.length === 0) {
    list.innerHTML = '<div style="color:var(--muted); font-size:0.9rem;">No hay proyectos disponibles en el mercado.</div>';
  } else {
    list.innerHTML = uniqueProyectos.map(p => {
      const isChecked = !window.proyectosOcultos.includes(p);
      return `<label style="display:flex; gap:8px; align-items:center; cursor:pointer; font-size:0.95rem;">
        <input type="checkbox" value="${p}" class="chk-filtro-proy" ${isChecked ? 'checked' : ''}>
        ${p}
      </label>`;
    }).join('');
  }
  document.getElementById('modal-filtros').style.display = 'flex';
}

function toggleFiltros(marcar) {
  const chks = document.querySelectorAll('.chk-filtro-proy');
  chks.forEach(chk => chk.checked = marcar);
}

function aplicarFiltros() {
  const chks = document.querySelectorAll('.chk-filtro-proy');
  const ocultos = [];
  chks.forEach(chk => {
    if (!chk.checked) ocultos.push(chk.value);
  });
  window.proyectosOcultos = ocultos;
  localStorage.setItem('crimson_filtros_mercado_ocultos', JSON.stringify(ocultos));
  document.getElementById('modal-filtros').style.display = 'none';
  renderMercado();
}

async function tomarMercadoTarea(capId, obra, cap, rol, btn) {
  if (!confirm(`¿Tomar tarea de ${rol} para ${obra} #${cap}? Tienes 3 días para entregar.`)) return;
  btn.disabled = true; btn.textContent = '...';
  const res = await api('tomarTarea', { capitulo_id: capId, proyecto: obra, capitulo: cap, rol: rol });
  if (res.exito) {
    toast(`Has tomado la tarea de ${rol}.`);
    cargarMercado();
    cargarTareas(); // Actualizar mis tareas
  } else {
    toast(res.mensaje || 'Error al tomar tarea.', 'err');
    btn.disabled = false;
  }
}

async function entregarTarea(tareaId, btn) {
  btn.disabled = true;
  btn.textContent = '...';
  const res = await api('entregarTarea', { tarea_id: tareaId });
  if (res.exito) {
    toast('¡Tarea marcada como entregada!');
    cargarTareas();
  } else {
    toast(res.mensaje || 'Error', 'err');
    btn.disabled = false;
    btn.textContent = 'Entregar';
  }
}

// ── Ranking ──────────────────────────────────────────────────────────────────

async function cargarRanking() {
  const res = await api('miRanking');
  if (!res.exito) return;

  document.getElementById('rank-pts').textContent = res.puntos;
  document.getElementById('rank-pos').innerHTML = res.puntos > 0
    ? `Posición <strong>#${res.posicion}</strong> este mes`
    : 'Aún no tienes puntos este mes — ¡entrega una tarea!';

  const medals = ['🥇', '🥈', '🥉'];
  const top = res.top5 || [];
  document.getElementById('rank-top5').innerHTML = top.length
    ? top.map((m, i) => `
        <div class="ranking-row ${m.nombre_display === USUARIO || m.nombre_display?.toLowerCase() === USUARIO.toLowerCase() ? 'ranking-me' : ''}">
          <div class="ranking-num">${medals[i] || '#' + (i+1)}</div>
          <div class="ranking-name">${m.nombre_display || '—'}</div>
          <div class="ranking-pts-sm">${m.puntos} pts</div>
        </div>`).join('')
    : '<div class="empty">Sin datos de ranking aún.</div>';
}

// ── Cambiar contraseña ───────────────────────────────────────────────────────

const modal = document.getElementById('modal-pass');
modal.style.display = 'none';

function abrirCambiarPass() {
  document.getElementById('pass-actual').value = '';
  document.getElementById('pass-nueva').value  = '';
  document.getElementById('pass-nueva2').value = '';
  document.getElementById('pass-msg').textContent = '';
  modal.style.cssText = modal.style.cssText.replace('display:none','display:flex');
  modal.style.display = 'flex';
}
function cerrarCambiarPass() { modal.style.display = 'none'; }

async function guardarPass() {
  const msg = document.getElementById('pass-msg');
  const fd  = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('actual',  document.getElementById('pass-actual').value);
  fd.append('nueva',   document.getElementById('pass-nueva').value);
  fd.append('nueva2',  document.getElementById('pass-nueva2').value);
  msg.style.color = 'var(--muted2)';
  msg.textContent = 'Guardando...';
  const res = await fetch('api.php?action=cambiarPassword', { method:'POST', body: fd, credentials:'same-origin' });
  const data = await res.json();
  if (data.exito) {
    msg.style.color = 'var(--green)';
    msg.textContent = '✓ ' + data.mensaje;
    setTimeout(cerrarCambiarPass, 1500);
  } else {
    msg.style.color = 'var(--red-bright)';
    msg.textContent = '✗ ' + (data.mensaje || 'Error');
  }
}

// ── Buscar ───────────────────────────────────────────────────────────────────

const ETAPA_LABELS_STAFF = {
  "01. RAWs":                { label: "RAWs",             icon: "📦" },
  "02. Traducción":          { label: "Traducción",        icon: "🌐" },
  "03. Limpieza y Redibujo": { label: "Limpieza/Redibujo", icon: "✏️" },
  "04. Typos":               { label: "Typos",             icon: "🔤" },
  "05. Control de Calidad":  { label: "Control de Calidad",icon: "✅" },
};

async function cargarProyectosBuscar() {
  const sel = document.getElementById('buscar-proyecto');
  if (sel.options.length > 1) return; // ya cargado
  const res = await api('proyectos');
  if (res.exito && res.datos.length) {
    sel.innerHTML = '<option value="">Seleccionar...</option>' +
      res.datos.map(p => `<option value="${p}">${p}</option>`).join('');
  } else {
    sel.innerHTML = '<option value="">Sin proyectos</option>';
  }
}

async function buscarCapituloStaff() {
  const proyecto = document.getElementById('buscar-proyecto').value;
  const cap      = document.getElementById('buscar-cap').value.trim();
  const etapa    = document.getElementById('buscar-etapa').value || 'Todas';
  const area     = document.getElementById('buscar-resultados');
  const btn      = document.getElementById('btn-buscar-staff');
  const txt      = document.getElementById('btn-buscar-txt');

  if (!proyecto) { toast('Selecciona un proyecto', 'err'); return; }
  if (!cap)      { toast('Ingresa el número de capítulo', 'err'); return; }

  btn.disabled = true;
  txt.innerHTML = '<span class="spinner"></span>';
  area.innerHTML = '';

  const res = await api('enlaces', null,
    `proyecto=${encodeURIComponent(proyecto)}&capitulo=${encodeURIComponent(cap)}&etapa=${encodeURIComponent(etapa)}`);

  btn.disabled = false;
  txt.textContent = '🔍 Buscar';

  if (!res.exito) {
    area.innerHTML = `<div style="text-align:center;padding:1rem;color:var(--muted)">${res.mensaje || 'Error al buscar'}</div>`;
    return;
  }

  const datos = res.datos || {};
  const keys  = Object.keys(datos);

  if (!keys.length) {
    area.innerHTML = `<div style="text-align:center;padding:1rem;color:var(--muted)">No se encontró el capítulo <b>${cap}</b> en <b>${proyecto}</b>.</div>`;
    return;
  }

  area.innerHTML = keys.map(k => {
    const info   = ETAPA_LABELS_STAFF[k] || { label: k, icon: "📁" };
    const enlace = datos[k];
    return `<div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem;background:var(--surface2);border:1px solid var(--border);border-radius:10px;margin-bottom:.5rem">
      <div style="min-width:0">
        <div style="font-size:.75rem;font-weight:700;color:var(--red-bright);margin-bottom:.2rem">${info.icon} ${info.label}</div>
        <div style="font-size:.8rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${enlace.nombre}</div>
      </div>
      <a href="${enlace.url}" target="_blank" rel="noopener"
         style="flex-shrink:0;background:var(--red);color:#fff;padding:.4rem .9rem;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none">
        ⬇ Bajar
      </a>
    </div>`;
  }).join('');
}

// ── Módulo Créditos ──────────────────────────────────────────────────────────

const CR = {
  img: null, loaded: false, drag: null,
  color: '#ff2484',
  font: '"New Wild Words","Wild Words","Bangers",Impact,Arial Black,sans-serif',
  pos: {
    'cr-trad':  { x: 0.390, y: 0.462 },
    'cr-type':  { x: 0.770, y: 0.462 },
    'cr-clean': { x: 0.450, y: 0.605 },
    'cr-qc':    { x: 0.383, y: 0.760 },
    'cr-apoyo': { x: 0.745, y: 0.760 },
  }
};

async function initCreditos() {
  if (CR.loaded) return;
  const hint = document.getElementById('cr-hint');
  hint.textContent = 'Cargando imagen…';
  const res = await api('getImagenCreditos');
  if (!res.exito) { hint.textContent = 'Error: no se encontró la plantilla.'; return; }
  CR.img = new Image();
  CR.img.onload = () => {
    CR.loaded = true;
    hint.textContent = 'Arrastra los nombres sobre la imagen para posicionarlos.';
    document.fonts.ready.then(() => { renderCredito(); initDragCredito(); });
    cargarProyectosCredito();
  };
  CR.img.src = res.data;
}

async function cargarProyectosCredito() {
  const res = await api('proyectos');
  const sel = document.getElementById('cr-manga');
  if (res.exito && res.datos.length) {
    sel.innerHTML = '<option value="">Seleccionar…</option>' +
      res.datos.map(p => `<option value="${p}">${p}</option>`).join('');
  }
}

async function cargarAsignacionesCredito() {
  const manga = document.getElementById('cr-manga').value;
  const cap   = document.getElementById('cr-cap').value.trim();
  if (!manga || cap === '') { toast('Selecciona manga y capítulo', 'err'); return; }
  const res = await api('capituloAsignaciones', null,
    `manga=${encodeURIComponent(manga)}&cap=${encodeURIComponent(cap)}`);
  if (!res.exito) { toast(res.mensaje || 'Sin datos', 'err'); return; }
  const a = res.asig;
  if (a.trad  && !document.getElementById('cr-trad').value)  document.getElementById('cr-trad').value  = a.trad;
  if (a.type  && !document.getElementById('cr-type').value)  document.getElementById('cr-type').value  = a.type;
  if (a.clean && !document.getElementById('cr-clean').value) document.getElementById('cr-clean').value = a.clean;
  renderCredito();
  toast('Asignaciones cargadas');
}

function renderCredito() {
  const canvas = document.getElementById('cr-canvas');
  if (!canvas || !CR.loaded) return;
  const W = CR.img.naturalWidth, H = CR.img.naturalHeight;
  canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(CR.img, 0, 0);
  const color = document.getElementById('cr-color').value;
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillStyle = color; ctx.shadowBlur = 0;
  Object.entries(CR.pos).forEach(([id, pos]) => {
    const val = document.getElementById(id)?.value.trim();
    if (!val) return;
    const sz = parseInt(document.getElementById('sz-' + id)?.value || 28);
    ctx.font = `${sz}px ${CR.font}`;
    ctx.fillText(val, W * pos.x, H * pos.y, W * 0.30);
  });
  if (CR.drag) {
    ctx.save();
    ctx.strokeStyle = 'rgba(255,255,255,.4)'; ctx.lineWidth = 1.5;
    ctx.setLineDash([4,4]);
    ctx.strokeRect(CR.pos[CR.drag].x*W - W*.12, CR.pos[CR.drag].y*H - H*.035, W*.24, H*.07);
    ctx.restore();
  }
}

function initDragCredito() {
  const canvas = document.getElementById('cr-canvas');
  if (!canvas || canvas._crDrag) return;
  canvas._crDrag = true;
  const pt = (e) => {
    const r = canvas.getBoundingClientRect();
    const s = e.touches ? e.touches[0] : e;
    return { x: (s.clientX - r.left) * canvas.width  / r.width,
             y: (s.clientY - r.top)  * canvas.height / r.height };
  };
  const hit = (mx, my) => {
    const W = canvas.width, H = canvas.height;
    for (const id of Object.keys(CR.pos)) {
      if (Math.abs(mx - CR.pos[id].x*W) < W*.13 && Math.abs(my - CR.pos[id].y*H) < H*.045) return id;
    }
    return null;
  };
  let off = {};
  canvas.addEventListener('mousedown',  e => { const {x,y}=pt(e); const id=hit(x,y); if(id){CR.drag=id; off={x:x-CR.pos[id].x*canvas.width,y:y-CR.pos[id].y*canvas.height}; e.preventDefault();} });
  canvas.addEventListener('touchstart', e => { const {x,y}=pt(e); const id=hit(x,y); if(id){CR.drag=id; off={x:x-CR.pos[id].x*canvas.width,y:y-CR.pos[id].y*canvas.height}; e.preventDefault();} }, {passive:false});
  const move = e => { if(!CR.drag) return; e.preventDefault(); const {x,y}=pt(e); CR.pos[CR.drag].x=(x-off.x)/canvas.width; CR.pos[CR.drag].y=(y-off.y)/canvas.height; renderCredito(); };
  canvas.addEventListener('mousemove',  move);
  canvas.addEventListener('touchmove',  move, {passive:false});
  const end = () => { CR.drag = null; renderCredito(); };
  canvas.addEventListener('mouseup',    end);
  canvas.addEventListener('mouseleave', end);
  canvas.addEventListener('touchend',   end);
}

function descargarCredito() {
  renderCredito();
  const canvas = document.getElementById('cr-canvas');
  const manga  = (document.getElementById('cr-manga').value  || 'credito').replace(/\s+/g,'_');
  const cap    = (document.getElementById('cr-cap').value    || '').replace(/\s+/g,'');
  const a = document.createElement('a');
  a.download = `credito_${manga}_cap${cap}.png`;
  a.href = canvas.toDataURL('image/png');
  a.click();
}

// ── Init ─────────────────────────────────────────────────────────────────────

// ── Equipo ───────────────────────────────────────────────────────────────────

const ROL_COLORES = {
  'Traductor':   '#3b82f6',
  'Limpieza':    '#10b981',
  'Typesetter':  '#f59e0b',
  'Quality Check': '#a855f7',
  'Redibujante': '#ec4899',
};

async function cargarEquipo() {
  const cont = document.getElementById('equipo-list');
  if (!cont) return;
  cont.innerHTML = '<div class="empty"><span class="spinner"></span></div>';
  const res = await api('listarEquipo');
  if (!res.exito || !res.data || !res.data.length) {
    cont.innerHTML = '<div class="empty"><div class="empty-icon">👥</div><div>Sin miembros registrados</div></div>';
    return;
  }
  // Agrupar por rol
  const grupos = {};
  for (const m of res.data) {
    const r = m.rol || 'Staff';
    if (!grupos[r]) grupos[r] = [];
    grupos[r].push(m);
  }
  const libres  = res.data.filter(m => !parseInt(m.ocupado)).length;
  const total   = res.data.length;
  let html = `<div style="display:flex;gap:1rem;margin-bottom:1rem;padding-bottom:.8rem;border-bottom:1px solid var(--border)">
    <div style="text-align:center;flex:1">
      <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--green);line-height:1">${libres}</div>
      <div style="font-size:.7rem;color:var(--muted)">Disponibles</div>
    </div>
    <div style="text-align:center;flex:1">
      <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--amber);line-height:1">${total - libres}</div>
      <div style="font-size:.7rem;color:var(--muted)">Ocupados</div>
    </div>
    <div style="text-align:center;flex:1">
      <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--text);line-height:1">${total}</div>
      <div style="font-size:.7rem;color:var(--muted)">Total</div>
    </div>
  </div>`;
  for (const [rol, miembros] of Object.entries(grupos)) {
    const color = ROL_COLORES[rol] || '#9898b0';
    html += `<div style="margin-bottom:1.1rem">
      <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
        <div style="width:3px;height:14px;border-radius:2px;background:${color}"></div>
        <span style="font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)">${rol}</span>
        <span style="font-size:.65rem;color:var(--muted2);margin-left:auto">${miembros.length}</span>
      </div>
      ${miembros.map(m => {
        const libre   = !parseInt(m.ocupado);
        const dotColor = libre ? 'var(--green)' : 'var(--amber)';
        const label   = libre ? 'Libre' : 'Ocupado';
        const labelColor = libre ? 'var(--green)' : 'var(--amber)';
        return `<div style="display:flex;align-items:center;gap:.7rem;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
          <div style="width:8px;height:8px;border-radius:50%;background:${dotColor};flex-shrink:0"></div>
          <div style="font-weight:500;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${m.nombre}</div>
          <div style="font-size:.7rem;font-weight:600;color:${labelColor}">${label}</div>
        </div>`;
      }).join('')}
    </div>`;
  }
  cont.innerHTML = html;
}

cargarProyectos();
cargarTareas();
</script>
</body>
</html>
