<?php require_once __DIR__ . '/../src/auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subir Archivo · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
  .upload-hero { padding: 3rem 0 1.5rem; }

  .form-wrapper {
    background: rgba(18, 18, 26, 0.6);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 3rem;
    box-shadow: 0 0 60px rgba(0,0,0,.5);
    position: relative;
  }

  .form-wrapper::before {
    content: '';
    display: block;
    height: 4px;
    background: linear-gradient(90deg, var(--red), #ff6b6b, var(--red));
    background-size: 200% 100%;
    animation: shimmer 2s linear infinite;
  }

  @keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }

  .form-header {
    padding: 1.5rem 1.75rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .form-icon {
    width: 42px; height: 42px;
    background: var(--red-glow);
    border: 1px solid var(--red-dim);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
  }

  .form-info h3 {
    font-family: var(--font-head);
    font-size: 1.2rem;
    letter-spacing: .04em;
  }

  .form-info p {
    color: var(--muted);
    font-size: .8rem;
    margin-top: .1rem;
  }

  .google-form-embed {
    width: 100%;
    min-height: 820px;
    border: none;
    display: block;
    background: transparent;
    margin-top: .5rem;
    /* Invertir colores del iframe para que combine con el tema oscuro */
    filter: invert(1) hue-rotate(180deg);
  }

  .form-note {
    padding: 1rem 1.75rem 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    border-top: 1px solid var(--border);
    margin-top: -.5rem;
  }

  .note-icon { font-size: 1rem; margin-top: .1rem; flex-shrink: 0; }

  .note-text {
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.5;
  }

  .note-text strong { color: var(--text); }

  .info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
  }

  .info-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 1.1rem 1.25rem;
    display: flex;
    gap: .75rem;
    align-items: flex-start;
  }

  .info-card-icon { font-size: 1.3rem; flex-shrink: 0; }

  .info-card-body h4 {
    font-size: .875rem;
    font-weight: 600;
    margin-bottom: .2rem;
  }

  .info-card-body p {
    font-size: .78rem;
    color: var(--muted);
    line-height: 1.4;
  }

  .info-card-body code {
    background: var(--faint);
    padding: .1rem .35rem;
    border-radius: 4px;
    font-size: .75rem;
    color: var(--red);
    font-family: monospace;
  }

  .success-msg {
    background: rgba(74, 222, 128, 0.1);
    border: 1px solid #4ade80;
    color: #4ade80;
    padding: 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    text-align: center;
    animation: slideUp 0.4s ease;
  }

  .error-msg {
    background: rgba(220, 38, 38, 0.1);
    border: 1px solid var(--red);
    color: var(--red);
    padding: 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    text-align: center;
    animation: slideUp 0.4s ease;
  }

  @keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  #successOverlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    display: none;
    align-items: center; justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(10px);
  }

  .success-modal {
    background: var(--card);
    border: 1px solid var(--border);
    padding: 3rem;
    border-radius: var(--radius);
    text-align: center;
    max-width: 400px;
    box-shadow: 0 0 50px rgba(0,0,0,0.5);
  }
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
      <a href="subir.php" class="nav-link active">Subir</a>
      <a href="admin.php" class="nav-link">Admin</a>
    </nav>
  </div>
</header>

<main class="container">

  <div class="upload-hero">
    <p class="hero-sub">Staff · Gestión de archivos</p>
    <h1 class="hero-title">Subir <span class="text-red">Capítulo</span></h1>
  </div>

  <!-- INFO CARDS -->
  <div class="info-cards">
    <div class="info-card">
      <div class="info-card-icon">📦</div>
      <div class="info-card-body">
        <h4>Formato aceptado</h4>
        <p>Sube archivos <code>.zip</code>, <code>.rar</code>, <code>.7z</code>, <code>.doc</code>, <code>.docx</code>, <code>.odt</code>, imágenes o PDF.</p>
      </div>
    </div>
    <div class="info-card">
      <div class="info-card-icon">🏷️</div>
      <div class="info-card-body">
        <h4>Nombre del archivo RAW</h4>
        <p>Usa el formato <code>Cap_1.zip</code>, <code>Cap_12.rar</code>, <code>Cap_2.7z</code> para que el buscador lo encuentre.</p>
      </div>
    </div>
    <div class="info-card">
      <div class="info-card-icon">📁</div>
      <div class="info-card-body">
        <h4>Otras etapas</h4>
        <p>Para Traducción, Typos, etc. sube dentro de la subcarpeta <code>Capítulo X</code> correspondiente.</p>
      </div>
    </div>
    <div class="info-card">
      <div class="info-card-icon">⏱️</div>
      <div class="info-card-body">
        <h4>Tiempo de procesamiento</h4>
        <p>El archivo aparece en el buscador en menos de <strong>1 minuto</strong> tras la subida.</p>
      </div>
    </div>
  </div>

  <!-- FORMULARIO EMBEBIDO -->
  <div class="form-wrapper">
    <div class="form-header">
      <div class="form-icon">📤</div>
      <div class="form-info">
        <h3>Formulario de Subida</h3>
        <p>Completa todos los campos antes de enviar</p>
      </div>
    </div>

    <div class="custom-upload-container" style="padding: 2.5rem; background: var(--bg2);">
      
      <!-- Selectores -->
      <div class="search-grid" style="margin-bottom: 2rem;">
        <div class="field-group">
          <label class="field-label">Proyecto</label>
          <select id="selProyecto" class="field-input">
            <option value="">Cargando proyectos...</option>
          </select>
        </div>
        <div class="field-group">
          <label class="field-label">Capítulo</label>
          <input type="number" id="inpCapitulo" class="field-input" placeholder="Ej: 12" min="1">
        </div>
        <div class="field-group">
          <label class="field-label">Etapa</label>
          <select id="selEtapa" class="field-input">
            <option value="01. RAWs">01. RAWs</option>
            <option value="02. Traducción">02. Traducción</option>
            <option value="03. Limpieza y Redibujo">03. Limpieza y Redibujo</option>
            <option value="04. Typos">04. Typos</option>
            <option value="05. Control de Calidad">05. Control de Calidad</option>
          </select>
        </div>
      </div>

      <!-- Zona Drag & Drop -->
      <div id="dropZone" style="border: 2px dashed var(--border); border-radius: var(--radius); padding: 4rem 2rem; text-align: center; cursor: pointer; transition: all 0.3s ease; background: rgba(0,0,0,0.2);">
        <div style="font-size: 3rem; margin-bottom: 1rem; color: var(--red);">📥</div>
        <h3 style="font-family: var(--font-head); font-size: 1.8rem; margin-bottom: 0.5rem;">Arrastra tu archivo aquí</h3>
        <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 1.5rem;">zip, rar, 7z, cbz, pdf, jpg, png, webp, doc, docx, odt</p>
        <button class="btn-outline" style="pointer-events: none;">Seleccionar Archivo</button>
        <input type="file" id="fileInput" accept=".zip,.rar,.7z,.cbz,.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.odt,application/zip,application/x-rar-compressed,application/x-7z-compressed,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text" style="display: none;">
      </div>

      <!-- Progreso -->
      <div id="progressZone" class="hidden" style="margin-top: 2rem; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600;">
          <span id="progressText">Subiendo archivo...</span>
          <span id="progressPercent">0%</span>
        </div>
        <div style="height: 8px; background: var(--bg); border-radius: 4px; overflow: hidden;">
          <div id="progressBar" style="height: 100%; width: 0%; background: var(--red); transition: width 0.2s linear; box-shadow: 0 0 10px var(--red-glow);"></div>
        </div>
      </div>

      <!-- Mensajes -->
      <div id="statusMessage" style="margin-top: 1.5rem; display: none;"></div>

    </div>
  </div>

  <!-- Overlay de Éxito -->
  <div id="successOverlay">
    <div class="success-modal">
      <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
      <h2 style="font-family: var(--font-head); margin-bottom: 1rem;">¡Subida Exitosa!</h2>
      <p style="color: var(--muted); margin-bottom: 2rem;">El archivo ya está en Google Drive y estará disponible en el buscador en breve.</p>
      <button onclick="closeSuccess()" class="btn-primary" style="width: 100%;">Aceptar</button>
    </div>
  </div>

  <script>
    const selProyecto = document.getElementById('selProyecto');
    const inpCapitulo = document.getElementById('inpCapitulo');
    const selEtapa = document.getElementById('selEtapa');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const progressZone = document.getElementById('progressZone');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressText = document.getElementById('progressText');
    const statusMessage = document.getElementById('statusMessage');
    const successOverlay = document.getElementById('successOverlay');

    function closeSuccess() {
      successOverlay.style.display = 'none';
      // Limpiar formulario
      fileInput.value = '';
      inpCapitulo.value = '';
      dropZone.style.pointerEvents = 'auto';
      dropZone.style.opacity = '1';
      progressZone.classList.add('hidden');
      progressBar.style.background = 'var(--red)';
    }

    // 1. Cargar Proyectos
    fetch('api.php?action=proyectos')
      .then(r => r.json())
      .then(res => {
        if(res.exito) {
          selProyecto.innerHTML = '<option value="">— Selecciona un proyecto —</option>';
          res.datos.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p; opt.textContent = p;
            selProyecto.appendChild(opt);
          });
        }
      });

    // 2. Drag & Drop Visuals
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
      dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
    });
    ['dragenter', 'dragover'].forEach(evt => {
      dropZone.addEventListener(evt, () => dropZone.style.borderColor = 'var(--red)');
    });
    ['dragleave', 'drop'].forEach(evt => {
      dropZone.addEventListener(evt, () => dropZone.style.borderColor = 'var(--border)');
    });

    // 3. Selección de Archivo
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('drop', e => handleFile(e.dataTransfer.files[0]));
    fileInput.addEventListener('change', e => handleFile(e.target.files[0]));

    function showStatus(msg, isError = false) {
      statusMessage.style.display = 'block';
      statusMessage.className = isError ? 'error-msg' : 'success-msg';
      statusMessage.textContent = msg;
    }

    function handleFile(file) {
      if(!file) return;
      statusMessage.style.display = 'none';

      const ext = file.name.split('.').pop().toLowerCase();
      const extsPermitidas = ['zip', 'rar', '7z', 'cbz', 'pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'odt'];
      if(!extsPermitidas.includes(ext)) {
        return showStatus('Tipo de archivo no permitido. Se permiten: zip, rar, 7z, cbz, pdf, imágenes, doc, docx, odt', true);
      }

      if(!selProyecto.value || !inpCapitulo.value) {
        return showStatus('Por favor, selecciona el proyecto y el capítulo antes de subir.', true);
      }

      startUpload(file);
    }

    // 4. Proceso de Subida Directa (Direct Resumable Upload)
    async function startUpload(file) {
      dropZone.style.pointerEvents = 'none';
      dropZone.style.opacity = '0.5';
      progressZone.classList.remove('hidden');
      progressText.textContent = 'Preparando conexión con Google Drive...';
      progressBar.style.width = '0%';
      progressPercent.textContent = '0%';

      try {
        // Paso A: Pedir URL de subida al proxy local (upload_api.php) con Token CSRF
        const initRes = await fetch('upload_api.php?action=initUpload', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            proyecto: selProyecto.value,
            capitulo: inpCapitulo.value,
            etapa: selEtapa.value,
            filename: file.name,
            mimeType: file.type || 'application/octet-stream',
            fileSize: file.size,
            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
          })
        });

        const initData = await initRes.json();
        
        if(!initData.exito) {
          let errorMsg = initData.mensaje || 'Error al conectar con Drive.';
          throw new Error(errorMsg);
        }

        const uploadUrl = initData.uploadUrl;
        progressText.textContent = `Subiendo ${file.name}...`;

        // Paso B: Subir archivo crudo directamente a Google Drive vía XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('PUT', uploadUrl, true);
        
        xhr.upload.onprogress = (e) => {
          if (e.lengthComputable) {
            const p = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = p + '%';
            progressPercent.textContent = p + '%';
          }
        };

        xhr.onload = () => {
          // Status 200, 201 son éxito total. 
          // Status 0 a veces ocurre por CORS al final de la subida aunque se haya subido.
          if(xhr.status === 200 || xhr.status === 201 || (xhr.status === 0 && progressPercent.textContent === '100%')) {
            progressText.textContent = '¡Subida Completada!';
            progressBar.style.background = '#4ade80'; // Verde
            successOverlay.style.display = 'flex';
            
            // Registrar en Excel y Discord a través del proxy local
            registrarSubida(file);
          } else {
            showStatus('Error al enviar archivo a Google. Código: ' + xhr.status, true);
            resetUI();
          }
        };

        xhr.onerror = () => { 
          // Si falló pero ya estaba al 100%, es probable que se haya subido bien (CORS issue)
          if (progressPercent.textContent === '100%') {
            progressText.textContent = '¡Subida Completada!';
            progressBar.style.background = '#4ade80';
            successOverlay.style.display = 'flex';

            // Registrar en Excel y Discord a través del proxy local
            registrarSubida(file);
          } else {
            showStatus('Error de red durante la subida.', true);
            resetUI();
          }
        };

        xhr.send(file);

      } catch (err) {
        showStatus(err.message, true);
        resetUI();
      }
    }

    // Helper para registrar la subida de forma segura a través del proxy local
    function registrarSubida(file) {
      fetch('upload_api.php?action=registrarSubida', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          proyecto: selProyecto.value,
          capitulo: inpCapitulo.value,
          etapa: selEtapa.value,
          filename: file.name,
          csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
        })
      });
    }

    function resetUI() {
      progressZone.classList.add('hidden');
      dropZone.style.pointerEvents = 'auto';
      dropZone.style.opacity = '1';
    }
  </script>
  </div>

</main>

<footer class="site-footer">
  <div class="container">
    <p>© 2025 Crimson Scan · Panel de Staff</p>
  </div>
</footer>

</body>
</html>
