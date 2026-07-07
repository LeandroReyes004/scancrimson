// ─── ETIQUETAS BONITAS PARA CADA ETAPA ───────────────────────────────────
const ETAPA_LABELS = {
  "01. RAWs":                  { label: "RAWs",             icon: "📦" },
  "02. Traducción":            { label: "Traducción",        icon: "🌐" },
  "03. Limpieza y Redibujo":   { label: "Limpieza/Redibujo", icon: "✏️" },
  "04. Typos":                 { label: "Typos",             icon: "🔤" },
  "05. Control de Calidad":    { label: "Control de Calidad",icon: "✅" },
};

// ─── CARGAR PROYECTOS AL INICIO ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  cargarProyectos();
  cargarHistorial();
});

function cargarProyectos() {
  fetch('api.php?action=proyectos')
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('sel-proyecto');
      if (!sel) return;
      if (!data.exito || !data.datos.length) {
        sel.innerHTML = '<option value="">Sin proyectos</option>';
        return;
      }
      sel.innerHTML = '<option value="">— Selecciona un proyecto —</option>' +
        data.datos.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');
    })
    .catch(() => {
      const sel = document.getElementById('sel-proyecto');
      if (sel) sel.innerHTML = '<option value="">Error al cargar</option>';
    });
}

// ─── BUSCAR CAPÍTULO ─────────────────────────────────────────────────────
function buscarCapitulo() {
  const proyecto = document.getElementById('sel-proyecto')?.value;
  const capitulo = document.getElementById('inp-capitulo')?.value;
  const etapa    = document.getElementById('sel-etapa')?.value || 'Todas';
  const area     = document.getElementById('resultados');

  if (!proyecto) { showToast('Selecciona un proyecto.'); return; }
  if (!capitulo) { showToast('Ingresa un número de capítulo.'); return; }

  area.classList.remove('hidden');
  area.innerHTML = `
    <div class="loading-pulse">
      <div class="pulse-bar"></div>
      <div class="pulse-bar short"></div>
    </div>`;

  const btn = document.getElementById('btn-buscar');
  if (btn) { btn.disabled = true; btn.querySelector('.btn-text').textContent = 'Buscando...'; }

  const url = `api.php?action=enlaces&proyecto=${encodeURIComponent(proyecto)}&capitulo=${encodeURIComponent(capitulo)}&etapa=${encodeURIComponent(etapa)}`;

  fetch(url)
    .then(r => r.json())
    .then(data => {
      renderResultados(data, proyecto, capitulo);
    })
    .catch(() => {
      area.innerHTML = '<div class="no-resultado"><strong>⚠️</strong>Error de conexión.</div>';
    })
    .finally(() => {
      if (btn) { btn.disabled = false; btn.querySelector('.btn-text').textContent = 'Buscar'; }
    });
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
    area.innerHTML = `
      <div class="no-resultado">
        <strong>🔍</strong>
        No se encontró el capítulo <strong>${esc(capitulo)}</strong>
        en <strong>${esc(proyecto)}</strong>.
      </div>`;
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
        <a href="${enlace.url}" class="btn-download" target="_blank" rel="noopener">
          ⬇ Descargar
        </a>
      </div>`;
  }).join('');

  area.innerHTML = `
    <p class="resultados-titulo">
      Resultados — <span>${esc(proyecto)}</span> · Cap. ${esc(capitulo)}
    </p>
    <div class="resultado-grid">${items}</div>`;
}

// ─── HISTORIAL ────────────────────────────────────────────────────────────
function cargarHistorial() {
  const lista = document.getElementById('historial-lista');
  const badge = document.getElementById('historial-count');
  if (!lista) return;

  fetch('api.php?action=historial')
    .then(r => r.json())
    .then(data => {
      if (!data.exito || !data.datos.length) {
        lista.innerHTML = '<p class="empty-msg">Sin actividad registrada.</p>';
        if (badge) badge.textContent = '0';
        return;
      }
      if (badge) badge.textContent = data.datos.length;
      lista.innerHTML = data.datos.map(fila => {
        const principal = fila[0] || '—';
        const resto     = fila.slice(1).filter(Boolean).join(' · ');
        return `
          <div class="historial-item">
            <div class="hist-main">${esc(principal)}</div>
            ${resto ? `<div class="hist-meta">${esc(resto)}</div>` : ''}
          </div>`;
      }).join('');
    })
    .catch(() => {
      if (lista) lista.innerHTML = '<p class="empty-msg">Error al cargar historial.</p>';
    });
}

// ─── UTILS ────────────────────────────────────────────────────────────────
function esc(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

function showToast(msg) {
  let t = document.getElementById('_toast');
  if (!t) {
    t = document.createElement('div');
    t.id = '_toast';
    t.style.cssText = `
      position:fixed; bottom:1.5rem; left:50%; transform:translateX(-50%);
      background:#1a1a2a; color:#e4e4f0; border:1px solid #dc2020;
      padding:.6rem 1.3rem; border-radius:8px; font-size:.875rem;
      z-index:9999; box-shadow:0 4px 20px rgba(0,0,0,.5);
      transition:opacity .3s;
    `;
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.opacity = '0'; }, 2500);
}

// Enter para buscar
document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && document.getElementById('inp-capitulo') === document.activeElement) {
    buscarCapitulo();
  }
});
