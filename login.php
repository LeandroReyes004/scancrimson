<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

// Si ya tiene sesión, ir al panel
if (isset($_SESSION['user'])) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario']  ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario && $password) {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'      => $user['id'],
                    'usuario' => $user['usuario'],
                    'rol'     => $user['rol'],
                ];
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión con la base de datos.';
        }
    } else {
        $error = 'Completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso · Crimson Scan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:       #080810;
  --card:     #13131f;
  --border:   #252535;
  --red:      #dc2020;
  --red-glow: rgba(220,32,32,0.2);
  --red-dim:  #8b1010;
  --text:     #e4e4f0;
  --muted:    #6a6a85;
  --font-h:   'Bebas Neue', sans-serif;
  --font-b:   'DM Sans', sans-serif;
}

html, body { height: 100%; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-b);
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  overflow: hidden;
}

/* ── FONDOS ── */
.bg-grid {
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image:
    linear-gradient(rgba(220,32,32,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(220,32,32,0.04) 1px, transparent 1px);
  background-size: 48px 48px;
}

.bg-glow {
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background: radial-gradient(ellipse 60% 50% at 50% 40%, rgba(220,32,32,0.12) 0%, transparent 70%);
}

.bg-orb {
  position: fixed; width: 500px; height: 500px;
  border-radius: 50%; z-index: 0; pointer-events: none;
  filter: blur(100px); opacity: 0.08;
  background: var(--red);
  top: 50%; left: 50%; transform: translate(-50%, -55%);
  animation: pulse 6s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: translate(-50%, -55%) scale(1); }
  50%       { transform: translate(-50%, -55%) scale(1.1); }
}

/* ── CARD ── */
.login-wrap {
  position: relative; z-index: 10;
  width: 100%; max-width: 400px;
  padding: 1.5rem;
  animation: fadeUp 0.5s ease;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

.login-card {
  background: rgba(19,19,31,0.85);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 20px;
  padding: 3rem 2.5rem;
  backdrop-filter: blur(24px);
  box-shadow: 0 24px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
}

/* ── LOGO ── */
.login-logo {
  text-align: center;
  margin-bottom: 2.5rem;
}

.logo-icon-wrap {
  width: 64px; height: 64px;
  background: var(--red-glow);
  border: 1px solid var(--red-dim);
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem;
  margin: 0 auto 1rem;
  box-shadow: 0 0 40px var(--red-glow);
}

.logo-name {
  font-family: var(--font-h);
  font-size: 2rem;
  letter-spacing: 0.1em;
}

.logo-name span { color: var(--red); }

.logo-sub {
  color: var(--muted);
  font-size: 0.8rem;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  margin-top: 0.3rem;
}

/* ── FORM ── */
.field-group {
  display: flex; flex-direction: column; gap: 0.4rem;
  margin-bottom: 1.2rem;
}

.field-label {
  font-size: 0.7rem; font-weight: 700;
  letter-spacing: 0.14em; text-transform: uppercase;
  color: var(--muted);
}

.field-input {
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  font-family: var(--font-b);
  font-size: 0.95rem;
  padding: 0.75rem 1rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  width: 100%;
}

.field-input:focus {
  border-color: var(--red);
  box-shadow: 0 0 0 3px var(--red-glow);
}

.field-input::placeholder { color: var(--muted); }

/* ── PASSWORD TOGGLE ── */
.field-wrap { position: relative; }
.field-wrap .field-input { padding-right: 2.8rem; }

.eye-btn {
  position: absolute; right: 0.9rem; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: var(--muted); font-size: 1rem; padding: 0;
  transition: color 0.2s; line-height: 1;
}
.eye-btn:hover { color: var(--text); }

/* ── BUTTON ── */
.btn-login {
  width: 100%;
  background: var(--red);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-family: var(--font-h);
  font-size: 1.1rem;
  letter-spacing: 0.08em;
  padding: 0.85rem;
  cursor: pointer;
  margin-top: 0.5rem;
  transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 4px 24px var(--red-glow);
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
}

.btn-login:hover {
  background: #b81a1a;
  transform: translateY(-1px);
  box-shadow: 0 8px 32px var(--red-glow);
}

.btn-login:active { transform: translateY(0); }

.btn-login:disabled {
  opacity: 0.6; cursor: not-allowed; transform: none;
}

/* ── ERROR ── */
.error-box {
  background: rgba(220,32,32,0.1);
  border: 1px solid rgba(220,32,32,0.3);
  border-radius: 8px;
  color: #f87171;
  font-size: 0.85rem;
  padding: 0.65rem 1rem;
  margin-bottom: 1.2rem;
  display: flex; align-items: center; gap: 0.5rem;
}

/* ── FOOTER ── */
.login-footer {
  text-align: center;
  margin-top: 2rem;
  color: var(--muted);
  font-size: 0.75rem;
}

.login-footer a {
  color: var(--muted);
  text-decoration: none;
  transition: color 0.2s;
}
.login-footer a:hover { color: var(--text); }

/* ── SPINNER ── */
.spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div class="bg-grid"></div>
<div class="bg-glow"></div>
<div class="bg-orb"></div>

<div class="login-wrap">
  <div class="login-card">

    <div class="login-logo">
      <div class="logo-icon-wrap">⚔</div>
      <div class="logo-name">CRIMSON <span>SCAN</span></div>
      <div class="logo-sub">Panel de Staff</div>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <span>⚠</span>
      <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" autocomplete="on">
      <div class="field-group">
        <label class="field-label" for="usuario">Usuario</label>
        <input
          id="usuario" name="usuario" type="text"
          class="field-input" placeholder="tu_usuario"
          value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
          autocomplete="username" required autofocus>
      </div>

      <div class="field-group">
        <label class="field-label" for="password">Contraseña</label>
        <div class="field-wrap">
          <input
            id="password" name="password" type="password"
            class="field-input" placeholder="••••••••••"
            autocomplete="current-password" required>
          <button type="button" class="eye-btn" id="eyeBtn" onclick="toggleEye()" title="Mostrar/ocultar">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-login" id="submitBtn">
        <span id="btnText">Entrar al panel</span>
        <span>→</span>
      </button>
    </form>

    <div class="login-footer">
      <a href="index.php">← Ir al buscador público</a>
    </div>

  </div>
</div>

<script>
function toggleEye() {
  const inp = document.getElementById('password');
  const btn = document.getElementById('eyeBtn');
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}

document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  const txt = document.getElementById('btnText');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span><span>Verificando...</span>';
});
</script>
</body>
</html>
