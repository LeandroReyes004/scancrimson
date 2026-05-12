<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- ─── PARTÍCULAS DE FONDO ─── -->
<div class="bg-grid"></div>
<div class="noise"></div>

<!-- ─── HEADER ─── -->
<header class="site-header">
  <div class="container header-inner">
    <div class="logo">
      <span class="logo-icon">⚔</span>
      <span class="logo-text">CRIMSON <span class="logo-accent">SCAN</span></span>
    </div>
    <nav class="site-nav">
      <a href="index.php" class="nav-link active">Buscar</a>
      <a href="subir.php" class="nav-link">Subir</a>
      <a href="admin.php" class="nav-link">Admin</a>
    </nav>
  </div>
</header>

<!-- ─── HERO ─── -->
<section class="hero">
  <div class="container">
    <p class="hero-sub">Panel de gestión de scanlation</p>
    <h1 class="hero-title">Encuentra tu <span class="text-red">capítulo</span></h1>
  </div>
</section>

<!-- ─── BUSCADOR ─── -->
<main class="container">
  <div class="search-card">
    <div class="search-grid">

      <div class="field-group">
        <label class="field-label">Proyecto</label>
        <select id="sel-proyecto" class="field-input">
          <option value="">Cargando proyectos...</option>
        </select>
      </div>

      <div class="field-group">
        <label class="field-label">Capítulo</label>
        <input id="inp-capitulo" type="number" min="1" class="field-input" placeholder="Ej: 5">
      </div>

      <div class="field-group">
        <label class="field-label">Etapa</label>
        <select id="sel-etapa" class="field-input">
          <option value="Todas">Todas las etapas</option>
          <option value="01. RAWs">01. RAWs</option>
          <option value="02. Traducción">02. Traducción</option>
          <option value="03. Limpieza y Redibujo">03. Limpieza y Redibujo</option>
          <option value="04. Typos">04. Typos</option>
          <option value="05. Control de Calidad">05. Control de Calidad</option>
        </select>
      </div>

      <div class="field-group field-btn">
        <button id="btn-buscar" class="btn-primary" onclick="buscarCapitulo()">
          <span class="btn-text">Buscar</span>
          <span class="btn-icon">→</span>
        </button>
      </div>

    </div>
  </div>

  <!-- RESULTADOS -->
  <div id="resultados" class="resultados-area hidden"></div>

  <!-- ─── HISTORIAL ─── -->
  <section class="historial-section">
    <div class="section-header">
      <h2 class="section-title">Actividad reciente</h2>
      <span class="section-badge" id="historial-count">—</span>
    </div>
    <div id="historial-lista" class="historial-lista">
      <div class="loading-pulse">
        <div class="pulse-bar"></div>
        <div class="pulse-bar"></div>
        <div class="pulse-bar short"></div>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container">
    <p>© 2025 Crimson Scan · Todos los derechos reservados</p>
  </div>
</footer>

<script src="assets/app.js"></script>
</body>
</html>
