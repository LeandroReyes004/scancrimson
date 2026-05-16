/* ─── CONFIG & STATE ─── */
const PASS_KEY = 'cs_admin_auth';
const TABS = ['dashboard', 'proyectos', 'nuevo', 'historial', 'usuarios'];
const TAB_LABELS = { 
    dashboard: 'Dashboard', 
    proyectos: 'Proyectos', 
    nuevo: 'Nuevo proyecto', 
    historial: 'Historial completo',
    usuarios: 'Gestión de usuarios'
};

let state = {
    historial: [],
    proyectos: [],
    usuarios: [],
    progreso: {},
    searchQuery: ''
};

/* ─── AUTH (Legacy removed, handled by PHP) ─── */
function mostrarPanel() {
    refrescarTodo();
}

/* ─── NAVIGATION ─── */
function switchTab(id) {
    // Ocultar todos los tabs
    TABS.forEach(t => {
        const el = document.getElementById('tab-' + t);
        if (el) el.classList.remove('active');
        const btn = document.getElementById('htab-' + t);
        if (btn) btn.classList.remove('active');
    });

    // Mostrar el tab seleccionado
    const tabEl = document.getElementById('tab-' + id);
    if (tabEl) tabEl.classList.add('active');
    const btnEl = document.getElementById('htab-' + id);
    if (btnEl) btnEl.classList.add('active');

    // Cargar datos solo cuando se necesitan
    if (id === 'proyectos') cargarProyectos();
    else if (id === 'historial') cargarHistorialFull();
    else if (id === 'usuarios') cargarUsuarios();
    // 'dashboard' y 'nuevo' no necesitan carga adicional
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
        
        // Inyectar Token CSRF automáticamente para peticiones POST de escritura
        if (options.method && options.method.toUpperCase() === 'POST') {
            if (!options.headers) options.headers = {};
            if (options.body instanceof FormData) {
                options.body.append('csrf_token', window.csrfToken || '');
            } else if (typeof options.body === 'string') {
                try {
                    const parsed = JSON.parse(options.body);
                    parsed.csrf_token = window.csrfToken || '';
                    options.body = JSON.stringify(parsed);
                } catch(e) {}
            } else {
                options.headers['X-CSRF-Token'] = window.csrfToken || '';
            }
        }

        const res = await fetch(url, options);
        const data = await res.json();
        if (!data.exito && data.mensaje && data.mensaje.includes('Sesión expirada')) {
            location.href = 'login.php';
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
    const resH = await apiFetch('historial');
    if (resH && resH.exito) {
        state.historial = resH.datos;
        calcularProgreso(); // Calcular métricas antes de mostrar
        
        document.getElementById('stat-total').textContent = state.historial.length;
        document.getElementById('stat-total-sub').textContent = 'registros en total';

        const hoyStr = new Date().toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' });
        const hoyCount = state.historial.filter(f => f[0] && f[0].includes(hoyStr)).length;
        document.getElementById('stat-hoy').textContent = hoyCount;
        document.getElementById('stat-hoy-sub').textContent = 'subidas hoy';

        const rawsCount = state.historial.filter(f => f[3] && f[3].includes('RAWs')).length;
        document.getElementById('stat-raws').textContent = rawsCount;
        document.getElementById('stat-raws-sub').textContent = 'de todos los tiempos';

        renderActividad(state.historial.slice(0, 5));
    }

    const resP = await apiFetch('proyectos');
    if (resP && resP.exito) {
        state.proyectos = resP.datos;
        document.getElementById('stat-proyectos').textContent = state.proyectos.length;
        document.getElementById('stat-proyectos-sub').textContent = `${state.proyectos.length} en Drive`;
    }
}

function calcularProgreso() {
    const prog = {};
    state.historial.forEach(f => {
        const manga = f[1];
        const cap = parseFloat(f[2]);
        if (manga && !isNaN(cap)) {
            if (!prog[manga] || cap > prog[manga]) {
                prog[manga] = cap;
            }
        }
    });
    state.progreso = prog;
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

function buildHistorialRows(datos, offset = 0) {
    if (!datos || !datos.length) {
        return '<tr><td colspan="6" class="empty-msg">No hay registros que coincidan.</td></tr>';
    }
    
    return datos.map((fila, index) => {
        const realIndex = state.historial.indexOf(fila);
        const etapa = (fila[3] || '').substring(0, 2);
        const estado = fila[5] === 'Inactivo' ? 'Inactivo' : 'Activo';
        const isInactive = estado === 'Inactivo';
        
        return `
            <tr id="row-${realIndex}" style="animation: fadeUp 0.3s ease forwards; animation-delay: ${index * 0.03}s; opacity:0; ${isInactive ? 'filter:grayscale(0.7); opacity:0.55' : ''}">
                <td class="cell-manga">${escHtml(fila[1] || '—')}</td>
                <td class="cell-cap">#${escHtml(fila[2] || '?')}</td>
                <td><span class="badge badge-${etapa}">${escHtml(fila[3] || '—')}</span></td>
                <td class="cell-date">${escHtml(fila[0] || '—')}</td>
                <td><span class="estado-badge" id="estado-badge-${realIndex}" style="background:${isInactive ? 'var(--muted)33' : 'var(--c5b)'}; color:${isInactive ? 'var(--muted2)' : 'var(--c5)'}; border:1px solid ${isInactive ? 'var(--muted)' : 'var(--c5)'}; border-radius:20px; padding:2px 10px; font-size:.75rem; font-weight:600">${estado}</span></td>
                <td>
                    <div class="row-actions">
                        <button class="act-btn" title="Editar" onclick="openEditModal(${realIndex})">
                            ✏️ Editar
                        </button>
                        <button class="act-btn ${isInactive ? '' : 'danger'}" id="btn-estado-${realIndex}" onclick="toggleEstadoRegistro(${realIndex}, '${isInactive ? 'Activo' : 'Inactivo'}', this)">
                            ${isInactive ? '◎ Activar' : '⊘ Inactivar'}
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

/* ─── USUARIOS ─── */
async function cargarUsuarios() {
    const body = document.getElementById('usuarios-body');
    if (!body) return;
    body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:3rem"><span class="spinner"></span></td></tr>';
    
    const res = await apiFetch('listarUsuarios');
    if (res && res.exito) {
        state.usuarios = res.datos;
        renderUsuarios();
    }
}

function renderUsuarios() {
    const body = document.getElementById('usuarios-body');
    if (!body || !state.usuarios.length) {
        body.innerHTML = '<tr><td colspan="5" class="empty-msg">No hay usuarios.</td></tr>';
        return;
    }

    body.innerHTML = state.usuarios.map(u => `
        <tr>
            <td style="font-weight:600">${escHtml(u.usuario)}</td>
            <td><span class="badge ${u.rol === 'admin' ? 'badge-01' : 'badge-02'}">${u.rol.toUpperCase()}</span></td>
            <td>
                <span class="estado-badge" style="background:${u.activo ? 'var(--c5b)' : 'var(--muted)33'}; color:${u.activo ? 'var(--c5)' : 'var(--muted2)'}; border:1px solid ${u.activo ? 'var(--c5)' : 'var(--muted)'}">
                    ${u.activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td style="color:var(--muted); font-size:0.8rem">${u.creado}</td>
            <td>
                <div class="row-actions">
                    <button class="act-btn" onclick="toggleUsuario(${u.id}, ${u.activo ? 0 : 1})">
                        ${u.activo ? 'Desactivar' : 'Activar'}
                    </button>
                    <button class="act-btn danger" onclick="confirmEliminarUsuario(${u.id}, '${u.usuario}')">Eliminar</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function openNuevoUsuario() {
    document.getElementById('user-modal').classList.remove('hidden');
    document.getElementById('user-modal-overlay').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('user-modal').classList.add('hidden');
    document.getElementById('user-modal-overlay').classList.add('hidden');
}

async function guardarNuevoUsuario() {
    const user = document.getElementById('new-user-name').value.trim();
    const pass = document.getElementById('new-user-pass').value;
    const rol = document.getElementById('new-user-rol').value;

    if (!user || !pass) { toast('Completa todos los campos', 'err'); return; }

    const form = new FormData();
    form.append('usuario', user);
    form.append('password', pass);
    form.append('rol', rol);

    const res = await apiFetch('crearUsuario', { method: 'POST', body: form });
    if (res && res.exito) {
        toast(res.mensaje);
        closeUserModal();
        cargarUsuarios();
    } else {
        toast(res?.mensaje || 'Error al crear usuario', 'err');
    }
}

async function toggleUsuario(id, activo) {
    const form = new FormData();
    form.append('id', id);
    form.append('activo', activo);

    const res = await apiFetch('toggleUsuario', { method: 'POST', body: form });
    if (res && res.exito) {
        toast(res.mensaje);
        cargarUsuarios();
    } else {
        toast(res?.mensaje || 'Error', 'err');
    }
}

function confirmEliminarUsuario(id, nombre) {
    showConfirm('¿Eliminar usuario?', `¿Estás seguro de eliminar permanentemente a "${nombre}"?`, async () => {
        const form = new FormData();
        form.append('id', id);
        const res = await apiFetch('eliminarUsuario', { method: 'POST', body: form });
        if (res && res.exito) {
            toast(res.mensaje);
            cargarUsuarios();
        } else {
            toast(res?.mensaje || 'Error', 'err');
        }
    });
}

/* ─── ACTIONS ─── */
function crearProyectoAction(inputId, btnId, resultId) {
    const inp = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    const resContainer = document.getElementById(resultId);
    const nombre = inp.value.trim();
    
    if (!nombre) { toast('Escribe el nombre del manga', 'err'); return; }

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span> Creando...';
    resContainer.innerHTML = '';

    const form = new FormData();
    form.append('nombre', nombre);

    apiFetch('crearProyecto', { method: 'POST', body: form })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (data && data.exito) {
                resContainer.innerHTML = `<div class="success-msg">✓ Proyecto "${nombre}" creado correctamente.</div>`;
                inp.value = '';
                toast('Proyecto creado: ' + nombre, 'ok');
                refrescarTodo();
            } else {
                const msg = data?.mensaje || 'Error al crear proyecto';
                resContainer.innerHTML = `<div class="error-msg">✕ ${msg}</div>`;
                toast(msg, 'err');
            }
        });
}

/* ─── CARGAR PROYECTOS ─── */
async function cargarProyectos() {
    const grid = document.getElementById('projects-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="loading-cell" style="grid-column:1/-1; padding:4rem; text-align:center"><span class="spinner"></span> Cargando proyectos…</div>';

    const res = await apiFetch('proyectos');
    if (res && res.exito && res.datos) {
        state.proyectos = res.datos;
        // Actualizar stat del dashboard si está disponible
        const statEl = document.getElementById('stat-proyectos');
        if (statEl) statEl.textContent = res.datos.length;
        filterProyectos(); // Renderizar usando filterProyectos
    } else {
        grid.innerHTML = '<div class="empty-msg" style="grid-column:1/-1">No se pudieron cargar los proyectos.</div>';
    }
}

let editIndex = -1;

function openEditModal(realIndex) {
    const data = state.historial[realIndex];
    if (!data) return;

    editIndex = realIndex;
    document.getElementById('edit-manga').value = data[1] || '';
    document.getElementById('edit-cap').value   = data[2] || '';
    document.getElementById('edit-etapa').value = data[3] || '01. RAWs';

    // Mostrar drawer lateral
    document.getElementById('edit-drawer').classList.remove('hidden');
    document.getElementById('edit-drawer-overlay').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-drawer').classList.add('hidden');
    document.getElementById('edit-drawer-overlay').classList.add('hidden');
    editIndex = -1;
}

async function guardarEdicion() {
    const manga = document.getElementById('edit-manga').value.trim();
    const cap = document.getElementById('edit-cap').value.trim();
    const etapa = document.getElementById('edit-etapa').value;
    const pass = ''; // Legacy removed

    if (!manga || !cap) { toast('Completa los campos', 'err'); return; }

    const btn = document.querySelector('#edit-drawer .btn-primary');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const form = new FormData();
    // form.append('pass', pass); // Legacy removed
    form.append('fila', editIndex + 2);
    form.append('manga', manga);
    form.append('cap', cap);
    form.append('etapa', etapa);

    const res = await apiFetch('editarRegistro', { method: 'POST', body: form });
    
    btn.disabled = false;
    btn.textContent = 'Guardar Cambios';

    if (res && res.exito) {
        // Actualizar el estado local sin recargar toda la tabla
        state.historial[editIndex][1] = manga;
        state.historial[editIndex][2] = cap;
        state.historial[editIndex][3] = etapa;

        const row = document.getElementById('row-' + editIndex);
        if (row) {
            const etapaCod = etapa.substring(0, 2);
            row.cells[0].textContent = manga;
            row.cells[1].textContent = '#' + cap;
            row.cells[2].innerHTML = `<span class="badge badge-${etapaCod}">${escHtml(etapa)}</span>`;
        }

        toast('Registro actualizado correctamente');
        closeEditModal();
        calcularProgreso(); // Actualizar métricas en memoria
    } else {
        toast(res?.mensaje || 'Error al guardar', 'err');
    }
}

async function toggleEstadoRegistro(realIndex, nuevoEstado, btnEl) {
    const isInactive = nuevoEstado === 'Inactivo'; // Si el nuevo estado es Inactivo, actualmente estaba Activo
    const pass = ''; // Legacy removed

    // Feedback inmediato: deshabilitar botón
    btnEl.disabled = true;
    btnEl.textContent = '...';

    const form = new FormData();
    // form.append('pass', pass); // Legacy removed
    form.append('fila', realIndex + 2);
    form.append('estado', nuevoEstado);

    const res = await apiFetch('cambiarEstado', { method: 'POST', body: form });

    if (res && res.exito) {
        // Actualizar el DOM local SIN recargar toda la tabla
        state.historial[realIndex][5] = nuevoEstado;
        const row = document.getElementById('row-' + realIndex);
        const badge = document.getElementById('estado-badge-' + realIndex);
        const nowInactive = nuevoEstado === 'Inactivo';

        if (row) {
            row.style.filter = nowInactive ? 'grayscale(0.7)' : '';
            row.style.opacity = nowInactive ? '0.55' : '1';
        }
        if (badge) {
            badge.textContent = nuevoEstado;
            badge.style.background = nowInactive ? 'var(--muted)33' : 'var(--c5b)';
            badge.style.color = nowInactive ? 'var(--muted2)' : 'var(--c5)';
            badge.style.borderColor = nowInactive ? 'var(--muted)' : 'var(--c5)';
        }

        // Actualizar el botón al nuevo estado contrario
        btnEl.disabled = false;
        btnEl.className = `act-btn ${nowInactive ? '' : 'danger'}`;
        btnEl.textContent = nowInactive ? '◎ Activar' : '⊘ Inactivar';
        btnEl.onclick = () => toggleEstadoRegistro(realIndex, nowInactive ? 'Activo' : 'Inactivo', btnEl);

        toast(`Registro ${nuevoEstado.toLowerCase()}`);
    } else {
        btnEl.disabled = false;
        btnEl.textContent = isInactive ? '⊘ Inactivar' : '◎ Activar';
        toast(res?.mensaje || 'Error al cambiar estado', 'err');
    }
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
    mostrarPanel();
});
