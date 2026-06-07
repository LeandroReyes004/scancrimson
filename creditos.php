<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hoja de Créditos · Crimson Scan</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#0a0a0e;--surface:rgba(255,255,255,.04);--border:rgba(255,255,255,.08);
    --text:#f0f0f4;--muted:#6e6e82;--muted2:#9898b0;
    --red:#dc2020;--red-bright:#ff3535;--green:#10b981;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;min-height:100vh}
  .top-bar{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.5rem;background:rgba(255,255,255,.03);border-bottom:1px solid var(--border)}
  .top-bar .title{font-size:1rem;font-weight:700}
  .top-bar .title span{color:var(--red-bright)}
  a.back{color:var(--muted);font-size:.82rem;text-decoration:none}
  a.back:hover{color:var(--text)}
  .layout{display:grid;grid-template-columns:340px 1fr;gap:1.5rem;padding:1.5rem;max-width:1300px;margin:0 auto}
  @media(max-width:900px){.layout{grid-template-columns:1fr}}
  .panel{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.25rem}
  .panel-title{font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted2);margin-bottom:1rem;padding-bottom:.6rem;border-bottom:1px solid var(--border)}
  .field{margin-bottom:.9rem}
  .field label{display:block;font-size:.68rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem}
  .field input,.field select,.field textarea{
    width:100%;background:rgba(255,255,255,.06);border:1px solid var(--border);
    border-radius:8px;color:var(--text);padding:.6rem .9rem;font-size:.88rem;
    font-family:inherit;outline:none;transition:border-color .15s
  }
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--red)}
  .field textarea{resize:vertical;min-height:60px}
  select option{background:#1a1a2e}
  .btn{background:var(--red);border:none;border-radius:8px;color:#fff;cursor:pointer;font-family:inherit;font-size:.85rem;font-weight:600;padding:.6rem 1.2rem;transition:background .15s}
  .btn:hover{background:#b81a1a}
  .btn-ghost{background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted2)}
  .btn-ghost:hover{background:rgba(255,255,255,.1);color:var(--text)}
  .btn-sm{padding:.4rem .85rem;font-size:.78rem}
  .btn-green{background:#10b981}
  .btn-green:hover{background:#0d9e6e}
  .row{display:flex;gap:.6rem;flex-wrap:wrap}
  .sep{border:none;border-top:1px solid var(--border);margin:1.1rem 0}
  .msg{font-size:.8rem;margin-top:.5rem;padding:.45rem .75rem;border-radius:6px}
  .msg.ok{background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25)}
  .msg.err{background:rgba(220,32,32,.12);color:#ff5555;border:1px solid rgba(220,32,32,.25)}
  /* Preview */
  .preview-wrap{display:flex;flex-direction:column;align-items:center;gap:1rem}
  #credito-canvas{max-width:100%;border-radius:10px;box-shadow:0 4px 32px rgba(0,0,0,.6);cursor:crosshair}
  .canvas-hint{font-size:.72rem;color:var(--muted);text-align:center}
  /* Asignaciones actuales */
  .asig-grid{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem}
  .asig-chip{background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:.45rem .7rem}
  .asig-chip .rol{font-size:.62rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:2px}
  .asig-chip .nombre{font-size:.82rem;font-weight:600}
  .asig-chip.filled{border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.06)}
  .asig-chip.filled .nombre{color:#10b981}
  .toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:999;display:flex;flex-direction:column;gap:.5rem}
  .toast{background:#1e1e2e;border:1px solid var(--border);border-radius:8px;padding:.55rem 1rem;font-size:.84rem;animation:slideIn .2s ease}
  .toast.ok{border-color:var(--green)}.toast.err{border-color:var(--red)}
  @keyframes slideIn{from{transform:translateX(20px);opacity:0}}
</style>
</head>
<body>

<div class="top-bar">
  <div class="title">CRIMSON <span>CRÉDITOS</span></div>
  <a href="admin.php" class="back">← Volver al panel</a>
</div>

<div class="layout">

  <!-- ── PANEL IZQUIERDO ── -->
  <div style="display:flex;flex-direction:column;gap:1rem">

    <!-- Selector manga + cap -->
    <div class="panel">
      <div class="panel-title">◈ Manga &amp; Capítulo</div>
      <div class="field">
        <label>Manga</label>
        <select id="sel-manga"></select>
      </div>
      <div class="field">
        <label>Capítulo</label>
        <input id="inp-cap" type="text" placeholder="Ej: 15">
      </div>
      <button class="btn btn-ghost btn-sm" onclick="cargarAsignaciones()">⬇ Cargar del sistema</button>
      <div id="asig-result"></div>
      <!-- Asignaciones actuales en BD -->
      <div id="asig-chips" style="display:none;margin-top:.75rem">
        <div style="font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem">Asignaciones en BD</div>
        <div class="asig-grid">
          <div class="asig-chip" id="chip-trad"><div class="rol">Trad</div><div class="nombre" id="chip-trad-n">—</div></div>
          <div class="asig-chip" id="chip-type"><div class="rol">Type</div><div class="nombre" id="chip-type-n">—</div></div>
          <div class="asig-chip" id="chip-clean"><div class="rol">Clean</div><div class="nombre" id="chip-clean-n">—</div></div>
          <div class="asig-chip" id="chip-proof"><div class="rol">QC/Proof</div><div class="nombre" id="chip-proof-n">—</div></div>
        </div>
      </div>
    </div>

    <!-- Campos del crédito -->
    <div class="panel">
      <div class="panel-title">✏ Nombres para la hoja</div>
      <div class="field">
        <label>Traductor/A</label>
        <input id="inp-trad" type="text" placeholder="Nombre del traductor" oninput="renderCanvas()">
      </div>
      <div class="field">
        <label>Typesetter</label>
        <input id="inp-type" type="text" placeholder="Nombre del typesetter" oninput="renderCanvas()">
      </div>
      <div class="field">
        <label>Cleaner / Redrawer</label>
        <input id="inp-clean" type="text" placeholder="Nombre del cleaner" oninput="renderCanvas()">
      </div>
      <div class="field">
        <label>Quality Checker</label>
        <input id="inp-qc" type="text" value="STAFF" oninput="renderCanvas()">
      </div>
      <div class="field">
        <label>Staff de Apoyo</label>
        <input id="inp-apoyo" type="text" value="ESCLAVOS CRIMSON'S" oninput="renderCanvas()">
      </div>
      <div class="row" style="margin-top:.5rem">
        <button class="btn btn-ghost btn-sm" onclick="limpiarCampos()">Limpiar</button>
        <button class="btn btn-sm" style="margin-left:auto" onclick="descargarCredito()">⬇ Descargar PNG</button>
      </div>
    </div>

    <!-- Asignar tarea -->
    <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
    <div class="panel">
      <div class="panel-title">⊕ Asignar tarea</div>
      <div class="field">
        <label>Rol</label>
        <select id="sel-rol-asignar">
          <option value="Traductor">Traductor</option>
          <option value="Limpiador">Limpiador / Redrawer</option>
          <option value="Typesetter">Typesetter</option>
          <option value="QC">QC / Proof</option>
        </select>
      </div>
      <div class="field">
        <label>Staff</label>
        <select id="sel-staff-asignar">
          <option value="">Cargando…</option>
        </select>
      </div>
      <div class="field">
        <label>Fecha límite <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
        <input id="inp-limite" type="datetime-local">
      </div>
      <button class="btn btn-green btn-sm" onclick="asignarTarea()">Asignar</button>
      <div id="asignar-msg"></div>
    </div>
    <?php endif; ?>

  </div>

  <!-- ── PANEL DERECHO: PREVIEW ── -->
  <div class="panel preview-wrap">
    <div class="panel-title" style="width:100%;text-align:center">Vista previa — <span style="color:var(--muted);text-transform:none;font-weight:400">edita los campos para actualizar</span></div>
    <canvas id="credito-canvas"></canvas>
    <div class="canvas-hint">La imagen se actualiza en tiempo real · Click derecho → Guardar imagen también funciona</div>
  </div>

</div>

<div class="toast-container" id="toast-container"></div>

<script>
const CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';

/* ── Posiciones del texto (fracción del ancho/alto de la imagen) ──
   Ajustar si los textos no quedan centrados en los recuadros.      */
const POS = {
  trad:  { x: 0.390, y: 0.462 },
  type:  { x: 0.770, y: 0.462 },
  clean: { x: 0.450, y: 0.605 },
  qc:    { x: 0.383, y: 0.760 },
  apoyo: { x: 0.745, y: 0.760 },
};

/* Rectángulos oscuros para tapar el texto por defecto de QC y Apoyo */
const COVERS = [
  { x: 0.252, y: 0.726, w: 0.278, h: 0.075 }, // QC "STAFF"
  { x: 0.578, y: 0.726, w: 0.302, h: 0.075 }, // APOYO "ESCLAVOS CRIMSON'S"
];

const IMG_SRC = 'creditos.jpg';
let imgEl = new Image();
let imgLoaded = false;
imgEl.crossOrigin = 'anonymous';
imgEl.onload = () => { imgLoaded = true; renderCanvas(); };
imgEl.onerror = () => {
  const canvas = document.getElementById('credito-canvas');
  if (!canvas) return;
  canvas.width = 530; canvas.height = 750;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#1a0a1e';
  ctx.fillRect(0, 0, 530, 750);
  ctx.fillStyle = '#ff5555';
  ctx.font = 'bold 16px Arial';
  ctx.textAlign = 'center';
  ctx.fillText('No se pudo cargar creditos.jpg', 265, 375);
};
imgEl.src = IMG_SRC + '?v=' + Date.now(); // evitar caché
// Si ya estaba cacheada y complete antes del onload
document.addEventListener('DOMContentLoaded', () => {
  if (imgEl.complete && imgEl.naturalWidth > 0) { imgLoaded = true; renderCanvas(); }
});

function renderCanvas() {
  const canvas = document.getElementById('credito-canvas');
  if (!canvas || !imgLoaded) return;
  const W = imgEl.naturalWidth  || 530;
  const H = imgEl.naturalHeight || 750;
  canvas.width  = W;
  canvas.height = H;
  const ctx = canvas.getContext('2d');

  ctx.drawImage(imgEl, 0, 0);

  // Tapar textos por defecto
  ctx.fillStyle = '#0f0710';
  COVERS.forEach(c => ctx.fillRect(c.x*W, c.y*H, c.w*W, c.h*H));

  const campos = {
    trad:  document.getElementById('inp-trad').value.trim(),
    type:  document.getElementById('inp-type').value.trim(),
    clean: document.getElementById('inp-clean').value.trim(),
    qc:    document.getElementById('inp-qc').value.trim()    || 'STAFF',
    apoyo: document.getElementById('inp-apoyo').value.trim() || "ESCLAVOS CRIMSON'S",
  };

  const fontSize = Math.round(W * 0.048);
  ctx.font         = `bold ${fontSize}px "DM Sans", Arial, sans-serif`;
  ctx.textAlign    = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle    = '#ffffff';
  ctx.shadowColor  = 'rgba(255,60,120,0.55)';
  ctx.shadowBlur   = 7;

  Object.entries(POS).forEach(([key, pos]) => {
    if (campos[key]) {
      const maxW = W * 0.22;
      ctx.fillText(campos[key], W * pos.x, H * pos.y, maxW);
    }
  });
}

function descargarCredito() {
  renderCanvas();
  const canvas = document.getElementById('credito-canvas');
  const manga  = (document.getElementById('sel-manga').value  || 'credito').replace(/\s+/g, '_');
  const cap    = (document.getElementById('inp-cap').value    || '').replace(/\s+/g, '');
  const link   = document.createElement('a');
  link.download = `credito_${manga}_cap${cap}.png`;
  link.href     = canvas.toDataURL('image/png');
  link.click();
}

function limpiarCampos() {
  ['inp-trad','inp-type','inp-clean'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('inp-qc').value    = 'STAFF';
  document.getElementById('inp-apoyo').value = "ESCLAVOS CRIMSON'S";
  renderCanvas();
}

/* ── Cargar proyectos ── */
async function cargarProyectos() {
  const res = await fetch('api.php?action=proyectos').then(r=>r.json()).catch(()=>null);
  const sel = document.getElementById('sel-manga');
  if (res?.exito && res.datos.length) {
    sel.innerHTML = '<option value="">— Seleccionar —</option>' +
      res.datos.map(n => `<option value="${esc(n)}">${esc(n)}</option>`).join('');
    // Pre-fill desde URL param
    const pUrl = new URLSearchParams(location.search).get('manga');
    const cUrl = new URLSearchParams(location.search).get('cap');
    if (pUrl) { sel.value = pUrl; }
    if (cUrl) { document.getElementById('inp-cap').value = cUrl; }
    if (pUrl && cUrl) cargarAsignaciones();
  } else {
    sel.innerHTML = '<option value="">Sin proyectos</option>';
  }
}

/* ── Cargar staff ── */
async function cargarStaff() {
  const res = await fetch('api.php?action=listarStaff').then(r=>r.json()).catch(()=>null);
  const sel = document.getElementById('sel-staff-asignar');
  if (!sel) return;
  if (res?.exito && res.datos?.length) {
    sel.innerHTML = '<option value="">— Seleccionar staff —</option>' +
      res.datos.map(s => {
        const nombre = s.nombre_display || s.usuario_form || s.discord_id;
        return `<option value="${esc(s.discord_id)}">${esc(nombre)} (${esc(s.rol||'Staff')})</option>`;
      }).join('');
  } else {
    sel.innerHTML = '<option value="">Sin staff registrado</option>';
  }
}

/* ── Cargar asignaciones de BD ── */
async function cargarAsignaciones() {
  const manga = document.getElementById('sel-manga').value;
  const cap   = document.getElementById('inp-cap').value.trim();
  const msgEl = document.getElementById('asig-result');
  if (!manga || cap === '') { toast('Selecciona manga y capítulo', 'err'); return; }

  msgEl.innerHTML = '<div style="font-size:.78rem;color:var(--muted);margin-top:.5rem">Cargando…</div>';

  const res = await fetch(`api.php?action=capituloAsignaciones&manga=${encodeURIComponent(manga)}&cap=${encodeURIComponent(cap)}`)
    .then(r=>r.json()).catch(()=>null);

  if (!res?.exito) {
    msgEl.innerHTML = `<div class="msg err">${res?.mensaje || 'Error al cargar'}</div>`;
    return;
  }

  const a = res.asig;
  // Actualizar chips
  document.getElementById('asig-chips').style.display = '';
  setChip('trad',  a.trad);
  setChip('type',  a.type);
  setChip('clean', a.clean);
  setChip('proof', a.proof);

  // Auto-rellenar campos si están vacíos
  if (a.trad  && !document.getElementById('inp-trad').value)  { document.getElementById('inp-trad').value  = a.trad;  }
  if (a.type  && !document.getElementById('inp-type').value)  { document.getElementById('inp-type').value  = a.type;  }
  if (a.clean && !document.getElementById('inp-clean').value) { document.getElementById('inp-clean').value = a.clean; }
  if (a.proof && !document.getElementById('inp-qc').value.includes('STAFF') ) { document.getElementById('inp-qc').value = a.proof; }

  renderCanvas();
  msgEl.innerHTML = `<div class="msg ok">✓ ${res.tareas.length} tarea(s) encontrada(s)</div>`;
  setTimeout(() => { msgEl.innerHTML = ''; }, 3000);
}

function setChip(rol, nombre) {
  const chip = document.getElementById('chip-' + rol);
  const n    = document.getElementById('chip-' + rol + '-n');
  if (!chip || !n) return;
  n.textContent = nombre || '—';
  chip.classList.toggle('filled', !!nombre);
}

/* ── Asignar tarea ── */
async function asignarTarea() {
  const manga      = document.getElementById('sel-manga').value;
  const cap        = document.getElementById('inp-cap').value.trim();
  const rol        = document.getElementById('sel-rol-asignar').value;
  const discord_id = document.getElementById('sel-staff-asignar').value;
  const limite     = document.getElementById('inp-limite').value;
  const msgEl      = document.getElementById('asignar-msg');

  if (!manga || cap === '') { toast('Selecciona manga y capítulo primero', 'err'); return; }
  if (!discord_id)          { toast('Selecciona un miembro del staff', 'err'); return; }

  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('manga',      manga);
  fd.append('cap',        cap);
  fd.append('rol',        rol);
  fd.append('discord_id', discord_id);
  if (limite) fd.append('limite', limite.replace('T',' ') + ':00');

  const res = await fetch('api.php?action=asignarTarea', {method:'POST',body:fd})
    .then(r=>r.json()).catch(()=>null);

  if (res?.exito) {
    msgEl.innerHTML = '<div class="msg ok">✓ Tarea asignada correctamente</div>';
    toast('Tarea asignada');
    await cargarAsignaciones();
  } else {
    msgEl.innerHTML = `<div class="msg err">${res?.mensaje || 'Error al asignar'}</div>`;
  }
  setTimeout(() => { msgEl.innerHTML = ''; }, 4000);
}

function esc(s) {
  return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toast(msg, type='ok') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(()=>{ t.style.opacity='0'; t.style.transition='.3s'; setTimeout(()=>t.remove(),300); }, 3500);
}

cargarProyectos();
cargarStaff();
</script>
</body>
</html>
