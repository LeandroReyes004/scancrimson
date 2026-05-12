<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
  :root {
    --card-hover: rgba(255, 255, 255, 0.03);
    --badge-raws: #ef4444;
    --badge-trad: #3b82f6;
    --badge-clean: #8b5cf6;
    --badge-typo: #f59e0b;
    --badge-qc: #10b981;
  }

  .admin-hero { padding: 3rem 0 2rem; display: flex; justify-content: space-between; align-items: center; }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
  }

  .stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
  }

  .stat-card::after {
    content: '';
    position: absolute;
    bottom: -20px; right: -10px;
    font-size: 5rem;
    opacity: 0.05;
  }
  .stat-proyectos::after { content: '📁'; }
  .stat-uploads::after { content: '🚀'; }
  .stat-recent::after { content: '⏱️'; }

  .stat-value { font-family: var(--font-head); font-size: 2.5rem; color: var(--red); line-height: 1; }
  .stat-label { font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 0.5rem; }

  .dashboard-main {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
    align-items: start;
  }

  @media (max-width: 992px) {
    .dashboard-main { grid-template-columns: 1fr; }
  }

  .panel-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
  }

  .table-container { overflow-x: auto; }
  .modern-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
  .modern-table th { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border); color: var(--muted); font-weight: 500; }
  .modern-table td { padding: 1rem; border-bottom: 1px solid var(--border); }
  
  .badge {
    padding: 0.25rem 0.6rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .badge-01 { background: rgba(239, 68, 68, 0.1); color: var(--badge-raws); border: 1px solid var(--badge-raws); }
  .badge-02 { background: rgba(59, 130, 246, 0.1); color: var(--badge-trad); border: 1px solid var(--badge-trad); }
  .badge-03 { background: rgba(139, 92, 246, 0.1); color: var(--badge-clean); border: 1px solid var(--badge-clean); }
  .badge-04 { background: rgba(245, 158, 11, 0.1); color: var(--badge-typo); border: 1px solid var(--badge-typo); }
  .badge-05 { background: rgba(16, 185, 129, 0.1); color: var(--badge-qc); border: 1px solid var(--badge-qc); }

  .loading-spinner {
    width: 20px; height: 20px;
    border: 2px solid rgba(220, 32, 32, 0.2);
    border-top-color: var(--red);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
    vertical-align: middle;
    margin-right: 8px;
  }

  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div class="bg-grid"></div>
<div class="noise"></div>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo" style="text-decoration:none">
      <span class="logo-icon">⚔</span>
      <span class="logo-text">CRIMSON <span class="logo-accent">SCAN</span></span>
    </a>
    <nav class="site-nav">
      <a href="index.php" class="nav-link">Buscar</a>
      <a href="subir.php" class="nav-link">Subir</a>
      <a href="admin.php" class="nav-link active">Admin</a>
    </nav>
  </div>
</header>

<!-- ─── LOGIN ─── -->
<div id="login-screen" class="login-screen">
  <div class="login-card">
    <div class="login-icon">🔐</div>
    <h2 class="login-title">Acceso Admin</h2>
    <p class="login-sub">Solo personal autorizado</p>
    <div class="field-group" style="margin-top:1.5rem">
      <label class="field-label">Contraseña</label>
      <input id="inp-pass" type="password" class="field-input" placeholder="••••••••"
             onkeydown="if(event.key==='Enter') verificarLogin()">
    </div>
    <div id="login-error" class="error-msg hidden">Contraseña incorrecta.</div>
    <button class="btn-primary" style="width:100%;margin-top:1rem" onclick="verificarLogin()">
      <span class="btn-text">Entrar</span><span class="btn-icon">→</span>
    </button>
  </div>
</div>

<!-- ─── PANEL ADMIN ─── -->
<main id="admin-panel" class="container hidden">

  <div class="admin-hero">
    <div>
      <p class="hero-sub">Dashboard · Administración</p>
      <h1 class="hero-title" style="margin-top:0.5rem">Crimson <span class="text-red">Control</span></h1>
    </div>
    <button class="btn-outline" onclick="cerrarSesion()">Cerrar sesión</button>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card stat-proyectos">
      <div class="stat-value" id="stat-proyectos">...</div>
      <div class="stat-label">Proyectos Activos</div>
    </div>
    <div class="stat-card stat-uploads">
      <div class="stat-value" id="stat-total">...</div>
      <div class="stat-label">Total Subidas</div>
    </div>
    <div class="stat-card stat-recent">
      <div class="stat-value" id="stat-hoy">...</div>
      <div class="stat-label">Subidas Hoy</div>
    </div>
  </div>

  <div class="dashboard-main">
    
    <!-- Sidebar: Crear Proyecto -->
    <div class="panel-card">
      <h3 class="card-subtitle" style="margin-bottom:1rem">📁 Nuevo Proyecto</h3>
      <p class="card-desc" style="margin-bottom:2rem">Crea automáticamente las 5 carpetas de etapa en Google Drive.</p>
      
      <div class="field-group" style="margin-bottom:1.5rem">
        <label class="field-label">Nombre del Manga</label>
        <input id="inp-proyecto-nuevo" type="text" class="field-input" placeholder="Ej: Solo Leveling">
      </div>
      
      <button class="btn-primary" style="width:100%" onclick="crearProyecto()" id="btn-crear">
        <span class="btn-text">Crear Proyecto</span>
      </button>

      <div id="crear-resultado" style="margin-top:1.5rem"></div>
    </div>

    <!-- Main Content: Historial -->
    <div class="panel-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem">
        <h3 class="card-subtitle">📋 Historial Reciente</h3>
        <button class="btn-outline" style="font-size:0.7rem; padding:0.5rem 1rem" onclick="cargarHistorialAdmin()">Refrescar</button>
      </div>
      
      <div class="table-container">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Manga</th>
              <th>Cap</th>
              <th>Etapa</th>
              <th>Archivo</th>
            </tr>
          </thead>
          <tbody id="admin-historial-body">
            <!-- Cargado vía JS -->
          </tbody>
        </table>
      </div>
    </div>

  </div>

</main>

<footer class="site-footer" style="margin-top:4rem">
  <div class="container"><p>© 2025 Crimson Scan · Management System</p></div>
</footer>

<script>
const PASS_KEY = 'cs_admin_auth';

function verificarLogin() {
  const pass = document.getElementById('inp-pass').value;
  if (pass === 'crimson2026') {
    sessionStorage.setItem(PASS_KEY, pass);
    mostrarPanel();
  } else {
    document.getElementById('login-error').classList.remove('hidden');
  }
}

function cerrarSesion() {
  sessionStorage.removeItem(PASS_KEY);
  location.reload();
}

function mostrarPanel() {
  document.getElementById('login-screen').classList.add('hidden');
  document.getElementById('admin-panel').classList.remove('hidden');
  actualizarStats();
  cargarHistorialAdmin();
}

function actualizarStats() {
  // Proyectos
  fetch('api.php?action=proyectos')
    .then(r => r.json())
    .then(res => {
      if(res.exito) document.getElementById('stat-proyectos').textContent = res.datos.length;
    });

  // Historial
  fetch('api.php?action=historial')
    .then(r => r.json())
    .then(res => {
      if(res.exito) {
        document.getElementById('stat-total').textContent = res.datos.length;
        // Calcular hoy
        const hoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        const countHoy = res.datos.filter(f => f[0] && f[0].includes(hoy)).length;
        document.getElementById('stat-hoy').textContent = countHoy;
      }
    });
}

function cargarHistorialAdmin() {
  const body = document.getElementById('admin-historial-body');
  body.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem">Cargando historial...</td></tr>';

  fetch('api.php?action=historial')
    .then(r => r.json())
    .then(data => {
      if (!data.exito || !data.datos.length) {
        body.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem">No hay registros disponibles.</td></tr>';
        return;
      }

      body.innerHTML = data.datos.map(fila => {
        const etapa = (fila[3] || '').substring(0, 2); // 01, 02...
        return `
          <tr>
            <td data-label="Fecha" style="color:var(--muted); white-space:nowrap">${fila[0]}</td>
            <td data-label="Manga" style="font-weight:600">${fila[1]}</td>
            <td data-label="Cap" style="color:var(--red)">#${fila[2]}</td>
            <td data-label="Etapa"><span class="badge badge-${etapa}">${fila[3] || '—'}</span></td>
            <td data-label="Archivo" style="font-size:0.8rem; opacity:0.8">${fila[4]}</td>
          </tr>
        `;
      }).join('');
    });
}

function crearProyecto() {
  const inp = document.getElementById('inp-proyecto-nuevo');
  const btn = document.getElementById('btn-crear');
  const res = document.getElementById('crear-resultado');
  const nombre = inp.value.trim();
  const pass = sessionStorage.getItem(PASS_KEY);

  if (!nombre) return;

  btn.disabled = true;
  btn.innerHTML = '<span class="loading-spinner"></span> Creando...';
  res.innerHTML = '';

  const form = new FormData();
  form.append('pass', pass);
  form.append('nombre', nombre);

  fetch('api.php?action=crearProyecto', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = 'Crear Proyecto';
      
      if (data.exito) {
        res.innerHTML = `<div class="success-msg">✅ ${data.mensaje}</div>`;
        inp.value = '';
        actualizarStats(); // Actualizar contador de proyectos
      } else {
        res.innerHTML = `<div class="error-msg">❌ ${data.mensaje}</div>`;
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = 'Crear Proyecto';
      res.innerHTML = '<div class="error-msg">Error de conexión con el servidor.</div>';
    });
}

if (sessionStorage.getItem(PASS_KEY)) mostrarPanel();
</script>
</body>
</html>
