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
