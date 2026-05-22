<?php require_once 'auth.php'; ?>
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
      <?php endif; ?>
      <a class="htab" href="subir.php">↑ Subir</a>
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
      <a href="settings.php" class="btn btn-ghost btn-sm" title="Configuración" style="text-decoration:none">⚙</a>
      <?php endif; ?>
      <a href="logout.php" class="user-logout" title="Cerrar sesión" style="text-decoration:none">⎋ Salir</a>
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
          <p class="page-sub">Flujo de Producción</p>
          <h1 class="page-title">Vista de <span>Proyectos</span></h1>
        </div>
        <div>
          <select id="vp-proyecto-select" class="field-input" style="display:inline-block; width:200px; margin-right:10px;" onchange="cargarVistaCapitulos()">
            <option value="">Cargando proyectos...</option>
          </select>
          <button class="btn btn-ghost btn-sm" onclick="cargarVistaCapitulos()" style="margin-right:6px">↺ Refrescar</button>
          <button class="btn btn-ghost btn-sm" id="btn-sync-all" onclick="syncTodosCapitulos()" title="Sincroniza todas las barras de progreso con Drive">⚡ Sync All</button>
        </div>
      </div>
      <div class="panel">
        <div class="panel-header" style="justify-content: space-between; display: flex;">
          <div class="panel-title" id="vp-proyecto-titulo">◎ Selecciona un proyecto</div>
          <button class="btn btn-primary btn-sm" onclick="abrirModalNuevoCapitulo()">+ Añadir Capítulo</button>
        </div>
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:70px;text-align:center;">Cap.</th>
                <th>Progreso</th>
                <th style="width:130px;text-align:center;">Estado</th>
                <th style="width:160px;text-align:center;">Acciones</th>
              </tr>
            </thead>
            <tbody id="vp-capitulos-body">
              <tr><td colspan="4" class="empty-msg">Selecciona un proyecto para ver sus capítulos.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Gestión de proyectos -->
      <div class="panel" style="margin-top:1.5rem">
        <div class="panel-header">
          <div class="panel-title">⚙ Gestión de Proyectos</div>
        </div>
        <div class="panel-body" id="gestion-proyectos-body">
          <p style="color:var(--muted);font-size:.85rem">Cargando...</p>
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
    <?php endif; ?>

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
    <span>📑 Añadir Capítulo</span>
    <button class="btn btn-ghost btn-sm" onclick="cerrarModalNuevoCapitulo()">✕</button>
  </div>
  <div class="drawer-body">
    <div class="field-group">
      <label class="field-label">Número de Capítulo</label>
      <input id="new-cap-num" type="number" step="0.1" class="field-input" placeholder="Ej: 15">
    </div>
    <button class="btn btn-primary" style="width:100%; margin-top:1rem" onclick="guardarNuevoCapitulo()">
      Añadir Capítulo
    </button>
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
</script>
<script src="assets/admin.js?v=4"></script>
<script>
// ─── PARCHES INLINE v3: índices correctos de la hoja ─────────────────────────
// Estructura de la hoja de cálculo:
//   f[0] = Marca temporal (fecha)
//   f[1] = Usuario
//   f[2] = Proyecto (nombre del manga)
//   f[3] = Etapa
//   f[4] = Capítulo
//   f[5] = URL del archivo en Drive
(function() {
  var _historial = [];

  async function fetchHistorial() {
    try {
      const r = await fetch('api.php?action=historial');
      const res = await r.json();
      if (res && res.exito && res.datos) {
        _historial = res.datos;
        if (window.state) window.state.historial = res.datos;
        // Actualizar stats del dashboard
        _actualizarStats(res.datos);
        return res.datos;
      }
    } catch(e) { console.error('fetchHistorial:', e); }
    return [];
  }

  function _actualizarStats(historial) {
    const hoyStr = new Date().toLocaleDateString('es-ES', {day:'2-digit',month:'2-digit',year:'numeric'});
    const hoyCount = historial.filter(function(f){ return f[0] && f[0].toString().includes(hoyStr.substring(0,5)); }).length;
    const rawsCount = historial.filter(function(f){ return f[3] && f[3].includes('RAWs'); }).length;
    const el = function(id){ return document.getElementById(id); };
    if(el('stat-total')) el('stat-total').textContent = historial.length;
    if(el('stat-hoy'))   el('stat-hoy').textContent   = hoyCount;
    if(el('stat-raws'))  el('stat-raws').textContent  = rawsCount;
    // Actividad reciente en dashboard
    _renderActividad(historial.slice(0, 8));
  }

  function _renderActividad(filas) {
    var cont = document.getElementById('actividad-mini');
    if (!cont) return;
    if (!filas.length) { cont.innerHTML = '<div class="empty-msg">Sin actividad reciente</div>'; return; }
    var colores = {'01':'#ef4444','02':'#3b82f6','03':'#8b5cf6','04':'#f59e0b','05':'#10b981'};
    cont.innerHTML = filas.map(function(f) {
      var etapaKey = (f[3] || '').substring(0, 2);
      var color = colores[etapaKey] || 'var(--muted)';
      return [
        '<div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">',
          '<div style="width:8px;height:8px;border-radius:50%;background:' + color + ';flex-shrink:0"></div>',
          '<div style="flex:1;min-width:0">',
            '<div style="font-size:.84rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">',
              (f[2] || '—') + ' <span style="color:var(--red-bright)">cap. ' + (f[4] || '?') + '</span>',
            '</div>',
            '<div style="font-size:.7rem;color:var(--muted);margin-top:2px">' + (f[3] || '—') + ' · ' + (f[1] || '') + '</div>',
          '</div>',
          '<div style="font-size:.7rem;color:var(--muted);white-space:nowrap">' + (f[0] || '').toString().substring(0,10) + '</div>',
        '</div>'
      ].join('');
    }).join('');
    if (cont.lastElementChild) cont.lastElementChild.style.borderBottom = 'none';
  }

  // Estadísticas por manga — usando f[2]=proyecto, f[4]=cap
  function statsParaManga(nombre, historial) {
    var filas = historial.filter(function(f) { return f[2] === nombre; });
    var caps = filas.map(function(f) { return parseFloat(f[4]); }).filter(function(n) { return !isNaN(n); });
    return {
      total: filas.length,
      minCap: caps.length ? Math.min.apply(null, caps) : '—',
      maxCap: caps.length ? Math.max.apply(null, caps) : '—',
      ultima: filas.length ? (filas[0][0] || '').toString().substring(0,10) : '—',
      ultimoUsuario: filas.length ? (filas[0][1] || '') : ''
    };
  }

  function renderTarjeta(nombre, s) {
    var capRango = s.total > 0
      ? 'Cap. <b style="color:#dc2020">' + s.minCap + '</b> → <b style="color:#dc2020">' + s.maxCap + '</b>'
      : '<span style="color:var(--muted)">Sin registros aún</span>';
    return [
      '<div class="project-card" style="display:flex;flex-direction:column;gap:10px">',
        '<div style="display:flex;align-items:center;gap:10px">',
          '<div class="project-icon">📖</div>',
          '<div class="project-name" style="font-size:.88rem;line-height:1.3">' + nombre + '</div>',
        '</div>',
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">',
          '<div style="background:rgba(255,255,255,.04);border-radius:8px;padding:8px 10px">',
            '<div style="font-size:.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:3px">Rango caps.</div>',
            '<div style="font-size:.82rem">' + capRango + '</div>',
          '</div>',
          '<div style="background:rgba(255,255,255,.04);border-radius:8px;padding:8px 10px">',
            '<div style="font-size:.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:2px;margin-bottom:3px">Subidas</div>',
            '<div style="font-size:.82rem;font-weight:700">' + s.total + '</div>',
          '</div>',
        '</div>',
        s.ultima !== '—' ? '<div style="font-size:.7rem;color:var(--muted)">Última: ' + s.ultima + (s.ultimoUsuario ? ' · ' + s.ultimoUsuario : '') + '</div>' : '',
        '<div class="project-actions">',
          '<button class="act-btn" onclick="window.open(\'index.php?proyecto=' + encodeURIComponent(nombre) + '\',\'_blank\')">🔍 Buscador</button>',
        '</div>',
      '</div>'
    ].join('');
  }

  window.vpProyectosMap = {};
  window.vpProyectosDriveMap = {};
  window.todosProyectos = [];

  window.cargarProyectos = async function() {
    try {
      const req = await fetch('api.php?action=listarProyectosAdmin');
      const res = await req.json();
      if(res && res.exito) {
        todosProyectos = res.datos;
        const sel = document.getElementById('vp-proyecto-select');
        sel.innerHTML = '<option value="">— Seleccionar Proyecto —</option>';
        res.datos.forEach(p => {
          vpProyectosMap[p.id] = p.nombre;
          vpProyectosDriveMap[p.id] = p.carpeta_drive_id || '';
          if(p.estado === 'activo') {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.nombre;
            sel.appendChild(opt);
          }
        });
        renderGestionProyectos();
      }
    } catch(e) {
      console.error(e);
    }
  };

  // ─── Gestión de proyectos (activar/desactivar, vincular Drive) ──────────
  window.renderGestionProyectos = function() {
    const body = document.getElementById('gestion-proyectos-body');
    if(!body) return;
    body.innerHTML = todosProyectos.map(p => {
      const activo  = p.estado === 'activo';
      const driveId = vpProyectosDriveMap[p.id] || '';
      const driveBtn = driveId
        ? `<a href="https://drive.google.com/drive/folders/${driveId}" target="_blank" class="btn btn-ghost btn-sm" title="Abrir en Drive">📂</a>`
        : `<button class="btn btn-ghost btn-sm" onclick="autoDetectarDrive(${p.id},this)" title="Buscar carpeta en Drive automáticamente">🔍 Auto</button>`;
      return `<div style="display:flex;align-items:center;gap:.75rem;padding:.55rem 0;border-bottom:1px solid var(--border);flex-wrap:wrap">
        <span style="flex:1;min-width:140px;font-size:.88rem;${activo?'':'color:var(--muted);text-decoration:line-through'}">${p.nombre}</span>
        <span style="font-size:.72rem;padding:2px 8px;border-radius:6px;background:${activo?'rgba(16,185,129,.15)':'rgba(220,32,32,.1)'};color:${activo?'#10b981':'#ff5555'}">${activo?'Activo':'Inactivo'}</span>
        <button class="btn btn-ghost btn-sm" onclick="toggleProyecto(${p.id},this)">${activo?'Desactivar':'Activar'}</button>
        <span id="drive-status-${p.id}" style="font-size:.75rem;color:${driveId?'#10b981':'var(--muted)'}">${driveId?'✓ Drive vinculado':'Sin Drive'}</span>
        ${driveBtn}
      </div>`;
    }).join('') || '<p style="color:var(--muted)">No hay proyectos.</p>';
  };

  window.toggleProyecto = async function(id, btn) {
    btn.disabled = true; btn.textContent = '…';
    const fd = new FormData();
    fd.append('csrf_token', window.csrfToken);
    fd.append('id', id);
    const res = await fetch('api.php?action=toggleEstadoProyecto', { method:'POST', body:fd }).then(r=>r.json());
    if(res.exito) {
      const p = todosProyectos.find(x => x.id == id);
      if(p) { p.estado = res.estado; vpProyectosMap[id] = p.nombre; }
      await cargarProyectos();
    } else {
      btn.disabled = false; btn.textContent = 'Error';
    }
  };

  window.autoDetectarDrive = async function(id, btn) {
    btn.disabled = true; btn.textContent = '⏳';
    const fd = new FormData();
    fd.append('csrf_token', window.csrfToken);
    fd.append('id', id);
    const res = await fetch('api.php?action=autoDetectarDriveId', { method:'POST', body:fd }).then(r=>r.json());
    if(res.exito) {
      vpProyectosDriveMap[id] = res.drive_id;
      const p = todosProyectos.find(x => x.id == id);
      if(p) p.carpeta_drive_id = res.drive_id;
      renderGestionProyectos();
    } else {
      btn.disabled = false; btn.textContent = '🔍 Auto';
      document.getElementById('drive-status-' + id).textContent = '✗ ' + (res.mensaje || 'No encontrado');
      document.getElementById('drive-status-' + id).style.color = '#ff5555';
    }
  };

  // ─── Vista de capítulos con barra de progreso ────────────────────────────
  const ETAPAS_LABEL = { estado_raw:'RAW', estado_trad:'Trad', estado_clean:'Clean', estado_type:'Type', estado_proof:'Proof' };
  const ETAPAS_KEYS  = Object.keys(ETAPAS_LABEL);

  function calcProgreso(c) {
    return ETAPAS_KEYS.reduce((s, k) => s + (parseInt(c[k]) ? 1 : 0), 0);
  }

  function renderBarra(hecho, total) {
    const pct = Math.round((hecho / total) * 100);
    let color;
    if (pct === 100)      color = '#10b981';
    else if (pct >= 60)   color = '#f59e0b';
    else if (pct >= 20)   color = '#dc2020';
    else                  color = '#6e6e82';
    return `
      <div style="display:flex;align-items:center;gap:10px;max-width:380px">
        <div style="flex:1;background:rgba(255,255,255,.08);border-radius:4px;height:8px;overflow:hidden;min-width:120px">
          <div style="height:100%;width:${pct}%;background:${color};border-radius:4px;transition:width .4s"></div>
        </div>
        <span style="font-size:.78rem;font-weight:700;color:${color};min-width:32px;white-space:nowrap">${hecho}/${total}</span>
        <span style="font-size:.72rem;color:${pct===100?'#10b981':'var(--muted)'};min-width:32px;white-space:nowrap">${pct}%</span>
      </div>`;
  }

  function renderEtapasDetalle(c) {
    return ETAPAS_KEYS.map(k => {
      const ok  = parseInt(c[k]);
      const lbl = ETAPAS_LABEL[k];
      return `<span onclick="toggleEstadoCap(${c.id},'${k}',${c[k]})"
        title="Click para cambiar"
        style="cursor:pointer;display:inline-flex;align-items:center;gap:4px;padding:3px 10px;
               border-radius:6px;font-size:.78rem;font-weight:600;
               background:${ok?'rgba(16,185,129,.15)':'rgba(255,255,255,.05)'};
               border:1px solid ${ok?'#10b981':'rgba(255,255,255,.1)'};
               color:${ok?'#10b981':'#6e6e82'}">
        ${ok?'✓':'✗'} ${lbl}
      </span>`;
    }).join('');
  }

  window.cargarVistaCapitulos = async function() {
    const pId  = document.getElementById('vp-proyecto-select').value;
    const tbody = document.getElementById('vp-capitulos-body');
    const titulo = document.getElementById('vp-proyecto-titulo');
    if(!pId) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty-msg">Selecciona un proyecto.</td></tr>';
      titulo.textContent = '◎ Selecciona un proyecto';
      return;
    }
    titulo.textContent = '◎ Capítulos de ' + vpProyectosMap[pId];
    tbody.innerHTML = '<tr><td colspan="4" class="loading-cell"><span class="spinner"></span></td></tr>';
    try {
      const req = await fetch('api.php?action=listarCapitulos&proyecto_id=' + pId);
      const res = await req.json();
      if(!res || !res.exito) {
        tbody.innerHTML = '<tr><td colspan="4" class="empty-msg">Error cargando capítulos.</td></tr>';
        return;
      }
      if(!res.datos.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="empty-msg">No hay capítulos registrados.</td></tr>';
        return;
      }
      tbody.innerHTML = res.datos.map(c => {
        const hecho = calcProgreso(c);
        const total = ETAPAS_KEYS.length;
        const pct   = Math.round((hecho / total) * 100);
        const estadoGen  = c.estado_general || 'Pendiente';
        const badgeClass = estadoGen === 'Publicado' ? 'success' : (estadoGen === 'Retrasado' ? 'danger' : 'warning');
        const isReady    = hecho === total && estadoGen !== 'Publicado';
        return `
          <tr id="cap-row-${c.id}">
            <td style="font-weight:bold;text-align:center;font-size:1.05rem">${c.numero}</td>
            <td>${renderBarra(hecho, total)}</td>
            <td style="text-align:center"><span class="badge ${badgeClass}">${estadoGen}</span></td>
            <td style="text-align:center;display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap">
              <button class="btn btn-ghost btn-sm" onclick="toggleDetalle(${c.id}, ${pId}, ${c.numero})">◎ Detalle</button>
              ${isReady ? `<button class="btn btn-primary btn-sm" onclick="publicarCapitulo(${c.id})">Publicar</button>` : ''}
            </td>
          </tr>
          <tr id="cap-det-${c.id}" style="display:none">
            <td colspan="4" style="padding:.75rem 1.25rem;background:rgba(255,255,255,.02);border-bottom:1px solid var(--border)">
              <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                ${renderEtapasDetalle(c)}
                <button class="btn btn-ghost btn-sm" onclick="verificarDrive(${c.id},${pId},${c.numero})" id="btn-drive-${c.id}"
                  style="margin-left:auto" title="Busca carpetas 'Capítulo N' en Drive y actualiza los estados automáticamente">⚡ Sync Drive</button>
              </div>
              <div id="drive-result-${c.id}" style="margin-top:.6rem;font-size:.8rem"></div>
            </td>
          </tr>`;
      }).join('');
    } catch(e) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty-msg">Error: ' + e.message + '</td></tr>';
    }
  };

  window.toggleDetalle = function(id, pId, num) {
    const det = document.getElementById('cap-det-' + id);
    det.style.display = det.style.display === 'none' ? '' : 'none';
  };

  window.verificarDrive = async function(capId, proyId, capNum) {
    const btn     = document.getElementById('btn-drive-' + capId);
    const res_div = document.getElementById('drive-result-' + capId);
    btn.disabled  = true;
    btn.textContent = '⏳ Sincronizando…';
    res_div.innerHTML = '<span style="color:var(--muted);font-size:.8rem">Buscando carpetas en Drive…</span>';
    try {
      const url = `api.php?action=verificarDriveCapitulo&proyecto_id=${proyId}&capitulo_id=${capId}&capitulo_num=${capNum}&sync=1`;
      const req = await fetch(url);
      const res = await req.json();
      if(!res.exito) {
        res_div.innerHTML = `<span style="color:#dc2020;font-size:.8rem">⚠ ${res.mensaje}</span>`;
        return;
      }
      const etapasNombre = { raw:'RAW', trad:'Traducción', clean:'Limpieza', type:'Typos', proof:'QC' };
      const chips = Object.entries(res.etapas).map(([k, v]) => {
        const ok = v.encontrado;
        return `<span title="${v.nombre || 'No encontrado'}"
          style="padding:3px 10px;border-radius:6px;font-size:.75rem;font-weight:600;
                 background:${ok?'rgba(16,185,129,.15)':'rgba(220,32,32,.12)'};
                 border:1px solid ${ok?'#10b981':'#dc2020'};
                 color:${ok?'#10b981':'#f87171'}">
          ${ok?'✓':'✗'} ${etapasNombre[k]}
        </span>`;
      }).join('');
      const syncMsg = res.actualizados > 0
        ? `<span style="color:#10b981;font-size:.75rem;margin-left:8px">✓ ${res.actualizados} etapa(s) actualizadas en BD</span>`
        : `<span style="color:var(--muted);font-size:.75rem;margin-left:8px">Sin cambios nuevos</span>`;
      res_div.innerHTML = `<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">${chips}${syncMsg}</div>`;
      // Recargar la tabla para que las barras reflejen los nuevos estados
      if(res.actualizados > 0) setTimeout(() => cargarVistaCapitulos(), 600);
    } catch(e) {
      res_div.innerHTML = `<span style="color:#dc2020;font-size:.8rem">Error: ${e.message}</span>`;
    } finally {
      btn.disabled    = false;
      btn.textContent = '⚡ Sync Drive';
    }
  };

  window.syncTodosCapitulos = async function() {
    const pId = document.getElementById('vp-proyecto-select').value;
    if (!pId) { alert('Selecciona un proyecto primero.'); return; }

    const driveId = vpProyectosDriveMap[pId];
    if (!driveId) {
      alert('Este proyecto no tiene carpeta de Drive vinculada.\nVe a Proyectos → Gestión de Proyectos y usa "🔍 Auto".');
      return;
    }

    const btn = document.getElementById('btn-sync-all');
    btn.disabled = true;

    // Obtener lista de capítulos
    let caps;
    try {
      const r = await fetch('api.php?action=listarCapitulos&proyecto_id=' + pId);
      const d = await r.json();
      if (!d.exito || !d.datos.length) { btn.disabled = false; return; }
      caps = d.datos;
    } catch(e) { btn.disabled = false; return; }

    let actualizadosTotal = 0;
    for (let i = 0; i < caps.length; i++) {
      const c = caps[i];
      btn.textContent = `⏳ ${i+1}/${caps.length}`;
      try {
        const url = `api.php?action=verificarDriveCapitulo&proyecto_id=${pId}&capitulo_id=${c.id}&capitulo_num=${c.numero}&sync=1`;
        const r = await fetch(url);
        const res = await r.json();
        if (res.exito) actualizadosTotal += (res.actualizados || 0);
      } catch(e) { /* sigue con el siguiente */ }
    }

    btn.disabled = false;
    btn.textContent = '⚡ Sync All';
    await cargarVistaCapitulos();
    if (actualizadosTotal > 0) {
      const msg = document.createElement('div');
      msg.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:10px 18px;border-radius:8px;font-size:.85rem;font-weight:600;z-index:9999';
      msg.textContent = `✓ ${actualizadosTotal} etapa(s) sincronizadas desde Drive`;
      document.body.appendChild(msg);
      setTimeout(() => msg.remove(), 4000);
    }
  };

  window.toggleEstadoCap = async function(id, campo, valorActual) {
    const nuevoValor = valorActual == 1 ? 0 : 1;
    const fd = new FormData();
    fd.append('id', id);
    fd.append('campo', campo);
    fd.append('valor', nuevoValor);
    fd.append('csrf_token', window.csrfToken);
    
    try {
      const r = await fetch('api.php?action=actualizarEstadoCapitulo', { method: 'POST', body: fd });
      const res = await r.json();
      if(res.exito) {
         cargarVistaCapitulos();
      } else {
         alert(res.mensaje || 'Error al actualizar');
      }
    } catch(e) {
      console.error(e);
    }
  };

  window.publicarCapitulo = async function(id) {
    if(!confirm("¿Marcar como publicado?")) return;
    const fd = new FormData();
    fd.append('id', id);
    fd.append('csrf_token', window.csrfToken);
    try {
      const r = await fetch('api.php?action=publicarCapitulo', { method: 'POST', body: fd });
      const res = await r.json();
      if(res.exito) cargarVistaCapitulos();
    } catch(e) {
      console.error(e);
    }
  };

  window.abrirModalNuevoCapitulo = function() {
    const pId = document.getElementById('vp-proyecto-select').value;
    if(!pId) return alert('Selecciona un proyecto primero.');
    document.getElementById('new-cap-num').value = '';
    document.getElementById('cap-modal-overlay').classList.remove('hidden');
    document.getElementById('cap-modal').classList.remove('hidden');
  };

  window.cerrarModalNuevoCapitulo = function() {
    document.getElementById('cap-modal-overlay').classList.add('hidden');
    document.getElementById('cap-modal').classList.add('hidden');
  };

  window.guardarNuevoCapitulo = async function() {
    const pId = document.getElementById('vp-proyecto-select').value;
    const num = document.getElementById('new-cap-num').value;
    if(!pId || !num) return;
    
    const fd = new FormData();
    fd.append('proyecto_id', pId);
    fd.append('numero', num);
    fd.append('csrf_token', window.csrfToken);
    
    try {
      const r = await fetch('api.php?action=crearCapitulo', { method: 'POST', body: fd });
      const res = await r.json();
      if(res.exito) {
        cerrarModalNuevoCapitulo();
        cargarVistaCapitulos();
      } else {
        alert(res.mensaje || 'Error al crear.');
      }
    } catch(e) {
      console.error(e);
    }
  };

  window.cargarHistorialFull = async function() {
    const tbody = document.getElementById('historial-full-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem"><span class="spinner"></span> Cargando…</td></tr>';
    const historial = await fetchHistorial();
    if (!historial || !historial.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="empty-msg">No hay registros.</td></tr>';
      return;
    }
    // Columnas: Manga | Cap | Etapa | Fecha | Usuario | Acciones
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

})();
</script>

</body>
</html>
