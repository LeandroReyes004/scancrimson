<?php require_once __DIR__ . '/../src/auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css?v=4">
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



<!-- ─── APP SHELL ─── -->
<div id="app-shell" class="app-shell">

  <!-- HEADER BAR -->
  <header class="app-header">
    <div class="header-brand">
      <span class="header-icon">⚔</span>
      <span class="header-name">CRIMSON <b>SCAN</b></span>
    </div>

    <nav class="header-tabs">
      <button class="htab active" id="htab-dashboard" onclick="switchTab('dashboard')">◈ Dashboard</button>
      <button class="htab" id="htab-proyectos" onclick="switchTab('proyectos')">◫ Proyectos</button>
      <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
      <button class="htab" id="htab-nuevo" onclick="switchTab('nuevo')">⊕ Nuevo</button>
      <?php endif; ?>
      <button class="htab" id="htab-historial" onclick="switchTab('historial')">≡ Historial</button>
      <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
      <button class="htab" id="htab-usuarios" onclick="switchTab('usuarios')">👤 Usuarios</button>
      <button class="htab" id="htab-staff" onclick="switchTab('staff')">⚔ Staff Discord</button>
      <button class="htab" id="htab-tareasadmin" onclick="switchTab('tareasadmin')">📋 Tareas</button>
      <button class="htab" id="htab-config" onclick="switchTab('config')">⚙ Integraciones</button>
      <?php endif; ?>
      <a class="htab" href="subir.php">↑ Subir</a>
      <a class="htab" href="creditos.php">✦ Créditos</a>
    </nav>

    <div class="header-right">
      <div style="margin-right: 15px; font-size: 0.85rem; color: var(--muted);">
        Hola, <b style="color: var(--text);"><?php echo htmlspecialchars($_SESSION['user']['usuario']); ?></b>
      </div>
      <div class="header-search">
        <span>⌕</span>
        <input type="text" id="search-input" placeholder="Buscar…" oninput="handleSearch(this.value)">
      </div>
      <button class="btn btn-ghost btn-sm" onclick="refrescarTodo()">↺</button>
      <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
      <a href="login.php?dev_preview=staff2026" class="btn btn-ghost btn-sm" title="Ver Modo Staff" style="text-decoration:none; color: var(--color-warning);">👨‍💻 Modo Staff</a>
      <a href="settings.php" class="btn btn-ghost btn-sm" title="Configuración" style="text-decoration:none">⚙</a>
      <?php endif; ?>
      <a href="logout.php" class="user-logout" title="Cerrar sesión" style="text-decoration:none">⎋ Salir</a>
      <button class="hamburger-btn" id="hamburger-btn" onclick="toggleMobileNav()" aria-label="Menú de navegación">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- ─── MOBILE NAV ─── -->
  <div id="mobile-nav-overlay" class="mobile-nav-overlay hidden" onclick="closeMobileNav()"></div>
  <div id="mobile-nav" class="mobile-nav hidden" role="navigation" aria-label="Menú principal">
    <div class="mobile-nav-header">
      <div class="header-icon">⚔</div>
      <span style="font-family:var(--font-head);font-size:1.1rem;letter-spacing:.06em">CRIMSON <b style="color:var(--red)">SCAN</b></span>
      <button class="mobile-nav-close" onclick="closeMobileNav()" aria-label="Cerrar menú">✕</button>
    </div>
    <div class="mobile-nav-body">
      <button class="mobile-nav-item active" data-tab="dashboard" onclick="mobileNavSwitch('dashboard')">◈ Dashboard</button>
      <button class="mobile-nav-item" data-tab="proyectos" onclick="mobileNavSwitch('proyectos')">◫ Proyectos</button>
      <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
      <button class="mobile-nav-item" data-tab="nuevo" onclick="mobileNavSwitch('nuevo')">⊕ Nuevo Proyecto</button>
      <?php endif; ?>
      <button class="mobile-nav-item" data-tab="historial" onclick="mobileNavSwitch('historial')">≡ Historial</button>
      <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
      <button class="mobile-nav-item" data-tab="usuarios" onclick="mobileNavSwitch('usuarios')">👤 Usuarios</button>
      <button class="mobile-nav-item" data-tab="staff" onclick="mobileNavSwitch('staff')">⚔ Staff Discord</button>
      <?php endif; ?>
      <a href="subir.php" class="mobile-nav-item">↑ Subir archivo</a>
      <a href="creditos.php" class="mobile-nav-item">✦ Hoja de Créditos</a>
      <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
      <a href="settings.php" class="mobile-nav-item">⚙ Configuración</a>
      <?php endif; ?>
    </div>
    <div class="mobile-nav-footer">
      <div style="font-size:.72rem;color:var(--muted);padding:.25rem .9rem .6rem;text-transform:uppercase;letter-spacing:.12em">
        <?php echo htmlspecialchars($_SESSION['user']['usuario']); ?> · <?php echo $_SESSION['user']['rol']; ?>
      </div>
      <a href="logout.php" class="mobile-nav-logout">⎋ Cerrar sesión</a>
    </div>
  </div>

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
          <div class="stat-trend" id="stat-proyectos-sub">activos</div>
        </div>
        <div class="stat-card sc2">
          <div class="stat-value" id="stat-disponible">—</div>
          <div class="stat-label">Staff disponible</div>
          <div class="stat-trend" id="stat-disponible-sub">sin tareas</div>
        </div>
        <div class="stat-card sc3">
          <div class="stat-value" id="stat-tareas">—</div>
          <div class="stat-label">Tareas activas</div>
          <div class="stat-trend" id="stat-tareas-sub">en curso</div>
        </div>
        <div class="stat-card sc4">
          <div class="stat-value" id="stat-atrasados">—</div>
          <div class="stat-label">Atrasados</div>
          <div class="stat-trend" id="stat-atrasados-sub">con retraso</div>
        </div>
      </div>

      <div class="two-col">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">✓ Staff disponible</div>
            <button class="btn btn-ghost btn-sm" onclick="cargarDashboard()">↺</button>
          </div>
          <div class="panel-body" id="dash-disponible">
            <div class="empty-msg">Cargando…</div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">⚠ Atrasados / Sin actividad</div>
          </div>
          <div class="panel-body" id="dash-atrasados">
            <div class="empty-msg">Cargando…</div>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-top:1.5rem">
        <div class="panel-header">
          <div class="panel-title">📢 Anunciar subida</div>
        </div>
        <div class="panel-body">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px">
            <div class="field-group" style="margin-bottom:0">
              <label class="field-label">Manga</label>
              <select id="anuncio-manga" class="field-input" onchange="anuncioActualizarMensaje()">
                <option value="">— Seleccionar —</option>
              </select>
            </div>
            <div class="field-group" style="margin-bottom:0">
              <label class="field-label">Capítulo</label>
              <input id="anuncio-cap" type="text" class="field-input" placeholder="Ej: 15" oninput="anuncioActualizarMensaje()">
            </div>
            <div class="field-group" style="margin-bottom:0">
              <label class="field-label">Link</label>
              <input id="anuncio-link" type="url" class="field-input" placeholder="https://…" oninput="anuncioActualizarMensaje()">
            </div>
          </div>
          <div class="field-group" style="margin-bottom:12px">
            <label class="field-label">Mensaje <span style="color:var(--muted);font-weight:400">(editable)</span></label>
            <textarea id="anuncio-mensaje" class="field-input" rows="4" style="resize:vertical;font-family:monospace;font-size:.82rem;line-height:1.5" placeholder="El mensaje se genera automáticamente al completar los campos…"></textarea>
          </div>
          <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:6px;font-size:.83rem;cursor:pointer">
              <input type="checkbox" id="anuncio-discord" checked style="accent-color:#dc2020"> Discord
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:.83rem;cursor:pointer">
              <input type="checkbox" id="anuncio-telegram" style="accent-color:#dc2020"> Telegram
            </label>
            <div style="margin-left:auto;display:flex;gap:8px">
              <button class="btn btn-ghost btn-sm" onclick="anuncioLimpiar()">Limpiar</button>
              <button class="btn btn-primary" onclick="anunciarSubida()" id="btn-anunciar">Publicar</button>
            </div>
          </div>
          <div id="anuncio-resultado" style="margin-top:.75rem;font-size:.83rem"></div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: PROYECTOS ══ -->
    <div id="tab-proyectos" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Flujo de Producción</p>
          <h1 class="page-title">Gestión de <span>Proyectos</span></h1>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="cargarProyectos()">↺ Refrescar</button>
      </div>
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">◎ Proyectos</div>
        </div>
        <div id="acordeon-proyectos" style="padding:0 .25rem">
          <div style="text-align:center;padding:2rem;color:var(--muted)"><span class="spinner"></span></div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: NUEVO PROYECTO ══ -->
    <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
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
    <?php endif; ?>

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
        <div style="padding: 1rem; border-bottom: 1px solid var(--border);">
          <input type="text" id="historial-search" class="field-input" placeholder="Buscar por manga, etapa, fecha..." oninput="filtrarHistorial(this.value)" style="max-width:400px">
        </div>
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

    <!-- ══ TAB: STAFF DISCORD ══ -->
    <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
    <div id="tab-staff" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Bot Discord</p>
          <h1 class="page-title">Staff <span>Discord</span></h1>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="cargarStaff()">↺ Refrescar</button>
      </div>
      <iframe src="staff.php" id="staff-iframe" style="width:100%;border:none;min-height:700px;border-radius:12px;"></iframe>
    </div>
    
    <!-- ══ TAB: TAREAS ADMIN ══ -->
    <div id="tab-tareasadmin" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Mercado y Tareas</p>
          <h1 class="page-title">Gestión de <span>Tareas</span></h1>
        </div>
        <div>
          <button class="btn btn-primary err" onclick="penalizarVencidas()" style="background:transparent; border:1px solid var(--red); color:var(--red);">Penalizar Vencidas</button>
          <button class="btn btn-ghost btn-sm" onclick="cargarTareasAdmin()">↺ Refrescar</button>
        </div>
      </div>
      <div class="panel">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Obra</th><th>Cap</th><th>Staff</th><th>Rol</th><th>Límite</th><th>Acciones</th></tr></thead>
            <tbody id="tareas-admin-body">
              <tr><td colspan="6" class="loading-cell"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ══ TAB: INTEGRACIONES / CONFIG ══ -->
    <div id="tab-config" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Ajustes</p>
          <h1 class="page-title">Integraciones <span>y Webhooks</span></h1>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="cargarConfig()">↺ Refrescar</button>
      </div>

      <div class="panel" style="max-width: 800px;">
        <div style="margin-bottom: 2rem;">
          <h3 style="margin-bottom: 0.5rem; color: var(--text);">Discord Webhook (Subidas de Capítulos)</h3>
          <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">Se utiliza para notificar cuando un capítulo completo ha sido subido/publicado.</p>
          <div style="display: flex; gap: 10px;">
            <input type="text" id="cfg-discord-subidas" class="input-text" placeholder="https://discord.com/api/webhooks/..." style="flex: 1;">
            <button class="btn btn-ghost" onclick="probarWebhook('cfg-discord-subidas', 'tareas')">Probar</button>
            <button class="btn btn-primary" onclick="guardarConfig('discord_webhook_subidas', document.getElementById('cfg-discord-subidas').value, this)">Guardar</button>
          </div>
        </div>

        <div style="margin-bottom: 2rem;">
          <h3 style="margin-bottom: 0.5rem; color: var(--text);">Discord Webhook (Tareas, Extensiones y Cancelaciones)</h3>
          <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">Se utiliza para notificar cuando el Staff toma, cancela o pide extensión de una tarea.</p>
          <div style="display: flex; gap: 10px;">
            <input type="text" id="cfg-discord-anuncios" class="input-text" placeholder="https://discord.com/api/webhooks/..." style="flex: 1;">
            <button class="btn btn-ghost" onclick="probarWebhook('cfg-discord-anuncios', 'anuncios')">Probar</button>
            <button class="btn btn-primary" onclick="guardarConfig('discord_webhook_anuncios', document.getElementById('cfg-discord-anuncios').value, this)">Guardar</button>
          </div>
        </div>

        <div>
          <h3 style="margin-bottom: 0.5rem; color: var(--text);">Telegram Bot</h3>
          <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">Si tienes un bot de Telegram, configura el Token y el ID del Chat para enviar anuncios de capítulos publicados.</p>
          <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" id="cfg-telegram-token" class="input-text" placeholder="Token (ej: 123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11)" style="flex: 1;">
            <button class="btn btn-primary" onclick="guardarConfig('telegram_token', document.getElementById('cfg-telegram-token').value, this)">Guardar</button>
          </div>
          <div style="display: flex; gap: 10px;">
            <input type="text" id="cfg-telegram-chat" class="input-text" placeholder="Chat ID (ej: -1001234567890)" style="flex: 1;">
            <button class="btn btn-primary" onclick="guardarConfig('telegram_chat_id', document.getElementById('cfg-telegram-chat').value, this)">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: USUARIOS ══ -->
    <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
    <div id="tab-usuarios" class="tab-content">
      <div class="page-header">
        <div>
          <p class="page-sub">Staff</p>
          <h1 class="page-title">Gestión de <span>Usuarios</span></h1>
        </div>
        <button class="btn btn-primary" onclick="openNuevoUsuario()">+ Nuevo usuario</button>
      </div>

      <div class="panel">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Creado</th><th>Acciones</th></tr></thead>
            <tbody id="usuarios-body">
              <tr><td colspan="5" class="loading-cell"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

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

<!-- MODAL: NUEVO CAPÍTULO -->
<div id="cap-modal-overlay" class="drawer-overlay hidden" onclick="cerrarModalNuevoCapitulo()"></div>
<div id="cap-modal" class="edit-drawer hidden">
  <div class="drawer-header">
    <span>📑 Añadir Capítulo(s)</span>
    <button class="btn btn-ghost btn-sm" onclick="cerrarModalNuevoCapitulo()">✕</button>
  </div>
  <div class="drawer-body">
    <input type="hidden" id="cap-modal-proy-id">
    <div style="display:flex;gap:4px;margin-bottom:1rem;background:rgba(255,255,255,.06);border-radius:8px;padding:4px">
      <button id="cap-modo-uno" class="btn btn-primary btn-sm" style="flex:1" onclick="setCapModo('uno')">Un capítulo</button>
      <button id="cap-modo-rango" class="btn btn-ghost btn-sm" style="flex:1" onclick="setCapModo('rango')">Rango</button>
    </div>
    <div id="cap-form-uno">
      <div class="field-group">
        <label class="field-label">Número de Capítulo</label>
        <input id="new-cap-num" type="number" step="0.1" class="field-input" placeholder="Ej: 15">
      </div>
    </div>
    <div id="cap-form-rango" style="display:none">
      <div style="display:flex;gap:8px">
        <div class="field-group" style="flex:1">
          <label class="field-label">Desde</label>
          <input id="new-cap-desde" type="number" min="1" class="field-input" placeholder="1">
        </div>
        <div class="field-group" style="flex:1">
          <label class="field-label">Hasta</label>
          <input id="new-cap-hasta" type="number" min="1" class="field-input" placeholder="15">
        </div>
      </div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;font-size:.83rem;color:var(--muted2);margin-top:.75rem;cursor:pointer">
      <input type="checkbox" id="cap-auto-sync" checked style="accent-color:#dc2020">
      Sincronizar con Drive al agregar
    </label>
    <button class="btn btn-primary" style="width:100%; margin-top:1rem" onclick="guardarNuevoCapitulo()">
      Añadir
    </button>
    <div id="cap-modal-resultado" style="margin-top:.75rem;font-size:.83rem;text-align:center"></div>
  </div>
</div>

<!-- MODAL: NUEVO USUARIO -->
<div id="user-modal-overlay" class="drawer-overlay hidden" onclick="closeUserModal()"></div>
<div id="user-modal" class="edit-drawer hidden">
  <div class="drawer-header">
    <span>👤 Nuevo Usuario</span>
    <button class="btn btn-ghost btn-sm" onclick="closeUserModal()">✕</button>
  </div>
  <div class="drawer-body">
    <div class="field-group">
      <label class="field-label">Nombre de Usuario</label>
      <input id="new-user-name" type="text" class="field-input" placeholder="ej: leandro">
    </div>
    <div class="field-group">
      <label class="field-label">Contraseña</label>
      <input id="new-user-pass" type="password" class="field-input" placeholder="••••••••">
    </div>
    <div class="field-group">
      <label class="field-label">Rol</label>
      <select id="new-user-rol" class="field-input">
        <option value="staff">Staff (Solo subidas)</option>
        <option value="admin">Admin (Todo el control)</option>
      </select>
    </div>
    <button class="btn btn-primary" style="width:100%; margin-top:1rem" onclick="guardarNuevoUsuario()">
      Crear Usuario
    </button>
  </div>
</div>

<script>
  window.csrfToken = '<?= $_SESSION['csrf_token'] ?>';

  function toggleMobileNav() {
    var nav     = document.getElementById('mobile-nav');
    var overlay = document.getElementById('mobile-nav-overlay');
    var btn     = document.getElementById('hamburger-btn');
    if (!nav.classList.contains('hidden')) {
      closeMobileNav();
    } else {
      nav.classList.remove('hidden');
      overlay.classList.remove('hidden');
      btn.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeMobileNav() {
    document.getElementById('mobile-nav').classList.add('hidden');
    document.getElementById('mobile-nav-overlay').classList.add('hidden');
    document.getElementById('hamburger-btn').classList.remove('open');
    document.body.style.overflow = '';
  }

  function mobileNavSwitch(tab) {
    if (typeof switchTab === 'function') switchTab(tab);
    closeMobileNav();
    document.querySelectorAll('.mobile-nav-item[data-tab]').forEach(function(el) {
      el.classList.toggle('active', el.dataset.tab === tab);
    });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMobileNav();
  });
</script>
<script src="assets/admin.js?v=6"></script>
<script>
// ─── PARCHES INLINE v3: índices correctos de la hoja ─────────────────────────
// Estructura de la hoja de cálculo:
//   f[0] = Marca temporal (fecha)
//   f[1] = Usuario
//   f[2] = Proyecto (nombre del manga)
//   f[3] = Etapa
//   f[4] = Capítulo
//   f[5] = URL del archivo en Drive
  // ─── DASHBOARD ───────────────────────────────────────────────────────────────
  window.anuncioActualizarMensaje = function() {
    const manga = document.getElementById('anuncio-manga').value;
    const cap   = (document.getElementById('anuncio-cap').value || '').trim();
    const link  = (document.getElementById('anuncio-link').value || '').trim();
    const ta    = document.getElementById('anuncio-mensaje');
    if (!ta) return;
    let msg = '';
    if (manga) msg += `📢 **${manga}**`;
    if (cap)   msg += ` — Capítulo ${cap}`;
    if (manga || cap) msg += ' ya disponible!';
    if (link)  msg += `\n🔗 ${link}`;
    ta.value = msg;
  };

  window.anuncioLimpiar = function() {
    ['anuncio-manga','anuncio-cap','anuncio-link','anuncio-mensaje','anuncio-resultado'].forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === 'SELECT') el.selectedIndex = 0;
      else el.value = '';
      if (id === 'anuncio-resultado') el.innerHTML = '';
    });
  };

  window.cargarDashboard = async function() {
    // Poblar dropdown de mangas
    try {
      const r = await fetch('api.php?action=proyectos');
      const res = await r.json();
      const sel = document.getElementById('anuncio-manga');
      if (sel && res.exito && res.datos) {
        const prev = sel.value;
        sel.innerHTML = '<option value="">— Seleccionar —</option>' +
          res.datos.map(n => `<option value="${n}"${n===prev?' selected':''}>${n}</option>`).join('');
      }
    } catch(e) {}

    // Stats
    try {
      const r = await fetch('api.php?action=dashboardStats');
      const res = await r.json();
      if (res.exito) {
        const el = id => document.getElementById(id);
        if (el('stat-proyectos'))  el('stat-proyectos').textContent  = res.data.proyectos_activos;
        if (el('stat-disponible')) el('stat-disponible').textContent = res.data.staff_disponible;
        if (el('stat-tareas'))     el('stat-tareas').textContent     = res.data.tareas_activas;
        const atEl = el('stat-atrasados');
        if (atEl) {
          atEl.textContent = res.data.atrasados;
          atEl.style.color = res.data.atrasados > 0 ? '#ff5555' : '';
        }
        const subEl = el('stat-atrasados-sub');
        if (subEl) subEl.style.color = res.data.atrasados > 0 ? '#ff5555' : '';
      }
    } catch(e) { console.error('dashboardStats:', e); }

    // Staff disponible
    try {
      const r = await fetch('api.php?action=staffDisponible');
      const res = await r.json();
      const cont = document.getElementById('dash-disponible');
      if (!cont) return;
      if (!res.exito) {
        cont.innerHTML = `<div class="empty-msg" style="color:var(--red-bright)">Error: ${res.mensaje}</div>`;
      } else if (!res.data || !res.data.length) {
        cont.innerHTML = '<div class="empty-msg">Todo el staff tiene tareas activas.</div>';
      } else {
        cont.innerHTML = res.data.map(s => `
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
            <div style="width:34px;height:34px;border-radius:50%;background:rgba(16,185,129,.15);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#10b981;flex-shrink:0">${(s.nombre_display||'?')[0].toUpperCase()}</div>
            <div style="flex:1;min-width:0">
              <div style="font-size:.85rem;font-weight:600">${s.nombre_display}</div>
              <div style="font-size:.71rem;color:var(--muted)">${s.rol || 'Staff'}</div>
            </div>
            <span style="font-size:.68rem;padding:2px 7px;border-radius:5px;background:rgba(16,185,129,.12);color:#10b981">Libre</span>
          </div>
        `).join('');
      }
    } catch(e) { console.error('staffDisponible:', e); }

    // Atrasados / inactivos
    try {
      const r = await fetch('api.php?action=staffAtrasados');
      const res = await r.json();
      const cont = document.getElementById('dash-atrasados');
      if (!cont) return;
      if (!res.exito) {
        cont.innerHTML = `<div class="empty-msg" style="color:var(--red-bright)">Error: ${res.mensaje}</div>`;
        return;
      }
      let html = '';
      if (res.atrasadas && res.atrasadas.length) {
        html += '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:6px">Tareas atrasadas</div>';
        html += res.atrasadas.map(t => `
          <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border)">
            <div style="width:8px;height:8px;border-radius:50%;background:#ff5555;flex-shrink:0"></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:.83rem;font-weight:600">${t.nombre_display}</div>
              <div style="font-size:.71rem;color:var(--muted)">${t.obra} cap.${t.cap} · ${t.rol}</div>
            </div>
            <span style="font-size:.68rem;color:#ff5555;white-space:nowrap">${t.horas_atraso}h atraso</span>
          </div>
        `).join('');
      }
      if (res.inactivos && res.inactivos.length) {
        html += `<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-top:12px;margin-bottom:6px">Sin actividad esta semana</div>`;
        html += res.inactivos.map(s => `
          <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border)">
            <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;flex-shrink:0"></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:.83rem;font-weight:600">${s.nombre_display}</div>
              <div style="font-size:.71rem;color:var(--muted)">${s.rol||'Staff'}</div>
            </div>
            <span style="font-size:.68rem;color:#f59e0b;white-space:nowrap">Inactivo</span>
          </div>
        `).join('');
      }
      if (!html) html = '<div class="empty-msg">Sin retrasos ni inactividad.</div>';
      cont.innerHTML = html;
    } catch(e) { console.error('staffAtrasados:', e); }
  };

  window.anunciarSubida = async function() {
    const mensaje  = (document.getElementById('anuncio-mensaje').value || '').trim();
    const link     = (document.getElementById('anuncio-link').value || '').trim();
    const discord  = document.getElementById('anuncio-discord').checked;
    const telegram = document.getElementById('anuncio-telegram').checked;
    const resEl    = document.getElementById('anuncio-resultado');
    const btn      = document.getElementById('btn-anunciar');
    if (!mensaje)              { toast('Completa el mensaje antes de publicar', 'err'); return; }
    if (!discord && !telegram) { toast('Selecciona al menos una plataforma', 'err'); return; }
    btn.disabled = true; btn.textContent = '…'; resEl.textContent = '';
    try {
      const fd = new FormData();
      fd.append('csrf_token', window.csrfToken);
      fd.append('mensaje', mensaje);
      fd.append('link', link);
      if (discord)  fd.append('discord',  '1');
      if (telegram) fd.append('telegram', '1');
      const r   = await fetch('api.php?action=anunciarSubida', { method:'POST', body:fd });
      const res = await r.json();
      if (res.exito) {
        const msgs = [];
        if (res.resultados.discord  === true)  msgs.push('Discord ✓');
        if (res.resultados.discord  === false) msgs.push('Discord ✗ (webhook no configurado)');
        if (res.resultados.telegram === true)  msgs.push('Telegram ✓');
        if (res.resultados.telegram === false) msgs.push('Telegram ✗ (token no configurado)');
        resEl.innerHTML = `<span style="color:#10b981">${msgs.join('  &nbsp;')}</span>`;
        toast('Anuncio publicado');
      } else {
        resEl.innerHTML = `<span style="color:#ff5555">${res.mensaje || 'Error'}</span>`;
        toast(res.mensaje || 'Error al anunciar', 'err');
      }
    } catch(e) {
      resEl.innerHTML = '<span style="color:#ff5555">Error de conexión</span>';
      toast('Error de conexión: ' + e.message, 'err');
    }
    btn.disabled = false; btn.textContent = 'Publicar';
  };

(function() {
  window.vpProyectosMap = {};
  window.vpProyectosDriveMap = {};
  window.todosProyectos = [];

  window.cargarProyectos = async function() {
    try {
      const req = await fetch('api.php?action=listarProyectosAdmin');
      const res = await req.json();
      if(res && res.exito) {
        todosProyectos = res.datos;
        res.datos.forEach(p => {
          vpProyectosMap[p.id] = p.nombre;
          vpProyectosDriveMap[p.id] = p.carpeta_drive_id || '';
        });
        renderAcordeonProyectos();
      }
    } catch(e) {
      console.error(e);
    }
  };

  // ─── Acordeón de proyectos ───────────────────────────────────────────────
  const ETAPAS_LABEL = { estado_raw:'RAW', estado_trad:'Trad', estado_clean:'Clean', estado_type:'Type', estado_proof:'Proof' };
  const ETAPAS_KEYS  = Object.keys(ETAPAS_LABEL);

  function calcProgreso(c) {
    return ETAPAS_KEYS.reduce((s, k) => s + (parseInt(c[k]) ? 1 : 0), 0);
  }

  function renderBarra(hecho, total) {
    const pct = Math.round((hecho / total) * 100);
    const color = pct === 100 ? '#10b981' : pct >= 60 ? '#f59e0b' : pct >= 20 ? '#dc2020' : '#6e6e82';
    return `<div style="display:flex;align-items:center;gap:8px;min-width:180px">
      <div style="flex:1;background:rgba(255,255,255,.08);border-radius:4px;height:7px;overflow:hidden;min-width:80px">
        <div style="height:100%;width:${pct}%;background:${color};border-radius:4px;transition:width .4s"></div>
      </div>
      <span style="font-size:.76rem;font-weight:700;color:${color};white-space:nowrap">${hecho}/${total}</span>
      <span style="font-size:.72rem;color:${pct===100?'#10b981':'var(--muted)'};white-space:nowrap">${pct}%</span>
    </div>`;
  }

  function renderEtapasChips(c, pId) {
    return ETAPAS_KEYS.map(k => {
      const ok = parseInt(c[k]);
      const lbl = ETAPAS_LABEL[k];
      return `<span onclick="toggleEstadoCap(${c.id},'${k}',${c[k]},${pId})" title="Click para cambiar"
        style="cursor:pointer;display:inline-flex;align-items:center;gap:3px;padding:2px 8px;
               border-radius:6px;font-size:.72rem;font-weight:600;
               background:${ok?'rgba(16,185,129,.15)':'rgba(255,255,255,.05)'};
               border:1px solid ${ok?'#10b981':'rgba(255,255,255,.1)'};
               color:${ok?'#10b981':'#6e6e82'}">${ok?'✓':'✗'} ${lbl}</span>`;
    }).join('');
  }

  window.renderAcordeonProyectos = function() {
    const body = document.getElementById('acordeon-proyectos');
    if (!body) return;
    if (!todosProyectos.length) {
      body.innerHTML = '<p style="color:var(--muted);padding:1.5rem">No hay proyectos.</p>';
      return;
    }
    body.innerHTML = todosProyectos.map(p => {
      const activo  = p.estado === 'activo';
      const driveId = vpProyectosDriveMap[p.id] || '';
      const driveBtn = driveId
        ? `<a href="https://drive.google.com/drive/folders/${driveId}" target="_blank" class="btn btn-ghost btn-sm" title="Abrir en Drive">📂</a>`
        : `<button class="btn btn-ghost btn-sm" onclick="autoDetectarDrive(${p.id},this)" title="Buscar carpeta en Drive">🔍 Auto</button>`;
      return `
        <div style="border-bottom:1px solid var(--border)">
          <div onclick="toggleAcordeon(${p.id})" style="display:flex;align-items:center;gap:.6rem;padding:.8rem .75rem;cursor:pointer;user-select:none">
            <span id="proy-arrow-${p.id}" style="font-size:.65rem;color:var(--muted);transition:transform .2s;display:inline-block;width:10px">▶</span>
            <span id="proy-nombre-${p.id}" style="flex:1;font-weight:700;font-size:.92rem;${activo?'':'color:var(--muted);text-decoration:line-through'}">${p.nombre}</span>
            <span id="proy-badge-${p.id}" style="font-size:.7rem;padding:2px 8px;border-radius:6px;background:${activo?'rgba(16,185,129,.15)':'rgba(220,32,32,.1)'};color:${activo?'#10b981':'#ff5555'}">${activo?'Activo':'Inactivo'}</span>
            <span id="drive-status-${p.id}" style="font-size:.72rem;color:${driveId?'#10b981':'var(--muted)'}">${driveId?'✓ Drive':'Sin Drive'}</span>
            <div onclick="event.stopPropagation()" style="display:flex;gap:4px">
              ${driveBtn}
              <button class="btn btn-ghost btn-sm" id="btn-sync-proy-${p.id}" onclick="syncTodosProyecto(${p.id},this)" title="Sync Drive todos los capítulos">⚡</button>
              <button class="btn btn-primary btn-sm" onclick="abrirModalNuevoCapitulo(${p.id})">+</button>
              <button class="btn btn-ghost btn-sm" onclick="toggleProyecto(${p.id},this)">${activo?'Off':'On'}</button>
            </div>
          </div>
          <div id="proy-body-${p.id}" style="display:none;background:rgba(255,255,255,.02)">
            <p style="color:var(--muted);padding:1rem;font-size:.83rem">Cargando…</p>
          </div>
        </div>`;
    }).join('');
  };

  window.toggleAcordeon = async function(id) {
    const body  = document.getElementById('proy-body-' + id);
    const arrow = document.getElementById('proy-arrow-' + id);
    if (!body) return;
    if (body.style.display === 'none') {
      body.style.display = '';
      arrow.style.transform = 'rotate(90deg)';
      await cargarCapitulosProyecto(id);
    } else {
      body.style.display = 'none';
      arrow.style.transform = '';
    }
  };

  window.cargarCapitulosProyecto = async function(pId) {
    const body = document.getElementById('proy-body-' + pId);
    if (!body) return;
    body.innerHTML = '<p style="color:var(--muted);padding:1rem;font-size:.83rem"><span class="spinner"></span></p>';
    try {
      const r   = await fetch('api.php?action=listarCapitulos&proyecto_id=' + pId);
      const res = await r.json();
      if (!res.exito || !res.datos.length) {
        body.innerHTML = '<p style="color:var(--muted);padding:.75rem 1rem;font-size:.83rem">Sin capítulos. Usa + para agregar.</p>';
        return;
      }
      body.innerHTML = `<div style="overflow-x:auto"><table class="data-table" style="margin:0;font-size:.83rem">
        <thead><tr>
          <th style="width:55px;text-align:center">Cap.</th>
          <th style="min-width:200px">Progreso</th>
          <th>Etapas</th>
          <th style="width:90px;text-align:center">Estado</th>
          <th style="width:70px;text-align:center">Sync</th>
        </tr></thead>
        <tbody>
        ${res.datos.map(c => {
          const hecho = calcProgreso(c);
          const total = ETAPAS_KEYS.length;
          const estadoGen  = c.estado_general || 'Pendiente';
          const badgeClass = estadoGen === 'Publicado' ? 'success' : estadoGen === 'Retrasado' ? 'danger' : 'warning';
          const isReady    = hecho === total && estadoGen !== 'Publicado';
          return `<tr>
            <td style="font-weight:bold;text-align:center">${c.numero}${isReady?` <button class="btn btn-primary" style="font-size:.65rem;padding:2px 6px;margin-top:3px" onclick="publicarCapitulo(${c.id},${pId})">Pub.</button>`:''}</td>
            <td>${renderBarra(hecho, total)}</td>
            <td style="white-space:nowrap">${renderEtapasChips(c, pId)}</td>
            <td style="text-align:center"><span class="badge ${badgeClass}" style="font-size:.68rem">${estadoGen}</span></td>
            <td style="text-align:center">
              <button class="btn btn-ghost btn-sm" id="btn-drive-${c.id}" onclick="verificarDriveAcordeon(${c.id},${pId},${c.numero})" title="Sync Drive este capítulo">⚡</button>
            </td>
          </tr>
          <tr id="drive-result-row-${c.id}" style="display:none">
            <td></td><td colspan="4"><div id="drive-result-${c.id}" style="font-size:.75rem;padding:.3rem 0"></div></td>
          </tr>`;
        }).join('')}
        </tbody>
      </table></div>`;
    } catch(e) {
      body.innerHTML = `<p style="color:#ff5555;padding:.75rem 1rem;font-size:.83rem">Error: ${e.message}</p>`;
    }
  };

  window.toggleProyecto = async function(id, btn) {
    const p = todosProyectos.find(x => x.id == id);
    const textoOriginal = btn.textContent;
    btn.disabled = true; btn.textContent = '…';
    try {
      const fd = new FormData();
      fd.append('csrf_token', window.csrfToken);
      fd.append('id', id);
      const r = await fetch('api.php?action=toggleEstadoProyecto', { method:'POST', body:fd });
      const res = await r.json();
      if (res.exito) {
        if (p) p.estado = res.estado;
        const activo = res.estado === 'activo';
        const badge = document.getElementById('proy-badge-' + id);
        if (badge) {
          badge.textContent = activo ? 'Activo' : 'Inactivo';
          badge.style.background = activo ? 'rgba(16,185,129,.15)' : 'rgba(220,32,32,.1)';
          badge.style.color = activo ? '#10b981' : '#ff5555';
        }
        const nombre = document.getElementById('proy-nombre-' + id);
        if (nombre) {
          nombre.style.color = activo ? '' : 'var(--muted)';
          nombre.style.textDecoration = activo ? '' : 'line-through';
        }
        btn.textContent = activo ? 'Off' : 'On';
        btn.disabled = false;
        toast(activo ? 'Proyecto activado' : 'Proyecto desactivado');
      } else {
        btn.textContent = textoOriginal; btn.disabled = false;
        toast(res.mensaje || 'Error al cambiar estado', 'err');
      }
    } catch(e) {
      btn.textContent = textoOriginal; btn.disabled = false;
      toast('Error de conexión: ' + e.message, 'err');
    }
  };

  window.autoDetectarDrive = async function(id, btn) {
    btn.disabled = true; btn.textContent = '⏳';
    const fd = new FormData();
    fd.append('csrf_token', window.csrfToken);
    fd.append('id', id);
    const res = await fetch('api.php?action=autoDetectarDriveId', { method:'POST', body:fd }).then(r=>r.json());
    if (res.exito) {
      vpProyectosDriveMap[id] = res.drive_id;
      const p = todosProyectos.find(x => x.id == id);
      if (p) p.carpeta_drive_id = res.drive_id;
      renderAcordeonProyectos();
    } else {
      btn.disabled = false; btn.textContent = '🔍 Auto';
      const st = document.getElementById('drive-status-' + id);
      if (st) { st.textContent = '✗ No encontrado'; st.style.color = '#ff5555'; }
    }
  };

  window.syncTodosProyecto = async function(pId, btn) {
    const driveId = vpProyectosDriveMap[pId];
    if (!driveId) { alert('Sin Drive vinculado. Usa 🔍 Auto primero.'); return; }
    if (btn) { btn.disabled = true; }
    let caps;
    try {
      const r = await fetch('api.php?action=listarCapitulos&proyecto_id=' + pId);
      const d = await r.json();
      if (!d.exito || !d.datos.length) { if (btn) { btn.disabled = false; } return; }
      caps = d.datos;
    } catch(e) { if (btn) btn.disabled = false; return; }
    let total = 0;
    for (let i = 0; i < caps.length; i++) {
      if (btn) btn.textContent = `${i+1}/${caps.length}`;
      try {
        const r = await fetch(`api.php?action=verificarDriveCapitulo&proyecto_id=${pId}&capitulo_id=${caps[i].id}&capitulo_num=${caps[i].numero}&sync=1`);
        const res = await r.json();
        if (res.exito) total += (res.actualizados || 0);
      } catch(e) {}
    }
    if (btn) { btn.disabled = false; btn.textContent = '⚡'; }
    const body = document.getElementById('proy-body-' + pId);
    if (body && body.style.display !== 'none') await cargarCapitulosProyecto(pId);
    if (total > 0) {
      const t = document.createElement('div');
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:10px 18px;border-radius:8px;font-size:.85rem;font-weight:600;z-index:9999';
      t.textContent = `✓ ${total} etapa(s) sincronizadas`;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3500);
    }
  };

  window.verificarDriveAcordeon = async function(capId, proyId, capNum) {
    const btn    = document.getElementById('btn-drive-' + capId);
    const resDiv = document.getElementById('drive-result-' + capId);
    const resRow = document.getElementById('drive-result-row-' + capId);
    btn.disabled = true; btn.textContent = '⏳';
    if (resRow) resRow.style.display = '';
    if (resDiv) resDiv.innerHTML = '<span style="color:var(--muted)">Buscando en Drive…</span>';
    try {
      const r   = await fetch(`api.php?action=verificarDriveCapitulo&proyecto_id=${proyId}&capitulo_id=${capId}&capitulo_num=${capNum}&sync=1`);
      const res = await r.json();
      if (!res.exito) { if (resDiv) resDiv.innerHTML = `<span style="color:#dc2020">⚠ ${res.mensaje}</span>`; return; }
      const EN = { raw:'RAW', trad:'Trad', clean:'Clean', type:'Type', proof:'QC' };
      const chips = Object.entries(res.etapas).map(([k,v]) => {
        const ok = v.encontrado;
        return `<span style="padding:2px 7px;border-radius:6px;font-size:.7rem;font-weight:600;background:${ok?'rgba(16,185,129,.15)':'rgba(220,32,32,.12)'};border:1px solid ${ok?'#10b981':'#dc2020'};color:${ok?'#10b981':'#f87171'}">${ok?'✓':'✗'} ${EN[k]}</span>`;
      }).join('');
      const msg = res.actualizados > 0 ? `<span style="color:#10b981;margin-left:6px">✓ ${res.actualizados} actualizadas</span>` : `<span style="color:var(--muted);margin-left:6px">Sin cambios</span>`;
      if (resDiv) resDiv.innerHTML = `<div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center">${chips}${msg}</div>`;
      if (res.actualizados > 0) await cargarCapitulosProyecto(proyId);
    } catch(e) {
      if (resDiv) resDiv.innerHTML = `<span style="color:#dc2020">Error: ${e.message}</span>`;
    } finally {
      btn.disabled = false; btn.textContent = '⚡';
    }
  };

  window.toggleEstadoCap = async function(id, campo, valorActual, proyId) {
    const fd = new FormData();
    fd.append('id', id); fd.append('campo', campo);
    fd.append('valor', valorActual == 1 ? 0 : 1);
    fd.append('csrf_token', window.csrfToken);
    try {
      const r = await fetch('api.php?action=actualizarEstadoCapitulo', { method:'POST', body:fd });
      const res = await r.json();
      if (res.exito && proyId) await cargarCapitulosProyecto(proyId);
      else if (!res.exito) alert(res.mensaje || 'Error');
    } catch(e) { console.error(e); }
  };

  window.publicarCapitulo = async function(id, proyId) {
    if (!confirm('¿Marcar como publicado?')) return;
    const fd = new FormData();
    fd.append('id', id); fd.append('csrf_token', window.csrfToken);
    try {
      const r = await fetch('api.php?action=publicarCapitulo', { method:'POST', body:fd });
      const res = await r.json();
      if (res.exito && proyId) await cargarCapitulosProyecto(proyId);
    } catch(e) { console.error(e); }
  };

  window.abrirModalNuevoCapitulo = function(proyId) {
    if (!proyId) return alert('Proyecto no seleccionado.');
    document.getElementById('cap-modal-proy-id').value = proyId;
    document.getElementById('new-cap-num').value = '';
    document.getElementById('new-cap-desde').value = '';
    document.getElementById('new-cap-hasta').value = '';
    document.getElementById('cap-modal-resultado').textContent = '';
    setCapModo('uno');
    document.getElementById('cap-modal-overlay').classList.remove('hidden');
    document.getElementById('cap-modal').classList.remove('hidden');
  };

  window.cerrarModalNuevoCapitulo = function() {
    document.getElementById('cap-modal-overlay').classList.add('hidden');
    document.getElementById('cap-modal').classList.add('hidden');
  };

  window.setCapModo = function(modo) {
    const esRango = modo === 'rango';
    document.getElementById('cap-form-uno').style.display   = esRango ? 'none' : '';
    document.getElementById('cap-form-rango').style.display = esRango ? '' : 'none';
    document.getElementById('cap-modo-uno').className   = 'btn btn-sm ' + (esRango ? 'btn-ghost' : 'btn-primary');
    document.getElementById('cap-modo-rango').className  = 'btn btn-sm ' + (esRango ? 'btn-primary' : 'btn-ghost');
  };

  window.guardarNuevoCapitulo = async function() {
    const pId    = parseInt(document.getElementById('cap-modal-proy-id').value);
    const esRango = document.getElementById('cap-form-rango').style.display !== 'none';
    const autoSync = document.getElementById('cap-auto-sync').checked;
    const resDiv  = document.getElementById('cap-modal-resultado');
    if (!pId) return;
    resDiv.textContent = '';
    let ok = false;

    if (esRango) {
      const desde = parseInt(document.getElementById('new-cap-desde').value);
      const hasta = parseInt(document.getElementById('new-cap-hasta').value);
      if (!desde || !hasta || desde > hasta || (hasta - desde) > 200) {
        resDiv.style.color = '#ff5555'; resDiv.textContent = 'Rango inválido (máx. 200 caps).'; return;
      }
      const fd = new FormData();
      fd.append('proyecto_id', pId); fd.append('desde', desde); fd.append('hasta', hasta);
      fd.append('csrf_token', window.csrfToken);
      const res = await fetch('api.php?action=crearCapitulosRango', { method:'POST', body:fd }).then(r=>r.json());
      if (!res.exito) { resDiv.style.color='#ff5555'; resDiv.textContent = res.mensaje || 'Error'; return; }
      resDiv.style.color = '#10b981';
      resDiv.textContent = `✓ ${res.creados} capítulo(s) creados (${res.omitidos} ya existían)`;
      ok = true;
    } else {
      const num = document.getElementById('new-cap-num').value;
      if (!num) return;
      const fd = new FormData();
      fd.append('proyecto_id', pId); fd.append('numero', num);
      fd.append('csrf_token', window.csrfToken);
      const res = await fetch('api.php?action=crearCapitulo', { method:'POST', body:fd }).then(r=>r.json());
      if (!res.exito) { resDiv.style.color='#ff5555'; resDiv.textContent = res.mensaje || 'Error'; return; }
      resDiv.style.color = '#10b981'; resDiv.textContent = '✓ Capítulo creado.';
      ok = true;
    }

    if (ok) {
      cerrarModalNuevoCapitulo();
      // Abrir acordeón del proyecto y recargar
      const body  = document.getElementById('proy-body-' + pId);
      const arrow = document.getElementById('proy-arrow-' + pId);
      if (body) { body.style.display = ''; if (arrow) arrow.style.transform = 'rotate(90deg)'; }
      await cargarCapitulosProyecto(pId);
      if (autoSync && vpProyectosDriveMap[pId]) {
        await syncTodosProyecto(pId, document.getElementById('btn-sync-proy-' + pId));
      }
    }
  };

  window.cargarHistorialFull = async function() {
    const tbody = document.getElementById('historial-full-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem"><span class="spinner"></span> Cargando…</td></tr>';
    let historial = [];
    try {
      const r = await fetch('api.php?action=historial');
      const res = await r.json();
      if (res && res.exito && res.datos) historial = res.datos;
    } catch(e) { console.error('cargarHistorialFull:', e); }
    if (!historial.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="empty-msg">No hay registros.</td></tr>';
      return;
    }
    tbody.innerHTML = historial.map(function(f, i) {
      var urlArchivo = f[5] ? '<a href="' + f[5] + '" target="_blank" style="color:var(--red-bright);text-decoration:none" title="Ver archivo">🔗</a>' : '';
      return [
        '<tr id="hrow-' + i + '">',
          '<td style="font-weight:600">' + (f[2] || '—') + '</td>',
          '<td>' + (f[4] || '—') + '</td>',
          '<td>' + (f[3] || '—') + '</td>',
          '<td style="color:var(--muted)">' + (f[0] || '—').toString().substring(0,16) + '</td>',
          '<td>' + (f[1] || '—') + ' ' + urlArchivo + '</td>',
          '<td class="actions-cell">',
            '<button class="act-btn" onclick="openEditModal(' + i + ')">✎ Editar</button>',
          '</td>',
        '</tr>'
      ].join('');
    }).join('');
  };

  window.filtrarHistorial = function(termino) {
    termino = termino.toLowerCase();
    const rows = document.querySelectorAll('#historial-full-body tr');
    rows.forEach(row => {
      if (row.querySelector('.empty-msg') || row.querySelector('.loading-cell')) return;
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(termino) ? '' : 'none';
    });
  };

  window.cargarTareasAdmin = async function() {
    const tbody = document.getElementById('tareas-admin-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="loading-cell"><span class="spinner"></span></td></tr>';
    
    try {
      const res = await (await fetch('api.php?action=getTodasTareas')).json();
      if (!res.exito) { tbody.innerHTML = '<tr><td colspan="6" class="empty-msg" style="color:red">Error al cargar tareas</td></tr>'; return; }
      if (!res.datos.length) { tbody.innerHTML = '<tr><td colspan="6" class="empty-msg">No hay tareas activas en este momento.</td></tr>'; return; }
      
      const ahora = Date.now();
      tbody.innerHTML = res.datos.map(t => {
        const d = new Date(t.limite);
        const diff = (d - ahora) / 3600000;
        const colorLim = diff < 0 ? 'var(--red)' : diff <= 24 ? 'var(--orange)' : 'var(--text)';
        const limStr = diff < 0 ? `Vencida hace ${Math.abs(Math.round(diff))}h` : `En ${Math.round(diff)}h`;
        
        const extHTML = parseInt(t.extension_solicitada) === 1 
          ? `<button class="act-btn" style="color:var(--orange); border-color:var(--orange);" onclick="aprobarExtension(${t.id})">Aprobar Extensión</button>`
          : '';

        return `<tr>
          <td><strong style="color:var(--red-bright)">${t.obra}</strong></td>
          <td>Cap #${t.cap}</td>
          <td>${t.nombre_display || t.discord_id}</td>
          <td><span style="font-size:0.75rem; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px;">${t.rol}</span></td>
          <td style="color:${colorLim}">${limStr}</td>
          <td class="actions-cell">${extHTML}</td>
        </tr>`;
      }).join('');
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="6" class="empty-msg" style="color:red">Error de red</td></tr>';
    }
  };

  window.aprobarExtension = async function(id) {
    const dias = prompt("¿Cuántos días extra quieres darle a esta tarea?", "2");
    if (!dias) return;
    const fd = new FormData(); fd.append('tarea_id', id); fd.append('dias', dias); fd.append('csrf_token', window.csrfToken);
    const res = await (await fetch('api.php?action=adminAprobarExtension', {method:'POST', body:fd})).json();
    alert(res.mensaje);
    if (res.exito) cargarTareasAdmin();
  };

  window.penalizarVencidas = async function() {
    if (!confirm("¿Revisar todas las tareas activas y descontar 1 punto a las que estén vencidas?")) return;
    const fd = new FormData(); fd.append('csrf_token', window.csrfToken);
    const res = await (await fetch('api.php?action=adminPenalizarVencidas', {method:'POST', body:fd})).json();
    alert(res.mensaje);
    if (res.exito) cargarTareasAdmin();
  };

  window.cargarConfig = async function() {
    try {
      const res = await (await fetch('api.php?action=getConfigSistema')).json();
      if (res.exito && res.config) {
        if (document.getElementById('cfg-discord-subidas')) document.getElementById('cfg-discord-subidas').value = res.config.discord_webhook_subidas || '';
        if (document.getElementById('cfg-discord-anuncios')) document.getElementById('cfg-discord-anuncios').value = res.config.discord_webhook_anuncios || '';
        if (document.getElementById('cfg-telegram-token')) document.getElementById('cfg-telegram-token').value = res.config.telegram_token || '';
        if (document.getElementById('cfg-telegram-chat')) document.getElementById('cfg-telegram-chat').value = res.config.telegram_chat_id || '';
      }
    } catch(e) { console.error(e); }
  };

  window.guardarConfig = async function(clave, valor, btn) {
    if (btn) {
      btn.disabled = true;
      btn.dataset.originalText = btn.textContent;
      btn.textContent = 'Guardando...';
    }
    const fd = new FormData();
    fd.append('clave', clave);
    fd.append('valor', valor);
    fd.append('csrf_token', window.csrfToken);
    try {
      const res = await (await fetch('api.php?action=setConfigSistema', {method:'POST', body:fd})).json();
      if (res.exito) {
        toast('Configuración guardada correctamente.');
      } else {
        toast('Error al guardar: ' + res.mensaje, 'err');
      }
    } catch(e) { 
      console.error("Error en guardarConfig:", e);
      toast('Error de red al guardar.', 'err'); 
    }
    if (btn) {
      btn.disabled = false;
      btn.textContent = btn.dataset.originalText;
    }
  };

  window.probarWebhook = async function(inputId, tipo) {
    const webhookUrl = document.getElementById(inputId).value;
    if (!webhookUrl) return toast('Por favor, ingresa una URL primero.', 'err');
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '...';
    try {
      const fd = new FormData();
      fd.append('url', webhookUrl);
      fd.append('tipo', tipo);
      fd.append('csrf_token', window.csrfToken);
      const res = await (await fetch('api.php?action=probarWebhook', {method:'POST', body:fd})).json();
      toast(res.mensaje, res.exito ? 'ok' : 'err');
    } catch(e) { toast('Error al enviar la prueba.', 'err'); }
    btn.disabled = false;
    btn.textContent = 'Probar';
  };

  // Cargar llamadas iniciales adicionales
  cargarTareasAdmin();
  cargarConfig();

})();
</script>

</body>
</html>
