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
          <button class="btn btn-ghost btn-sm" onclick="cargarVistaCapitulos()">↺ Refrescar</button>
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
                <th style="width: 80px; text-align: center;">Cap.</th>
                <th style="text-align: center;">RAW</th>
                <th style="text-align: center;">Trad</th>
                <th style="text-align: center;">Clean</th>
                <th style="text-align: center;">Type</th>
                <th style="text-align: center;">Proof</th>
                <th style="text-align: center;">Estado</th>
                <th style="text-align: center;">Acción</th>
              </tr>
            </thead>
            <tbody id="vp-capitulos-body">
              <tr><td colspan="8" class="empty-msg">Selecciona un proyecto para ver sus capítulos.</td></tr>
            </tbody>
          </table>
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

  window.cargarProyectos = async function() {
    try {
      // Usar un endpoint para obtener los proyectos en formato id, nombre
      const req = await fetch('api.php?action=listarProyectosAdmin');
      const res = await req.json();
      if(res && res.exito) {
        const sel = document.getElementById('vp-proyecto-select');
        sel.innerHTML = '<option value="">— Seleccionar Proyecto —</option>';
        res.datos.forEach(p => {
          vpProyectosMap[p.id] = p.nombre;
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = p.nombre;
          sel.appendChild(opt);
        });
      }
    } catch(e) {
      console.error(e);
    }
  };

  window.cargarVistaCapitulos = async function() {
    const pId = document.getElementById('vp-proyecto-select').value;
    const tbody = document.getElementById('vp-capitulos-body');
    const titulo = document.getElementById('vp-proyecto-titulo');
    if(!pId) {
      tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">Selecciona un proyecto.</td></tr>';
      titulo.textContent = '◎ Selecciona un proyecto';
      return;
    }
    titulo.textContent = '◎ Capítulos de ' + vpProyectosMap[pId];
    tbody.innerHTML = '<tr><td colspan="8" class="loading-cell"><span class="spinner"></span></td></tr>';
    
    try {
      const req = await fetch('api.php?action=listarCapitulos&proyecto_id=' + pId);
      const res = await req.json();
      if(res && res.exito) {
        if(res.datos.length === 0) {
          tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">No hay capítulos registrados para este proyecto.</td></tr>';
          return;
        }
        tbody.innerHTML = res.datos.map(c => {
          const checkIcon = val => parseInt(val) === 1 ? '✅' : '⬜';
          const badgeClass = c.estado_general === 'Publicado' ? 'success' : (c.estado_general === 'Retrasado' ? 'danger' : 'warning');
          const isAllReady = parseInt(c.estado_raw) && parseInt(c.estado_trad) && parseInt(c.estado_clean) && parseInt(c.estado_type) && parseInt(c.estado_proof);
          let btnHtml = '—';
          if(c.estado_general !== 'Publicado' && isAllReady) {
             btnHtml = `<button class="btn btn-primary btn-sm" onclick="publicarCapitulo(${c.id})">Publicar</button>`;
          }
          
          return `
            <tr>
              <td style="font-weight: bold; text-align: center; font-size: 1.1rem">${c.numero}</td>
              <td style="text-align: center; cursor: pointer" onclick="toggleEstadoCap(${c.id}, 'estado_raw', ${c.estado_raw})">${checkIcon(c.estado_raw)}</td>
              <td style="text-align: center; cursor: pointer" onclick="toggleEstadoCap(${c.id}, 'estado_trad', ${c.estado_trad})">${checkIcon(c.estado_trad)}</td>
              <td style="text-align: center; cursor: pointer" onclick="toggleEstadoCap(${c.id}, 'estado_clean', ${c.estado_clean})">${checkIcon(c.estado_clean)}</td>
              <td style="text-align: center; cursor: pointer" onclick="toggleEstadoCap(${c.id}, 'estado_type', ${c.estado_type})">${checkIcon(c.estado_type)}</td>
              <td style="text-align: center; cursor: pointer" onclick="toggleEstadoCap(${c.id}, 'estado_proof', ${c.estado_proof})">${checkIcon(c.estado_proof)}</td>
              <td style="text-align: center"><span class="badge ${badgeClass}">${c.estado_general}</span></td>
              <td style="text-align: center">${btnHtml}</td>
            </tr>
          `;
        }).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">Error cargando capítulos.</td></tr>';
      }
    } catch(e) {
      tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">Error: ' + e.message + '</td></tr>';
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
