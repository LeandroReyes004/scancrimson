/* ─── CONFIG & STATE ─── */
const PASS_KEY = 'cs_admin_auth';
const TABS = ['dashboard', 'proyectos', 'nuevo', 'historial'];
const TAB_LABELS = { 
    dashboard: 'Dashboard', 
    proyectos: 'Proyectos', 
    nuevo: 'Nuevo proyecto', 
    historial: 'Historial completo' 
};

let state = {
    historial: [],
    proyectos: [],
    searchQuery: ''
};

/* ─── AUTH ─── */
function verificarLogin() {
    const inp = document.getElementById('inp-pass');
    const pass = inp.value;
    // Contraseña hardcoded según el código original
    if (pass === 'crimson2026') {
        sessionStorage.setItem(PASS_KEY, pass);
        mostrarPanel();
    } else {
        const err = document.getElementById('login-error');
        err.classList.remove('hidden');
        inp.style.borderColor = 'var(--red)';
        inp.focus();
        setTimeout(() => {
            err.classList.add('hidden');
            inp.style.borderColor = '';
        }, 3000);
    }
}

function cerrarSesion() {
    sessionStorage.removeItem(PASS_KEY);
    location.reload();
}

function mostrarPanel() {
    document.getElementById('login-screen').classList.add('hidden');
    document.getElementById('app-shell').classList.remove('hidden');
    refrescarTodo();
}

/* ─── NAVIGATION ─── */
function switchTab(id) {
    TABS.forEach(t => {
        const tabEl = document.getElementById('tab-' + t);
        if (tabEl) tabEl.classList.remove('active');
    });
    
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    
    const activeTab = document.getElementById('tab-' + id);
    if (activeTab) activeTab.classList.add('active');
    
    // Marcar item en sidebar
    const navItem = document.querySelector(`.nav-item[onclick*="'${id}'"]`);
    if (navItem) navItem.classList.add('active');
    
    document.getElementById('topbar-page').textContent = TAB_LABELS[id] || id;
    
    if (id === 'proyectos') cargarProyectos();
    if (id === 'historial') cargarHistorialFull();
    
    // Cerrar sidebar en móvil si está abierto
    document.querySelector('.sidebar').classList.remove('open');
}

/* ─── UI COMPONENTS ─── */
function toast(msg, type = 'ok') {
    const container = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    const icon = type === 'ok' ? '✓' : '✕';
    t.innerHTML = `<span class="toast-icon">${icon}</span> <span>${msg}</span>`;
    container.appendChild(t);
    
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(20px)';
        t.style.transition = '0.3s ease';
        setTimeout(() => t.remove(), 300);
    }, 4000);
}

function showConfirm(title, text, onConfirm) {
    const overlay = document.getElementById('confirm-overlay');
    const dialog = overlay.querySelector('.dialog');
    dialog.querySelector('h3').textContent = title;
    dialog.querySelector('p').textContent = text;
    
    overlay.classList.remove('hidden');
    
    // Limpiar eventos previos
    const btnConfirm = overlay.querySelector('.btn-dialog.confirm');
    const newConfirm = btnConfirm.cloneNode(true);
    btnConfirm.parentNode.replaceChild(newConfirm, btnConfirm);
    
    newConfirm.onclick = () => {
        onConfirm();
        closeConfirm();
    };
}

function closeConfirm() {
    document.getElementById('confirm-overlay').classList.add('hidden');
}

/* ─── DATA FETCHING ─── */
async function apiFetch(action, options = {}) {
    try {
        const url = `api.php?action=${action}`;
        const res = await fetch(url, options);
        const data = await res.json();
        if (!data.exito && data.mensaje === 'Contraseña incorrecta.') {
            cerrarSesion();
            return null;
        }
        return data;
    } catch (err) {
        console.error(`Error en API (${action}):`, err);
        toast('Error de conexión con el servidor', 'err');
        return null;
    }
}

function refrescarTodo() {
    cargarStats();
    cargarHistorial();
}

async function cargarStats() {
    const resP = await apiFetch('proyectos');
    if (resP && resP.exito) {
        document.getElementById('stat-proyectos').textContent = resP.datos.length;
        document.getElementById('stat-proyectos-sub').textContent = `${resP.datos.length} en Drive`;
        state.proyectos = resP.datos;
    }

    const resH = await apiFetch('historial');
    if (resH && resH.exito) {
        const datos = resH.datos;
        state.historial = datos;
        
        document.getElementById('stat-total').textContent = datos.length;
        document.getElementById('stat-total-sub').textContent = 'registros en total';

        const hoyStr = new Date().toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' });
        const hoyCount = datos.filter(f => f[0] && f[0].includes(hoyStr)).length;
        document.getElementById('stat-hoy').textContent = hoyCount;
        document.getElementById('stat-hoy-sub').textContent = 'subidas hoy';

        const rawsCount = datos.filter(f => f[3] && f[3].includes('RAWs')).length;
        document.getElementById('stat-raws').textContent = rawsCount;
        document.getElementById('stat-raws-sub').textContent = 'de todos los tiempos';

        renderActividad(datos.slice(0, 5));
    }
}

function renderActividad(filas) {
    const container = document.getElementById('actividad-mini');
    if (!filas || !filas.length) {
        container.innerHTML = '<div class="empty-msg">Sin actividad reciente</div>';
        return;
    }
    
    container.innerHTML = filas.map(f => {
        const etapa = (f[3] || '').substring(0, 2);
        const colors = { '01':'#ef4444', '02':'#3b82f6', '03':'#8b5cf6', '04':'#f59e0b', '05':'#10b981' };
        const color = colors[etapa] || 'var(--muted)';
        
        return `
            <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border)">
                <div style="width:8px; height:8px; border-radius:50%; background:${color}; box-shadow: 0 0 10px ${color}66"></div>
                <div style="flex:1; min-width:0">
                    <div style="font-size:.85rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                        ${escHtml(f[1] || '—')} <span style="color:var(--red-bright)">cap. ${escHtml(f[2] || '?')}</span>
                    </div>
                    <div style="font-size:.7rem; color:var(--muted); margin-top:2px">${escHtml(f[3] || '—')}</div>
                </div>
                <div style="font-size:.7rem; color:var(--muted); font-weight:500">${escHtml(f[0] || '')}</div>
            </div>
        `;
    }).join('');
    
    if (container.lastElementChild) container.lastElementChild.style.borderBottom = 'none';
}

function buildHistorialRows(datos) {
    if (!datos || !datos.length) {
        return '<tr><td colspan="6" class="empty-msg">No hay registros que coincidan.</td></tr>';
    }
    
    return datos.map((fila, index) => {
        const etapa = (fila[3] || '').substring(0, 2);
        return `
            <tr style="animation: fadeUp 0.3s ease forwards; animation-delay: ${index * 0.03}s; opacity:0">
                <td class="cell-manga">${escHtml(fila[1] || '—')}</td>
                <td class="cell-cap">#${escHtml(fila[2] || '?')}</td>
                <td><span class="badge badge-${etapa}">${escHtml(fila[3] || '—')}</span></td>
                <td class="cell-date">${escHtml(fila[0] || '—')}</td>
                <td class="cell-file" title="${escHtml(fila[4] || '')}">${escHtml(fila[4] || '—')}</td>
                <td>
                    <div class="row-actions">
                        <button class="act-btn" title="Copiar nombre" onclick="copyToClipboard('${escHtml(fila[4] || '')}')">
                            <span>⎘</span>
                        </button>
                        <button class="act-btn danger" title="Eliminar" onclick="confirmDeleteRegistro('${index}', '${escHtml(fila[1])}')">
                            <span>✕</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function cargarHistorial() {
    const body = document.getElementById('historial-body');
    body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:3rem"><span class="spinner"></span></td></tr>';
    
    const res = await apiFetch('historial');
    if (res && res.exito) {
        state.historial = res.datos;
        filterHistorial('historial-body', 20);
    }
}

async function cargarHistorialFull() {
    const body = document.getElementById('historial-full-body');
    body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:3rem"><span class="spinner"></span></td></tr>';
    
    const res = await apiFetch('historial');
    if (res && res.exito) {
        state.historial = res.datos;
        filterHistorial('historial-full-body');
    }
}

async function cargarProyectos() {
    const grid = document.getElementById('projects-grid');
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:4rem"><span class="spinner"></span></div>';
    
    const res = await apiFetch('proyectos');
    if (res && res.exito) {
        state.proyectos = res.datos;
        filterProyectos();
    }
}

/* ─── SEARCH & FILTER ─── */
function handleSearch(query) {
    state.searchQuery = query.toLowerCase();
    
    // Filtrar según la pestaña activa
    const activeTab = TABS.find(t => document.getElementById('tab-' + t).classList.contains('active'));
    
    if (activeTab === 'dashboard') filterHistorial('historial-body', 20);
    if (activeTab === 'historial') filterHistorial('historial-full-body');
    if (activeTab === 'proyectos') filterProyectos();
}

function filterHistorial(targetId, limit = null) {
    const container = document.getElementById(targetId);
    if (!container) return;
    
    let filtered = state.historial.filter(f => {
        const text = `${f[1]} ${f[2]} ${f[3]} ${f[4]}`.toLowerCase();
        return text.includes(state.searchQuery);
    });
    
    if (limit) filtered = filtered.slice(0, limit);
    
    container.innerHTML = buildHistorialRows(filtered);
}

function filterProyectos() {
    const grid = document.getElementById('projects-grid');
    if (!grid) return;
    
    const filtered = state.proyectos.filter(p => p.toLowerCase().includes(state.searchQuery));
    
    if (!filtered.length) {
        grid.innerHTML = '<div class="project-empty"><span>📁</span> No se encontraron proyectos.</div>';
        return;
    }
    
    grid.innerHTML = filtered.map(nombre => `
        <div class="project-card">
            <div class="project-icon">📖</div>
            <div class="project-name">${escHtml(nombre)}</div>
            <div class="project-meta">
                <span>📂 5 carpetas</span>
                <span>☁ Drive</span>
            </div>
            <div class="project-actions">
                <button class="act-btn" onclick="window.open('index.php?proyecto=${encodeURIComponent(nombre)}', '_blank')">Ver buscador</button>
                <button class="act-btn danger" onclick="confirmDeleteProyecto('${escHtml(nombre)}')">Eliminar</button>
            </div>
        </div>
    `).join('');
}

/* ─── ACTIONS ─── */
function crearProyectoAction(inputId, btnId, resultId) {
    const inp = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    const resContainer = document.getElementById(resultId);
    const nombre = inp.value.trim();
    const pass = sessionStorage.getItem(PASS_KEY);
    
    if (!nombre) { toast('Escribe el nombre del manga', 'err'); return; }

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span> Creando...';
    resContainer.innerHTML = '';

    const form = new FormData();
    form.append('pass', pass);
    form.append('nombre', nombre);

    fetch('api.php?action=crearProyecto', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (data.exito) {
                resContainer.innerHTML = `<div class="success-msg">✓ Proyecto "${nombre}" creado correctamente.</div>`;
                inp.value = '';
                toast('Proyecto creado: ' + nombre, 'ok');
                refrescarTodo();
            } else {
                resContainer.innerHTML = `<div class="error-msg">✕ ${data.mensaje}</div>`;
                toast(data.mensaje, 'err');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            toast('Error de conexión', 'err');
        });
}

function confirmDeleteRegistro(index, manga) {
    showConfirm(
        '¿Eliminar registro?',
        `¿Estás seguro de eliminar el registro de "${manga}"? Esta acción se reflejará en el historial.`,
        () => {
            // Aquí iría la llamada a la API si existiera eliminarRegistro
            toast('Funcionalidad de eliminación en desarrollo', 'err');
        }
    );
}

function confirmDeleteProyecto(nombre) {
    showConfirm(
        '¿Eliminar proyecto?',
        `¿Estás seguro de eliminar "${nombre}"? Esto NO borrará las carpetas en Drive por seguridad, solo lo quitará de la vista si el backend lo permite.`,
        () => {
            toast('Acción protegida por seguridad', 'err');
        }
    );
}

/* ─── UTILS ─── */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    toast('Nombre copiado al portapapeles');
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}

/* ─── INIT ─── */
document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem(PASS_KEY)) {
        mostrarPanel();
    }
});
