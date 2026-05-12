<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<div class="bg-grid"></div>
<div class="noise"></div>

<!-- ─── LOGIN SCREEN ─── -->
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
      <span>Entrar al panel</span>
      <span>→</span>
    </button>
  </div>
</div>

<!-- ─── APP SHELL ─── -->
<div id="app-shell" class="app-shell hidden">

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">⚔</div>
      <div class="sidebar-logo-text">CRIMSON <span>SCAN</span></div>
    </div>

    <div class="nav-section-label">Panel</div>
    <a class="nav-item active" href="#" onclick="switchTab('dashboard');return false;">
      <span class="nav-icon">◈</span> Dashboard
    </a>
    <a class="nav-item" href="index.php">
      <span class="nav-icon">⌕</span> Buscar
    </a>

    <div class="nav-section-label">Gestión</div>
    <a class="nav-item" href="#" onclick="switchTab('proyectos');return false;">
      <span class="nav-icon">◫</span> Proyectos
    </a>
    <a class="nav-item" href="#" onclick="switchTab('nuevo');return false;">
      <span class="nav-icon">⊕</span> Nuevo proyecto
    </a>
    <a class="nav-item" href="subir.php">
      <span class="nav-icon">↑</span> Subir archivo
      <span class="nav-badge" id="nav-badge-subidas"></span>
    </a>

    <div class="nav-section-label">Sistema</div>
    <a class="nav-item" href="#" onclick="switchTab('historial');return false;">
      <span class="nav-icon">≡</span> Historial
    </a>

    <div class="sidebar-footer">
      <div class="user-chip">
        <div class="user-avatar">AD</div>
        <div class="user-info">
          <div class="user-name">Administrador</div>
          <div class="user-role">Crimson Staff</div>
        </div>
        <button class="user-logout" onclick="cerrarSesion()" title="Cerrar sesión">⎋</button>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="top-bar">
      <button class="btn-ghost btn" style="padding: 5px 10px; display: none;" onclick="toggleSidebar()" id="btn-mobile-menu">☰</button>
      
      <div class="top-bar-title">
        <span>Crimson Scan</span>
        <span class="top-bar-sep">/</span>
        <span class="top-bar-page" id="topbar-page">Dashboard</span>
      </div>

      <div class="top-bar-search">
        <i>⌕</i>
        <input type="text" placeholder="Buscar en el panel..." oninput="handleSearch(this.value)">
      </div>

      <div class="top-bar-actions">
        <button class="btn btn-ghost" onclick="refrescarTodo()">↺ Refrescar</button>
        <button class="btn btn-primary" onclick="switchTab('nuevo')">+ Nuevo proyecto</button>
      </div>
    </div>

    <div class="page-body">

      <!-- ══ TAB: DASHBOARD ══ -->
      <div id="tab-dashboard" class="tab-content active">
        <div class="section-head">
          <p>Admin · Panel de control</p>
          <h1>Crimson <span>Control</span></h1>
        </div>

        <div class="stats-grid">
          <div class="stat-card sc1">
            <div class="stat-value" id="stat-proyectos">—</div>
            <div class="stat-label">Proyectos activos</div>
            <div class="stat-trend" id="stat-proyectos-sub">Cargando…</div>
          </div>
          <div class="stat-card sc2">
            <div class="stat-value" id="stat-total">—</div>
            <div class="stat-label">Total subidas</div>
            <div class="stat-trend" id="stat-total-sub">Cargando…</div>
          </div>
          <div class="stat-card sc3">
            <div class="stat-value" id="stat-hoy">—</div>
            <div class="stat-label">Subidas hoy</div>
            <div class="stat-trend" id="stat-hoy-sub">—</div>
          </div>
          <div class="stat-card sc4">
            <div class="stat-value" id="stat-raws">—</div>
            <div class="stat-label">RAWs registradas</div>
            <div class="stat-trend" id="stat-raws-sub">—</div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem;">
          <!-- Left: Quick Actions & Activity -->
          <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="panel">
              <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon">◫</span> Crear proyecto</div>
              </div>
              <div class="panel-body">
                <div class="field-group">
                  <label class="field-label">Nombre del manga</label>
                  <input id="inp-proyecto-nuevo" type="text" class="field-input" placeholder="Ej: Solo Leveling">
                </div>
                <button id="btn-crear" class="btn btn-primary" style="width:100%" onclick="crearProyectoAction('inp-proyecto-nuevo', 'btn-crear', 'crear-resultado')">
                  <span>Crear en Google Drive</span>
                </button>
                <div id="crear-resultado" style="margin-top:1rem"></div>
              </div>
            </div>

            <div class="panel">
              <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon">◎</span> Actividad reciente</div>
              </div>
              <div class="panel-body" id="actividad-mini" style="padding-top:0.5rem">
                <div class="empty-msg">Cargando…</div>
              </div>
            </div>
          </div>

          <!-- Right: Recent History -->
          <div class="panel">
            <div class="panel-header">
              <div class="panel-title"><span class="panel-title-icon">≡</span> Historial reciente</div>
              <button class="btn btn-ghost" onclick="cargarHistorial()">↺ Refrescar</button>
            </div>
            <div class="table-scroll">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Manga</th>
                    <th>Cap.</th>
                    <th>Etapa</th>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="historial-body">
                  <tr><td colspan="6" style="text-align:center;padding:3rem"><span class="spinner"></span> Cargando…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ TAB: PROYECTOS ══ -->
      <div id="tab-proyectos" class="tab-content">
        <div class="section-head">
          <p>Gestión · Drive</p>
          <h1>Mis <span>Proyectos</span></h1>
        </div>
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-title-icon">◫</span> Carpetas en Google Drive</div>
            <button class="btn btn-ghost" onclick="cargarProyectos()">↺ Refrescar</button>
          </div>
          <div class="panel-body">
            <div id="projects-grid" class="projects-grid">
              <div class="empty-msg">Cargando proyectos…</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ TAB: NUEVO PROYECTO ══ -->
      <div id="tab-nuevo" class="tab-content">
        <div class="section-head">
          <p>Gestión · Crear</p>
          <h1>Nuevo <span>Proyecto</span></h1>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem;">
          <div class="panel">
            <div class="panel-header">
              <div class="panel-title"><span class="panel-title-icon">⊕</span> Crear proyecto en Drive</div>
            </div>
            <div class="panel-body">
              <p style="font-size:.9rem;color:var(--muted2);margin-bottom:1.5rem;line-height:1.6">
                Se crearán automáticamente las 5 carpetas de etapa (RAWs, Traducción, Limpieza, Typos y QC) en la raíz de Google Drive.
              </p>
              <div class="field-group">
                <label class="field-label">Nombre del manga</label>
                <input id="inp-proyecto-nuevo2" type="text" class="field-input" placeholder="Ej: Solo Leveling">
              </div>
              <button id="btn-crear2" class="btn btn-primary" style="width:100%" onclick="crearProyectoAction('inp-proyecto-nuevo2', 'btn-crear2', 'crear-resultado2')">
                <span>Crear en Google Drive</span>
              </button>
              <div id="crear-resultado2" style="margin-top:1rem"></div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-header">
              <div class="panel-title"><span class="panel-title-icon">◉</span> Requisitos</div>
            </div>
            <div class="panel-body" style="display:flex; flex-direction:column; gap:1.25rem">
              <div style="display:flex; gap:12px">
                <span style="color:var(--c5)">✓</span>
                <div>
                  <div style="font-size:.9rem; font-weight:600">Apps Script activo</div>
                  <div style="font-size:.8rem; color:var(--muted); margin-top:2px">La conexión con Drive está establecida.</div>
                </div>
              </div>
              <div style="display:flex; gap:12px">
                <span style="color:var(--c4)">⚠</span>
                <div>
                  <div style="font-size:.9rem; font-weight:600">Nombre único</div>
                  <div style="font-size:.8rem; color:var(--muted); margin-top:2px">Evita duplicados para no confundir al sistema.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ TAB: HISTORIAL ══ -->
      <div id="tab-historial" class="tab-content">
        <div class="section-head">
          <p>Sistema · Registros</p>
          <h1>Historial <span>Completo</span></h1>
        </div>
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-title-icon">≡</span> Todos los registros de Google Sheets</div>
            <button class="btn btn-ghost" onclick="cargarHistorialFull()">↺ Refrescar</button>
          </div>
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Manga</th>
                  <th>Cap.</th>
                  <th>Etapa</th>
                  <th>Fecha</th>
                  <th>Archivo</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="historial-full-body">
                <tr><td colspan="6" style="text-align:center;padding:3rem"><span class="spinner"></span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /app-shell -->

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<!-- CONFIRM DIALOG -->
<div id="confirm-overlay" class="overlay hidden">
  <div class="dialog">
    <div class="dialog-icon">🗑</div>
    <h3>¿Eliminar registro?</h3>
    <p>Esta acción no se puede deshacer.</p>
    <div class="dialog-actions">
      <button class="btn btn-ghost" style="flex:1" onclick="closeConfirm()">Cancelar</button>
      <button class="btn btn-primary confirm" style="flex:1">Eliminar</button>
    </div>
  </div>
</div>

<script src="assets/admin.js"></script>
</body>
</html>
