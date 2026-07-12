<?php
define('AUTH_NO_GUARD', 1);
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/database/db.php';

// --- DEV PREVIEW BYPASS (Toggle Mode) ---
if (isset($_GET['dev_preview'])) {
    if ($_GET['dev_preview'] === 'admin2026') {
        auth_set_cookie(['id' => 1, 'usuario' => 'Admin Preview', 'rol' => 'admin']);
        header('Location: admin.php');
        exit;
    }
    if ($_GET['dev_preview'] === 'staff2026') {
        auth_set_cookie(['id' => 2, 'usuario' => 'Staff Preview', 'rol' => 'Staff']); // Rol needs to trigger staff features, panel_staff accepts anything not admin
        header('Location: panel_staff.php');
        exit;
    }
}
// ----------------------------------------

// Si ya está autenticado, redirigir según rol
if ($u = auth_get_user()) {
    header('Location: ' . ($u['rol'] === 'admin' ? 'admin.php' : 'panel_staff.php'));
    exit;
}

$csrf_token = csrf_token_generate();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar Token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!csrf_token_verify($csrfToken)) {
        $error = 'Token de seguridad inválido. Por favor intenta de nuevo.';
    } else {
        $usuario  = trim($_POST['usuario']  ?? '');
        $password = $_POST['password'] ?? '';

        if ($usuario && $password) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
                $stmt->execute([$usuario]);
                $user = $stmt->fetch();

                if ($user) {
                    // Verificar bloqueo por intentos fallidos (Brute Force)
                    if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
                        $diff = strtotime($user['bloqueado_hasta']) - time();
                        if ($diff < 60) {
                            $error = "Tu cuenta está bloqueada temporalmente por exceso de intentos. Intenta de nuevo en {$diff} segundos.";
                        } else {
                            $mins = ceil($diff / 60);
                            $error = "Tu cuenta está bloqueada temporalmente por exceso de intentos. Intenta de nuevo en {$mins} minutos.";
                        }
                    } elseif ($user['activo'] != 1) {
                        $error = 'Tu usuario se encuentra inactivo. Contacta a un administrador.';
                    } else {
                        $authenticated = false;
                        
                        // Validar con password_verify (seguro) o plain text (legacy con actualización automática)
                        if (password_verify($password, $user['password'])) {
                            $authenticated = true;
                        } elseif ($password === $user['password']) {
                            // Detectada contraseña en texto plano (Legacy). Hashearla automáticamente.
                            $authenticated = true;
                            $hash = password_hash($password, PASSWORD_BCRYPT);
                            $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $user['id']]);
                        }

                        if ($authenticated) {
                            // Limpiar intentos fallidos
                            $db->prepare("UPDATE usuarios SET intentos = 0, bloqueado_hasta = NULL WHERE id = ?")->execute([$user['id']]);

                            auth_set_cookie([
                                'id'      => $user['id'],
                                'usuario' => $user['usuario'],
                                'rol'     => $user['rol'],
                            ]);
                            $destino = ($user['rol'] === 'admin') ? 'admin.php' : 'panel_staff.php';
                            header('Location: ' . $destino);
                            exit;
                        } else {
                            // Incrementar intentos fallidos con bloqueo exponencial
                            $intentos = $user['intentos'] + 1;
                            $bloqueo  = null;
                            // Segundos de bloqueo por nivel: 5→30s, 10→5min, 15→30min, 20+→2h
                            if ($intentos >= 20) {
                                $seg     = 7200;
                                $bloqueo = date('Y-m-d H:i:s', time() + $seg);
                                $error   = 'Demasiados intentos fallidos. Cuenta bloqueada por 2 horas.';
                            } elseif ($intentos >= 15) {
                                $seg     = 1800;
                                $bloqueo = date('Y-m-d H:i:s', time() + $seg);
                                $error   = 'Demasiados intentos fallidos. Cuenta bloqueada por 30 minutos.';
                            } elseif ($intentos >= 10) {
                                $seg     = 300;
                                $bloqueo = date('Y-m-d H:i:s', time() + $seg);
                                $error   = 'Demasiados intentos fallidos. Cuenta bloqueada por 5 minutos.';
                            } elseif ($intentos >= 5) {
                                $seg     = 30;
                                $bloqueo = date('Y-m-d H:i:s', time() + $seg);
                                $error   = 'Has superado el límite de intentos. Cuenta bloqueada por 30 segundos.';
                            } else {
                                $error = 'Usuario o contraseña incorrectos.';
                            }
                            error_log("Login fallido: usuario={$usuario} intentos={$intentos} IP=" . ($_SERVER['REMOTE_ADDR'] ?? '?'));
                            $db->prepare("UPDATE usuarios SET intentos = ?, bloqueado_hasta = ? WHERE id = ?")
                               ->execute([$intentos, $bloqueo, $user['id']]);
                        }
                    }
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
    
    // Registrar el error en la base de datos para los administradores
    if ($error) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '?';
        $user_attempt = isset($_POST['usuario']) ? trim($_POST['usuario']) : '?';
        try {
            $db = getDB();
            $db->prepare("INSERT INTO login_logs (usuario, ip, error) VALUES (?, ?, ?)")
               ->execute([$user_attempt, $ip, $error]);
        } catch (Exception $e) {
            // Ignorar silenciosamente si falla el log
        }
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
  --bg:       #09080f;
  --card:     #13111e;
  --border:   #25233a;
  --red:      #DC143C;
  --red-glow: rgba(220,20,60,0.2);
  --red-dim:  #7A0022;
  --text:     #eae8f2;
  --muted:    #6b6880;
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
    linear-gradient(rgba(220,20,60,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(220,20,60,0.04) 1px, transparent 1px);
  background-size: 48px 48px;
}

.bg-glow {
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background: radial-gradient(ellipse 60% 50% at 50% 40%, rgba(220,20,60,0.12) 0%, transparent 70%);
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
  background: rgba(19,17,30,0.88);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 3rem 2.5rem;
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  box-shadow: 0 24px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.06);
}

@media (max-width: 440px) {
  .login-card { padding: 2.5rem 1.75rem; border-radius: 16px; }
  .login-wrap { padding: 1rem; }
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
  padding: 0 1rem;
  height: 52px;
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
  padding: 0;
  height: 52px;
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
      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
