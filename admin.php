<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css?v=3">
<style>
/* Override de seguridad: garantizar layout correcto */
#app-shell { display: flex !important; flex-direction: column !important; }
.app-header { display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; width: 100% !important; }
.tab-content { display: none !important; }
.tab-content.active { display: block !important; }
</style>
</head>
<body>

<div class="bg-grid"></div>
<div class="noise"></div>

<!-- ─── LOGIN ─── -->
<div id="login-screen" class="login-screen">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon">⚔</div>
      <div class="login-logo-text">CRIMSON <span>SCAN</span></div>
    </div>
    <div class="login-title">Acceso al panel</div>
    <div class="field-group">
      <label class="field-label">Contraseña</label>
      <input id="inp-pass" type="password" class="field-input" placeholder="••••••••"
             onkeydown="if(event.key==='Enter')verificarLogin()">
    </div>
    <div id="login-error" class="error-msg hidden">Contraseña incorrecta.</div>
    <button class="btn-primary btn" style="width:100%; margin-top:1rem" onclick="verificarLogin()">
      <span>Entrar al panel</span> <span>→</span>
    </button>
  </div>
</div>

<!-- ─── APP SHELL ─── -->
<div id="app-shell" class="app-shell hidden">

  <!-- HEADER BAR -->
  <header class="app-header">
    <div class="header-brand">
      <span class="header-icon">⚔</span>
      <span class="header-name">CRIMSON <b>SCAN</b></span>
    </div>

    <nav class="header-tabs">
      <button class="htab active" id="htab-dashboard" onclick="switchTab('dashboard')">◈ Dashboard</button>
      <button class="htab" id="htab-proyectos" onclick="switchTab('proyectos')">◫ Proyectos</button>
      <button class="htab" id="htab-nuevo" onclick="switchTab('nuevo')">⊕ Nuevo</button>
      <button class="htab" id="htab-historial" onclick="switchTab('historial')">≡ Historial</button>
      <a class="htab" href="subir.php">↑ Subir</a>
    </nav>

    <div class="header-right">
      <div class="header-search">
        <span>⌕</span>
        <input type="text" id="search-input" placeholder="Buscar…" oninput="handleSearch(this.value)">
      </div>
      <button class="btn btn-ghost btn-sm" onclick="refrescarTodo()">↺</button>
      <button class="user-logout" onclick="cerrarSesion()" title="Cerrar sesión">⎋ Salir</button>
    </div>
  </header>

  <!-- PAGE BODY -->
  <div class="page-body">

    <!-- ══ TAB: DASHBOARD ══ -->
    <div id="tab-dashboard" class="tab-content active">
      <div class="page-header">
        <div>
          <p class="page-sub">Panel de control</p>
          <h1 class="page-title">Crimson <span>Control</span></h1>
        </div>
        <button class="btn btn-primary" onclick="switchTab('nuevo')">+ Nuevo proyecto</button>
      </div>

      <div class="stats-grid">
        <div class="stat-card sc1">
          <div class="stat-value" id="stat-proyectos">—</div>
          <div class="stat-label">Proyectos</div>
          <div class="stat-trend" id="stat-proyectos-sub">en Drive</div>
        </div>
        <div class="stat-card sc2">
          <div class="stat-value" id="stat-total">—</div>
          <div class="stat-label">Total subidas</div>
          <div class="stat-trend" id="stat-total-sub">registros</div>
        </div>
        <div class="stat-card sc3">
          <div class="stat-value" id="stat-hoy">—</div>
          <div class="stat-label">Subidas hoy</div>
          <div class="stat-trend" id="stat-hoy-sub">hoy</div>
        </div>
        <div class="stat-card sc4">
          <div class="stat-value" id="stat-raws">—</div>
          <div class="stat-label">RAWs</div>
          <div class="stat-trend" id="stat-raws-sub">registradas</div>
        </div>
      </div>

      <div class="two-col">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">◎ Actividad reciente</div>
            <button class="btn btn-ghost btn-sm" onclick="refrescarTodo()">↺</button>
          </div>
          <div class="panel-body" id="actividad-mini">
            <div class="empty-msg">Cargando…</div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">≡ Últimas 10 subidas</div>
          </div>
          <div class="table-scroll">
            <table class="data-table">
              <thead><tr><th>Manga</th><th>Cap.</th><th>Etapa</th><th>Estado</th><th>Acciones</th></tr></thead>
              <tbody id="historial-body">
                <tr><td colspan="5" class="loading-cell"><span class="spinner"></span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: PROYECTOS ══ -->
    <div id="tab-proyectos" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Google Drive</p>
          <h1 class="page-title">Mis <span>Proyectos</span></h1>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="cargarProyectos()">↺ Refrescar</button>
      </div>
      <div id="projects-grid" class="projects-grid">
        <div class="loading-cell" style="grid-column:1/-1; padding:4rem; text-align:center"><span class="spinner"></span></div>
      </div>
    </div>

    <!-- ══ TAB: NUEVO PROYECTO ══ -->
    <div id="tab-nuevo" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Gestión</p>
          <h1 class="page-title">Nuevo <span>Proyecto</span></h1>
        </div>
      </div>

      <div class="form-center">
        <div class="panel" style="max-width:520px; width:100%">
          <div class="panel-header">
            <div class="panel-title">⊕ Crear carpetas en Drive</div>
          </div>
          <div class="panel-body">
            <p style="font-size:.9rem;color:var(--muted2);margin-bottom:1.5rem;line-height:1.6">
              Se crearán automáticamente las 5 carpetas de etapa (<b>RAWs, Traducción, Limpieza, Typos y QC</b>) dentro de la carpeta raíz de Google Drive.
            </p>
            <div class="field-group">
              <label class="field-label">Nombre del manga</label>
              <input id="inp-proyecto-nuevo2" type="text" class="field-input" placeholder="Ej: Solo Leveling"
                     onkeydown="if(event.key==='Enter')crearProyectoAction('inp-proyecto-nuevo2','btn-crear2','crear-resultado2')">
            </div>
            <button id="btn-crear2" class="btn btn-primary" style="width:100%; margin-top:.5rem"
                    onclick="crearProyectoAction('inp-proyecto-nuevo2','btn-crear2','crear-resultado2')">
              Crear en Google Drive
            </button>
            <div id="crear-resultado2" style="margin-top:1rem"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: HISTORIAL ══ -->
    <div id="tab-historial" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Google Sheets</p>
          <h1 class="page-title">Historial <span>Completo</span></h1>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="cargarHistorialFull()">↺ Refrescar</button>
      </div>
      <div class="panel">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Manga</th><th>Cap.</th><th>Etapa</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="historial-full-body">
              <tr><td colspan="6" class="loading-cell"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /page-body -->
</div><!-- /app-shell -->

<!-- TOAST -->
<div class="toast-container" id="toast-container"></div>

<!-- EDIT PANEL (drawer desde la derecha) -->
<div id="edit-drawer-overlay" class="drawer-overlay hidden" onclick="closeEditModal()"></div>
<div id="edit-drawer" class="edit-drawer hidden">
  <div class="drawer-header">
    <span>✏️ Editar Registro</span>
    <button class="btn btn-ghost btn-sm" onclick="closeEditModal()">✕</button>
  </div>
  <div class="drawer-body">
    <div class="field-group">
      <label class="field-label">Manga</label>
      <input id="edit-manga" type="text" class="field-input">
    </div>
    <div class="field-group">
      <label class="field-label">Capítulo</label>
      <input id="edit-cap" type="number" step="0.1" class="field-input">
    </div>
    <div class="field-group">
      <label class="field-label">Etapa</label>
      <select id="edit-etapa" class="field-input">
        <option value="01. RAWs">01. RAWs</option>
        <option value="02. Traducción">02. Traducción</option>
        <option value="03. Limpieza y Redibujo">03. Limpieza y Redibujo</option>
        <option value="04. Typos">04. Typos</option>
        <option value="05. Control de Calidad">05. Control de Calidad</option>
      </select>
    </div>
    <button class="btn btn-primary" style="width:100%; margin-top:1rem" onclick="guardarEdicion()">
      Guardar Cambios
    </button>
  </div>
</div>

<!-- CONFIRM DIALOG -->
<div id="confirm-overlay" class="overlay hidden">
  <div class="dialog">
    <div class="dialog-icon">⚠️</div>
    <h3>Confirmar acción</h3>
    <p id="confirm-text">¿Estás seguro?</p>
    <div class="dialog-actions">
      <button class="btn btn-ghost" style="flex:1" onclick="closeConfirm()">Cancelar</button>
      <button class="btn btn-primary confirm" style="flex:1">Confirmar</button>
    </div>
  </div>
</div>

<script src="assets/admin.js?v=4"></script>
<script>
// Parches inline - funciones críticas que garantizan que los tabs carguen
window.addEventListener('DOMContentLoaded', function() {
  // Sobrescribir cargarProyectos si no fue definida por admin.js
  if (typeof cargarProyectos !== 'function') {
    window.cargarProyectos = async function() {
      const grid = document.getElementById('projects-grid');
      if (!grid) return;
      grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem"><span class="spinner"></span> Cargando...</div>';
      try {
        const r = await fetch('api.php?action=proyectos');
        const res = await r.json();
        if (res && res.exito && res.datos && res.datos.length) {
          grid.innerHTML = res.datos.map(function(nombre) {
            return '<div class="project-card"><div class="project-icon">📖</div><div class="project-name">' + nombre + '</div><div class="project-meta">' + nombre + '</div><div class="project-actions"><button class="act-btn" onclick="window.open(\'index.php?proyecto=' + encodeURIComponent(nombre) + '\',\'_blank\')">🔍 Ver</button></div></div>';
          }).join('');
        } else {
          grid.innerHTML = '<div class="empty-msg" style="grid-column:1/-1">No hay proyectos.</div>';
        }
      } catch(e) {
        grid.innerHTML = '<div class="empty-msg" style="grid-column:1/-1">Error: ' + e.message + '</div>';
      }
    };
  }
});
</script>
</body>
</html>
