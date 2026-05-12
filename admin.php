<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<style>
/* ─── RESET & BASE ─── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --red:        #dc2020;
  --red-dim:    rgba(220,32,32,.18);
  --red-glow:   rgba(220,32,32,.08);
  --bg:         #0a0a0d;
  --bg2:        #0f0f13;
  --sidebar:    #0d0d10;
  --card:       #111116;
  --border:     #1c1c22;
  --border2:    #252530;
  --text:       #e8e8ec;
  --muted:      #55555f;
  --muted2:     #888890;
  --font-head:  'Bebas Neue', sans-serif;
  --font-body:  'DM Sans', sans-serif;
  --radius:     10px;
  --radius-sm:  7px;
  --sidebar-w:  230px;
  --header-h:   60px;
  /* etapa colours */
  --c1: #ef4444; --c1b: rgba(239,68,68,.12);
  --c2: #3b82f6; --c2b: rgba(59,130,246,.12);
  --c3: #8b5cf6; --c3b: rgba(139,92,246,.12);
  --c4: #f59e0b; --c4b: rgba(245,158,11,.12);
  --c5: #10b981; --c5b: rgba(16,185,129,.12);
}
html, body { height: 100%; }
body {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
  line-height: 1.5;
  overflow-x: hidden;
}
a { text-decoration: none; color: inherit; }
input, select, button, textarea { font-family: inherit; }

/* ─── LOGIN SCREEN ─── */
.login-screen {
  position: fixed; inset: 0;
  background: var(--bg);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
}
.login-screen.hidden { display: none; }
.login-bg {
  position: absolute; inset: 0; overflow: hidden; pointer-events: none;
}
.login-bg::before {
  content: '';
  position: absolute;
  top: -200px; left: 50%; transform: translateX(-50%);
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(220,32,32,.08) 0%, transparent 70%);
}
.login-card {
  position: relative;
  background: var(--card);
  border: 1px solid var(--border2);
  border-radius: 16px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 360px;
  box-shadow: 0 40px 80px rgba(0,0,0,.6);
}
.login-card::before {
  content: '';
  display: block;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--red), transparent);
  border-radius: 3px 3px 0 0;
  position: absolute; top: 0; left: 0; right: 0;
}
.login-logo {
  display: flex; align-items: center; gap: 10px;
  justify-content: center;
  margin-bottom: 2rem;
}
.login-logo-icon {
  width: 38px; height: 38px;
  background: var(--red-dim);
  border: 1px solid var(--red-dim);
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
}
.login-logo-text {
  font-family: var(--font-head);
  font-size: 1.3rem;
  letter-spacing: .08em;
}
.login-logo-text span { color: var(--red); }
.login-title {
  font-size: .8rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 2px;
  text-align: center;
  margin-bottom: 1.5rem;
}
.field-group { margin-bottom: 1rem; }
.field-label {
  display: block;
  font-size: .7rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: .5rem;
  font-weight: 500;
}
.field-input {
  width: 100%;
  background: var(--bg2);
  border: 1px solid var(--border2);
  border-radius: var(--radius-sm);
  padding: .65rem .9rem;
  color: var(--text);
  font-size: .9rem;
  transition: border-color .2s;
  outline: none;
}
.field-input:focus { border-color: var(--red); }
.btn-primary {
  width: 100%;
  padding: .75rem;
  background: var(--red);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-size: .9rem;
  font-weight: 500;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: opacity .2s, transform .1s;
  margin-top: 1rem;
}
.btn-primary:hover { opacity: .88; }
.btn-primary:active { transform: scale(.98); }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; }
.error-msg {
  background: rgba(220,32,32,.1);
  border: 1px solid rgba(220,32,32,.3);
  color: #f87171;
  padding: .65rem .9rem;
  border-radius: var(--radius-sm);
  font-size: .82rem;
  margin-top: .75rem;
  text-align: center;
}
.error-msg.hidden { display: none; }
.success-msg {
  background: rgba(16,185,129,.1);
  border: 1px solid rgba(16,185,129,.3);
  color: #34d399;
  padding: .65rem .9rem;
  border-radius: var(--radius-sm);
  font-size: .82rem;
  margin-top: .75rem;
}

/* ─── APP SHELL ─── */
.app-shell { display: flex; min-height: 100vh; }
.app-shell.hidden { display: none; }

/* ─── SIDEBAR ─── */
.sidebar {
  width: var(--sidebar-w);
  background: var(--sidebar);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  transition: transform .3s;
}
.sidebar-logo {
  padding: 1.25rem 1.25rem 1rem;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 10px;
}
.sidebar-logo-icon {
  width: 34px; height: 34px;
  background: var(--red-dim);
  border: 1px solid rgba(220,32,32,.25);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem;
  flex-shrink: 0;
}
.sidebar-logo-text {
  font-family: var(--font-head);
  font-size: 1.1rem;
  letter-spacing: .08em;
  line-height: 1;
}
.sidebar-logo-text span { color: var(--red); }
.sidebar-logo-badge {
  margin-left: auto;
  font-size: .55rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1px;
  border: 1px solid var(--border2);
  padding: 2px 6px;
  border-radius: 4px;
}
.nav-section-label {
  padding: 1.1rem 1.1rem .4rem;
  font-size: .62rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 2px;
  font-weight: 500;
}
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: .55rem 1rem;
  margin: 1px .6rem;
  border-radius: var(--radius-sm);
  font-size: .83rem;
  color: var(--muted2);
  cursor: pointer;
  transition: all .15s;
  position: relative;
  text-decoration: none;
}
.nav-item:hover { background: rgba(255,255,255,.04); color: var(--text); }
.nav-item.active {
  background: var(--red-glow);
  color: #f87171;
  border: 1px solid var(--red-dim);
}
.nav-item.active::before {
  content: '';
  position: absolute; left: -1px; top: 20%; bottom: 20%;
  width: 2px; background: var(--red);
  border-radius: 2px;
}
.nav-icon { font-size: 1rem; width: 18px; text-align: center; flex-shrink: 0; }
.nav-badge {
  margin-left: auto;
  background: var(--red);
  color: #fff;
  font-size: .62rem;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
}
.sidebar-footer {
  margin-top: auto;
  padding: .9rem;
  border-top: 1px solid var(--border);
}
.user-chip {
  display: flex; align-items: center; gap: 10px;
  padding: .65rem .75rem;
  background: var(--bg2);
  border: 1px solid var(--border2);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: border-color .2s;
}
.user-chip:hover { border-color: var(--border2); background: rgba(255,255,255,.03); }
.user-avatar {
  width: 30px; height: 30px;
  background: linear-gradient(135deg, var(--red), #7f1d1d);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .7rem;
  font-weight: 600;
  color: #fff;
  flex-shrink: 0;
}
.user-info { flex: 1; min-width: 0; }
.user-name { font-size: .8rem; font-weight: 500; color: var(--text); }
.user-role { font-size: .68rem; color: var(--muted); margin-top: 1px; }
.user-logout {
  color: var(--muted);
  font-size: .85rem;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: color .15s;
  background: none;
  border: none;
  display: flex;
}
.user-logout:hover { color: #f87171; }

/* ─── MAIN CONTENT ─── */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
.top-bar {
  height: var(--header-h);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
  padding: 0 2rem;
  gap: 1rem;
  position: sticky; top: 0;
  background: rgba(10,10,13,.85);
  backdrop-filter: blur(12px);
  z-index: 50;
}
.top-bar-title {
  font-size: .75rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  display: flex; align-items: center; gap: 6px;
}
.top-bar-sep { color: var(--border2); }
.top-bar-page { color: var(--muted2); }
.top-bar-actions { margin-left: auto; display: flex; gap: .6rem; }
.btn-sm {
  padding: .45rem .9rem;
  border-radius: var(--radius-sm);
  font-size: .78rem;
  font-weight: 500;
  cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  transition: all .15s;
}
.btn-ghost {
  background: transparent;
  border: 1px solid var(--border2);
  color: var(--muted2);
}
.btn-ghost:hover { border-color: var(--muted); color: var(--text); background: rgba(255,255,255,.03); }
.btn-accent {
  background: var(--red);
  border: none;
  color: #fff;
}
.btn-accent:hover { opacity: .85; }

.page-body { padding: 2rem; flex: 1; }

/* ─── SECTION HEADER ─── */
.section-head {
  margin-bottom: 1.75rem;
}
.section-head h1 {
  font-family: var(--font-head);
  font-size: 2rem;
  letter-spacing: .06em;
  line-height: 1;
  color: var(--text);
}
.section-head h1 span { color: var(--red); }
.section-head p {
  font-size: .78rem;
  color: var(--muted);
  margin-top: .4rem;
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

/* ─── STATS GRID ─── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}
@media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
.stat-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
  position: relative;
  overflow: hidden;
  transition: border-color .2s;
}
.stat-card:hover { border-color: var(--border2); }
.stat-card::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
}
.sc1::after { background: var(--c1); }
.sc2::after { background: var(--c2); }
.sc3::after { background: var(--c3); }
.sc4::after { background: var(--c5); }
.stat-value {
  font-family: var(--font-head);
  font-size: 2.4rem;
  line-height: 1;
  color: var(--text);
  margin-bottom: .3rem;
}
.stat-label {
  font-size: .7rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}
.stat-trend {
  display: flex; align-items: center; gap: 4px;
  font-size: .72rem;
  margin-top: .5rem;
}
.trend-up { color: var(--c5); }
.trend-neutral { color: var(--muted); }

/* ─── TABS ─── */
.tabs {
  display: flex;
  gap: 0;
  border-bottom: 1px solid var(--border);
  margin-bottom: 1.5rem;
}
.tab-btn {
  padding: .6rem 1.1rem;
  font-size: .8rem;
  color: var(--muted);
  cursor: pointer;
  border: none;
  background: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all .15s;
  display: flex; align-items: center; gap: 6px;
}
.tab-btn:hover { color: var(--muted2); }
.tab-btn.active { color: var(--red); border-bottom-color: var(--red); }
.tab-icon { font-size: .9rem; }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ─── DASHBOARD GRID ─── */
.dash-grid {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 1.25rem;
  align-items: start;
}
@media (max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } }

/* ─── PANELS ─── */
.panel {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 1.25rem;
}
.panel:last-child { margin-bottom: 0; }
.panel-header {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.panel-title {
  font-size: .82rem;
  font-weight: 500;
  color: var(--text);
  display: flex; align-items: center; gap: 7px;
}
.panel-title-icon { font-size: .95rem; color: var(--red); }
.panel-body { padding: 1.25rem; }

/* ─── CREAR PROYECTO ─── */
.etapa-list { display: flex; flex-direction: column; gap: .4rem; margin-bottom: 1.25rem; }
.etapa-row {
  display: flex; align-items: center; gap: 8px;
  padding: .5rem .75rem;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: .8rem;
}
.etapa-dot {
  width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}
.etapa-row:nth-child(1) .etapa-dot { background: var(--c1); }
.etapa-row:nth-child(2) .etapa-dot { background: var(--c2); }
.etapa-row:nth-child(3) .etapa-dot { background: var(--c3); }
.etapa-row:nth-child(4) .etapa-dot { background: var(--c4); }
.etapa-row:nth-child(5) .etapa-dot { background: var(--c5); }
.etapa-name { color: var(--muted2); flex: 1; }
.etapa-check { margin-left: auto; color: var(--c5); font-size: .8rem; }

.btn-full {
  width: 100%;
  padding: .7rem;
  background: var(--red);
  border: none;
  border-radius: var(--radius-sm);
  color: #fff;
  font-size: .85rem;
  font-weight: 500;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: opacity .2s, transform .1s;
}
.btn-full:hover { opacity: .85; }
.btn-full:active { transform: scale(.98); }
.btn-full:disabled { opacity: .5; cursor: not-allowed; }

/* ─── HISTORIAL TABLE ─── */
.table-scroll { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
  text-align: left;
  padding: .7rem 1rem;
  font-size: .67rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  font-weight: 500;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
.data-table td {
  padding: .75rem 1rem;
  border-bottom: 1px solid rgba(28,28,34,.7);
  font-size: .82rem;
  color: var(--muted2);
}
.data-table tbody tr { transition: background .12s; }
.data-table tbody tr:hover td { background: rgba(255,255,255,.02); }
.data-table tbody tr:last-child td { border-bottom: none; }
.cell-manga { font-weight: 500; color: var(--text); }
.cell-cap { color: var(--red); font-weight: 500; }
.cell-date { font-size: .75rem; color: var(--muted); white-space: nowrap; }
.cell-file { font-size: .75rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ─── BADGES ─── */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: .2rem .55rem;
  border-radius: 5px;
  font-size: .67rem;
  font-weight: 600;
  letter-spacing: .5px;
  white-space: nowrap;
  border: 1px solid transparent;
}
.badge-01 { background: var(--c1b); color: var(--c1); border-color: rgba(239,68,68,.2); }
.badge-02 { background: var(--c2b); color: var(--c2); border-color: rgba(59,130,246,.2); }
.badge-03 { background: var(--c3b); color: var(--c3); border-color: rgba(139,92,246,.2); }
.badge-04 { background: var(--c4b); color: var(--c4); border-color: rgba(245,158,11,.2); }
.badge-05 { background: rgba(16,185,129,.12); color: var(--c5); border-color: rgba(16,185,129,.2); }

/* ─── ROW ACTIONS ─── */
.row-actions { display: flex; gap: 5px; justify-content: flex-end; }
.act-btn {
  background: var(--bg2);
  border: 1px solid var(--border2);
  border-radius: 5px;
  padding: 4px 7px;
  color: var(--muted);
  cursor: pointer;
  font-size: .8rem;
  transition: all .15s;
  display: flex; align-items: center;
}
.act-btn:hover { color: var(--text); border-color: var(--muted); }
.act-btn.danger:hover { color: #f87171; border-color: rgba(239,68,68,.3); }

/* ─── PROJECTS GRID ─── */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: .9rem;
}
.project-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.1rem;
  cursor: pointer;
  transition: all .15s;
  display: flex; flex-direction: column; gap: .5rem;
}
.project-card:hover { border-color: var(--border2); background: rgba(255,255,255,.025); transform: translateY(-1px); }
.project-icon {
  width: 36px; height: 36px;
  background: var(--red-glow);
  border: 1px solid var(--red-dim);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
}
.project-name { font-size: .85rem; font-weight: 500; color: var(--text); }
.project-meta { font-size: .72rem; color: var(--muted); }
.project-empty {
  grid-column: 1 / -1;
  text-align: center;
  padding: 3rem;
  color: var(--muted);
  font-size: .85rem;
}

/* ─── SPINNER ─── */
.spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.15);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
  display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── EMPTY / LOADING STATES ─── */
.loading-rows td { text-align: center; padding: 2.5rem; color: var(--muted); }
.empty-state { text-align: center; padding: 3rem; color: var(--muted); font-size: .85rem; }
.empty-state span { display: block; font-size: 2rem; margin-bottom: .75rem; }

/* ─── TOAST ─── */
.toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: .5rem; }
.toast {
  background: var(--card);
  border: 1px solid var(--border2);
  border-radius: var(--radius-sm);
  padding: .75rem 1rem;
  font-size: .82rem;
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 8px 30px rgba(0,0,0,.5);
  animation: toastIn .25s ease;
  min-width: 240px;
  max-width: 320px;
}
.toast.ok { border-left: 3px solid var(--c5); }
.toast.err { border-left: 3px solid var(--red); }
.toast-icon { font-size: 1rem; }
@keyframes toastIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

/* ─── CONFIRM DIALOG ─── */
.overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.7);
  backdrop-filter: blur(6px);
  z-index: 500;
  display: flex; align-items: center; justify-content: center;
}
.overlay.hidden { display: none; }
.dialog {
  background: var(--card);
  border: 1px solid var(--border2);
  border-radius: 14px;
  padding: 2rem;
  max-width: 340px;
  width: 90%;
  text-align: center;
}
.dialog-icon { font-size: 2.5rem; margin-bottom: 1rem; }
.dialog h3 { font-size: 1rem; font-weight: 500; margin-bottom: .5rem; }
.dialog p { font-size: .82rem; color: var(--muted2); margin-bottom: 1.5rem; }
.dialog-actions { display: flex; gap: .75rem; }
.btn-dialog {
  flex: 1; padding: .65rem;
  border-radius: var(--radius-sm);
  font-size: .85rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity .15s;
}
.btn-dialog.cancel { background: var(--bg2); border: 1px solid var(--border2); color: var(--muted2); }
.btn-dialog.confirm { background: var(--red); border: none; color: #fff; }
.btn-dialog:hover { opacity: .8; }

/* ─── NOISE / GRID BG ─── */
.bg-grid {
  position: fixed; inset: 0; pointer-events: none; z-index: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.015) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.015) 1px, transparent 1px);
  background-size: 40px 40px;
}
</style>
</head>
<body>

<div class="bg-grid"></div>

<!-- ─── LOGIN ─── -->
<div id="login-screen" class="login-screen">
  <div class="login-bg"></div>
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
    <button class="btn-primary" onclick="verificarLogin()">
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
      <div class="sidebar-logo-badge">Admin</div>
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
      <span class="nav-badge" id="nav-badge-subidas">—</span>
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
      <div class="top-bar-title">
        <span>Crimson Scan</span>
        <span class="top-bar-sep">/</span>
        <span class="top-bar-page" id="topbar-page">Dashboard</span>
      </div>
      <div class="top-bar-actions">
        <button class="btn-sm btn-ghost" onclick="refrescarTodo()">↺ Refrescar</button>
        <button class="btn-sm btn-accent" onclick="switchTab('nuevo')">+ Nuevo proyecto</button>
      </div>
    </div>

    <div class="page-body">

      <!-- ══ TAB: DASHBOARD ══ -->
      <div id="tab-dashboard" class="tab-content active">
        <div class="section-head">
          <p>Admin · Panel de control</p>
          <h1>Crimson <span>Control</span></h1>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
          <div class="stat-card sc1">
            <div class="stat-value" id="stat-proyectos">—</div>
            <div class="stat-label">Proyectos activos</div>
            <div class="stat-trend trend-neutral" id="stat-proyectos-sub">Cargando…</div>
          </div>
          <div class="stat-card sc2">
            <div class="stat-value" id="stat-total">—</div>
            <div class="stat-label">Total subidas</div>
            <div class="stat-trend trend-neutral" id="stat-total-sub">Cargando…</div>
          </div>
          <div class="stat-card sc3">
            <div class="stat-value" id="stat-hoy">—</div>
            <div class="stat-label">Subidas hoy</div>
            <div class="stat-trend trend-neutral" id="stat-hoy-sub">—</div>
          </div>
          <div class="stat-card sc4">
            <div class="stat-value" id="stat-raws">—</div>
            <div class="stat-label">RAWs pendientes</div>
            <div class="stat-trend trend-neutral" id="stat-raws-sub">—</div>
          </div>
        </div>

        <!-- Dashboard grid -->
        <div class="dash-grid">
          <!-- LEFT COL -->
          <div>
            <!-- Crear Proyecto rápido -->
            <div class="panel">
              <div class="panel-header">
                <div class="panel-title">
                  <span class="panel-title-icon">◫</span> Crear proyecto
                </div>
              </div>
              <div class="panel-body">
                <div class="field-group">
                  <label class="field-label">Nombre del manga</label>
                  <input id="inp-proyecto-nuevo" type="text" class="field-input" placeholder="Ej: Solo Leveling">
                </div>
                <div class="field-group" style="margin-bottom:.9rem">
                  <label class="field-label">Carpetas que se crearán</label>
                  <div class="etapa-list">
                    <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">01. RAWs</span><span class="etapa-check">✓</span></div>
                    <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">02. Traducción</span><span class="etapa-check">✓</span></div>
                    <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">03. Limpieza y Redibujo</span><span class="etapa-check">✓</span></div>
                    <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">04. Typos</span><span class="etapa-check">✓</span></div>
                    <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">05. Control de Calidad</span><span class="etapa-check">✓</span></div>
                  </div>
                </div>
                <button id="btn-crear" class="btn-full" onclick="crearProyecto()">
                  <span>Crear en Google Drive</span>
                </button>
                <div id="crear-resultado" style="margin-top:.75rem"></div>
              </div>
            </div>

            <!-- Actividad reciente (mini) -->
            <div class="panel">
              <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon">◎</span> Actividad reciente</div>
              </div>
              <div class="panel-body" id="actividad-mini" style="padding-top:.5rem; padding-bottom:.5rem">
                <div style="color:var(--muted);font-size:.8rem;text-align:center;padding:1.5rem 0">Cargando…</div>
              </div>
            </div>
          </div>

          <!-- RIGHT COL: Historial table -->
          <div class="panel">
            <div class="panel-header">
              <div class="panel-title"><span class="panel-title-icon">≡</span> Historial reciente</div>
              <button class="btn-sm btn-ghost" onclick="cargarHistorial()">↺ Refrescar</button>
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
                    <th></th>
                  </tr>
                </thead>
                <tbody id="historial-body">
                  <tr class="loading-rows"><td colspan="6"><span class="spinner"></span> Cargando historial…</td></tr>
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
        <div class="panel" style="margin-bottom:0">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-title-icon">◫</span> Proyectos en Google Drive</div>
            <button class="btn-sm btn-ghost" onclick="cargarProyectos()">↺ Refrescar</button>
          </div>
          <div class="panel-body">
            <div id="projects-grid" class="projects-grid">
              <div class="project-empty">Cargando proyectos…</div>
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
        <div class="dash-grid">
          <div class="panel" style="margin-bottom:0">
            <div class="panel-header">
              <div class="panel-title"><span class="panel-title-icon">⊕</span> Crear proyecto en Drive</div>
            </div>
            <div class="panel-body">
              <p style="font-size:.82rem;color:var(--muted2);margin-bottom:1.25rem;line-height:1.6">
                Se crearán automáticamente las 5 carpetas de etapa en la raíz de Google Drive configurada en <code style="background:var(--bg2);padding:1px 5px;border-radius:4px;font-size:.78rem">config.php</code>.
              </p>
              <div class="field-group">
                <label class="field-label">Nombre del manga</label>
                <input id="inp-proyecto-nuevo2" type="text" class="field-input" placeholder="Ej: Solo Leveling">
              </div>
              <div class="field-group" style="margin-bottom:1.25rem">
                <label class="field-label">Carpetas que se crearán</label>
                <div class="etapa-list">
                  <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">01. RAWs</span><span class="etapa-check">✓</span></div>
                  <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">02. Traducción</span><span class="etapa-check">✓</span></div>
                  <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">03. Limpieza y Redibujo</span><span class="etapa-check">✓</span></div>
                  <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">04. Typos</span><span class="etapa-check">✓</span></div>
                  <div class="etapa-row"><div class="etapa-dot"></div><span class="etapa-name">05. Control de Calidad</span><span class="etapa-check">✓</span></div>
                </div>
              </div>
              <button id="btn-crear2" class="btn-full" onclick="crearProyecto2()">
                <span>Crear en Google Drive</span>
              </button>
              <div id="crear-resultado2" style="margin-top:.75rem"></div>
            </div>
          </div>
          <div>
            <div class="panel">
              <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon">◉</span> Requisitos</div>
              </div>
              <div class="panel-body" style="display:flex;flex-direction:column;gap:.9rem">
                <div style="display:flex;gap:10px;align-items:flex-start">
                  <span style="color:var(--c5);font-size:.9rem;margin-top:1px">✓</span>
                  <div>
                    <div style="font-size:.82rem;font-weight:500;color:var(--text)">Apps Script configurado</div>
                    <div style="font-size:.75rem;color:var(--muted);margin-top:2px">APPS_SCRIPT_URL debe estar definida en config.php</div>
                  </div>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                  <span style="color:var(--c5);font-size:.9rem;margin-top:1px">✓</span>
                  <div>
                    <div style="font-size:.82rem;font-weight:500;color:var(--text)">Carpeta raíz en Drive</div>
                    <div style="font-size:.75rem;color:var(--muted);margin-top:2px">CARPETA_RAIZ_ID debe apuntar a la carpeta correcta</div>
                  </div>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                  <span style="color:var(--c4);font-size:.9rem;margin-top:1px">⚠</span>
                  <div>
                    <div style="font-size:.82rem;font-weight:500;color:var(--text)">Nombre único</div>
                    <div style="font-size:.75rem;color:var(--muted);margin-top:2px">No uses nombres que ya existen en Drive para evitar duplicados</div>
                  </div>
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
          <h1>Historial <span>completo</span></h1>
        </div>
        <div class="panel" style="margin-bottom:0">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-title-icon">≡</span> Todos los registros</div>
            <button class="btn-sm btn-ghost" onclick="cargarHistorialFull()">↺ Refrescar</button>
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
                  <th></th>
                </tr>
              </thead>
              <tbody id="historial-full-body">
                <tr class="loading-rows"><td colspan="6">Cargando…</td></tr>
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
    <p>Esta acción no se puede deshacer. El registro se eliminará del historial en Google Sheets.</p>
    <div class="dialog-actions">
      <button class="btn-dialog cancel" onclick="closeConfirm()">Cancelar</button>
      <button class="btn-dialog confirm" onclick="doDelete()">Eliminar</button>
    </div>
  </div>
</div>

<script>
/* ─── AUTH ─── */
const PASS_KEY = 'cs_admin_auth';

function verificarLogin() {
  const pass = document.getElementById('inp-pass').value;
  if (pass === 'crimson2026') {
    sessionStorage.setItem(PASS_KEY, pass);
    mostrarPanel();
  } else {
    const err = document.getElementById('login-error');
    err.classList.remove('hidden');
    const inp = document.getElementById('inp-pass');
    inp.style.borderColor = 'var(--red)';
    setTimeout(() => inp.style.borderColor = '', 2000);
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

/* ─── TAB NAVIGATION ─── */
const TABS = ['dashboard','proyectos','nuevo','historial'];
const TAB_LABELS = { dashboard:'Dashboard', proyectos:'Proyectos', nuevo:'Nuevo proyecto', historial:'Historial' };

function switchTab(id) {
  TABS.forEach(t => {
    document.getElementById('tab-' + t).classList.remove('active');
  });
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  document.getElementById('topbar-page').textContent = TAB_LABELS[id] || id;
  if (id === 'proyectos') cargarProyectos();
  if (id === 'historial') cargarHistorialFull();
}

/* ─── TOAST ─── */
function toast(msg, type = 'ok') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = `<span class="toast-icon">${type === 'ok' ? '✓' : '✕'}</span> <span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(20px)'; t.style.transition = 'all .3s'; setTimeout(() => t.remove(), 300); }, 3500);
}

/* ─── DATA LOADERS ─── */
function refrescarTodo() {
  cargarStats();
  cargarHistorial();
}

function cargarStats() {
  fetch('api.php?action=proyectos')
    .then(r => r.json())
    .then(res => {
      if (res.exito) {
        document.getElementById('stat-proyectos').textContent = res.datos.length;
        document.getElementById('stat-proyectos-sub').textContent = res.datos.length + ' en Drive';
        document.getElementById('nav-badge-subidas').textContent = '';
      }
    }).catch(() => {});

  fetch('api.php?action=historial')
    .then(r => r.json())
    .then(res => {
      if (!res.exito) return;
      const datos = res.datos;
      document.getElementById('stat-total').textContent = datos.length;
      document.getElementById('stat-total-sub').textContent = 'en Google Sheets';

      const hoy = new Date().toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' });
      const countHoy = datos.filter(f => f[0] && f[0].includes(hoy.split('/').join('/'))).length;
      document.getElementById('stat-hoy').textContent = countHoy;
      document.getElementById('stat-hoy-sub').textContent = 'registros de hoy';

      const raws = datos.filter(f => f[3] && f[3].startsWith('01.')).length;
      document.getElementById('stat-raws').textContent = raws;
      document.getElementById('stat-raws-sub').textContent = 'de ' + datos.length + ' total';

      renderActividad(datos.slice(0, 5));
    }).catch(() => {});
}

function renderActividad(filas) {
  const c = document.getElementById('actividad-mini');
  if (!filas.length) { c.innerHTML = '<div style="color:var(--muted);font-size:.8rem;text-align:center;padding:1.5rem 0">Sin actividad reciente</div>'; return; }
  c.innerHTML = filas.map(f => {
    const etapa = (f[3] || '').substring(0,2);
    const colors = { '01':'var(--c1)','02':'var(--c2)','03':'var(--c3)','04':'var(--c4)','05':'var(--c5)' };
    const col = colors[etapa] || 'var(--muted)';
    return `<div style="display:flex;align-items:center;gap:10px;padding:.55rem 0;border-bottom:1px solid var(--border)">
      <div style="width:6px;height:6px;border-radius:50%;background:${col};flex-shrink:0"></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.8rem;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${f[1] || '—'} <span style="color:var(--red)">cap. ${f[2] || '?'}</span></div>
        <div style="font-size:.7rem;color:var(--muted);margin-top:1px">${f[3] || '—'}</div>
      </div>
      <div style="font-size:.7rem;color:var(--muted);flex-shrink:0">${f[0] || ''}</div>
    </div>`;
  }).join('');
  // quitar borde del último
  c.querySelector('div:last-child').style.borderBottom = 'none';
}

function buildHistorialRows(datos) {
  if (!datos.length) return '<tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--muted)">No hay registros disponibles.</td></tr>';
  return datos.map(fila => {
    const etapa = (fila[3] || '').substring(0, 2);
    return `<tr>
      <td class="cell-manga">${escHtml(fila[1] || '—')}</td>
      <td class="cell-cap">#${escHtml(String(fila[2] || '?'))}</td>
      <td><span class="badge badge-${etapa}">${escHtml(fila[3] || '—')}</span></td>
      <td class="cell-date">${escHtml(fila[0] || '—')}</td>
      <td class="cell-file" title="${escHtml(fila[4] || '')}">${escHtml(fila[4] || '—')}</td>
      <td><div class="row-actions">
        <button class="act-btn" title="Copiar nombre" onclick="navigator.clipboard.writeText('${escHtml(fila[4]||'')}');toast('Nombre copiado')">⎘</button>
        <button class="act-btn danger" title="Eliminar">✕</button>
      </div></td>
    </tr>`;
  }).join('');
}

function cargarHistorial() {
  const body = document.getElementById('historial-body');
  body.innerHTML = '<tr class="loading-rows"><td colspan="6"><span class="spinner"></span> Cargando…</td></tr>';
  fetch('api.php?action=historial')
    .then(r => r.json())
    .then(data => {
      body.innerHTML = buildHistorialRows((data.datos || []).slice(0, 20));
    }).catch(() => {
      body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">Error al cargar historial.</td></tr>';
    });
}

function cargarHistorialFull() {
  const body = document.getElementById('historial-full-body');
  body.innerHTML = '<tr class="loading-rows"><td colspan="6">Cargando…</td></tr>';
  fetch('api.php?action=historial')
    .then(r => r.json())
    .then(data => {
      body.innerHTML = buildHistorialRows(data.datos || []);
    }).catch(() => {
      body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">Error al cargar.</td></tr>';
    });
}

function cargarProyectos() {
  const grid = document.getElementById('projects-grid');
  grid.innerHTML = '<div class="project-empty">Cargando proyectos…</div>';
  fetch('api.php?action=proyectos')
    .then(r => r.json())
    .then(res => {
      if (!res.exito || !res.datos.length) {
        grid.innerHTML = '<div class="project-empty"><span>📁</span>No hay proyectos todavía.</div>';
        return;
      }
      grid.innerHTML = res.datos.map(nombre => `
        <div class="project-card">
          <div class="project-icon">📖</div>
          <div class="project-name">${escHtml(nombre)}</div>
          <div class="project-meta">5 etapas · Google Drive</div>
        </div>`).join('');
    }).catch(() => {
      grid.innerHTML = '<div class="project-empty">Error al cargar proyectos.</div>';
    });
}

/* ─── CREAR PROYECTO ─── */
function _crearProyecto(inputId, btnId, resultId) {
  const inp = document.getElementById(inputId);
  const btn = document.getElementById(btnId);
  const res = document.getElementById(resultId);
  const nombre = inp.value.trim();
  const pass = sessionStorage.getItem(PASS_KEY);
  if (!nombre) { toast('Escribe el nombre del manga', 'err'); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Creando carpetas…';
  res.innerHTML = '';

  const form = new FormData();
  form.append('pass', pass);
  form.append('nombre', nombre);

  fetch('api.php?action=crearProyecto', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<span>Crear en Google Drive</span>';
      if (data.exito) {
        res.innerHTML = `<div class="success-msg">✓ Proyecto "${nombre}" creado correctamente.</div>`;
        inp.value = '';
        toast('Proyecto creado: ' + nombre, 'ok');
        cargarStats();
      } else {
        res.innerHTML = `<div class="error-msg">✕ ${data.mensaje}</div>`;
        toast(data.mensaje, 'err');
      }
    }).catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<span>Crear en Google Drive</span>';
      res.innerHTML = '<div class="error-msg">✕ Error de conexión con el servidor.</div>';
      toast('Error de conexión', 'err');
    });
}

function crearProyecto()  { _crearProyecto('inp-proyecto-nuevo',  'btn-crear',  'crear-resultado');  }
function crearProyecto2() { _crearProyecto('inp-proyecto-nuevo2', 'btn-crear2', 'crear-resultado2'); }

/* ─── CONFIRM DELETE (placeholder) ─── */
function closeConfirm() { document.getElementById('confirm-overlay').classList.add('hidden'); }
function doDelete() { closeConfirm(); toast('Eliminado del historial', 'ok'); }

/* ─── UTILS ─── */
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ─── INIT ─── */
if (sessionStorage.getItem(PASS_KEY)) mostrarPanel();
</script>
</body>
</html>
