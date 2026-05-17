<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Inicializar el token CSRF para peticiones API
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/bridge_client.php';

header('Content-Type: application/json; charset=utf-8');

// CORS Seguro dinámico limitado al host actual
$allowed = [];
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host) {
    $allowed[] = rtrim($protocol . $host, '/');
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    if (in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
}

// Protección CSRF Global para peticiones POST de escritura
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        echo json_encode(['exito' => false, 'mensaje' => 'Token CSRF inválido o ausente.']);
        exit;
    }
}

// ─── Auth helpers ────────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}
function isAdmin(): bool {
    return isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin';
}
function requireLogin(): void {
    if (!isLoggedIn()) {
        echo json_encode(['exito' => false, 'mensaje' => 'Sesión expirada.']);
        exit;
    }
}
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        echo json_encode(['exito' => false, 'mensaje' => 'Acceso denegado. Se requiere rol admin.']);
        exit;
    }
}

// ─── HTTP helper ─────────────────────────────────────────────────────────────
function httpGet(string $url, int $timeout = 20): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_USERAGENT      => 'CrimsonScan/2.0',
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) return ['__curl_error' => $err];
    if ($code !== 200 || !$res) return ['__http_error' => "HTTP {$code}", '__body' => substr($res ?: '', 0, 300)];
    $decoded = json_decode($res, true);
    if ($decoded === null) return ['__json_error' => 'respuesta no es JSON válido', '__body' => substr($res, 0, 300)];
    return $decoded;
}

function driveQ(string $q, string $fields = 'files(id,name)', int $limit = 100): array {
    $url = DRIVE_API . '/files?' . http_build_query([
        'q'        => $q,
        'fields'   => $fields,
        'pageSize' => $limit,
        'key'      => GOOGLE_API_KEY,
    ]);
    $data = httpGet($url);
    return $data['files'] ?? [];
}

function folderIdByName(string $parentId, string $name): ?string {
    $files = driveQ("'{$parentId}' in parents and name='" . addslashes($name) . "' and mimeType='application/vnd.google-apps.folder' and trashed=false", 'files(id,name)', 1);
    return $files[0]['id'] ?? null;
}

function downloadUrl(string $fileId): string {
    return "https://drive.google.com/uc?export=download&id={$fileId}";
}

// ─── ROUTER ──────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {

    // ── PÚBLICAS (no requieren sesión) ──────────────────────────────────────

    case 'proyectos':
        if (APPS_SCRIPT_URL) {
            $res = httpGet(APPS_SCRIPT_URL . '?action=listarProyectos');
            if ($res && isset($res['exito']) && $res['exito'] && isset($res['datos'])) {
                echo json_encode($res);
                break;
            }
        }
        $files   = driveQ("'" . CARPETA_RAIZ_ID . "' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false", 'files(id,name)', 200);
        $nombres = array_column($files, 'name');
        sort($nombres);
        echo json_encode(['exito' => true, 'datos' => $nombres]);
        break;

    case 'enlaces':
        $proyecto     = trim($_GET['proyecto'] ?? '');
        $capitulo     = intval($_GET['capitulo'] ?? 0);
        $etapaBuscada = trim($_GET['etapa'] ?? 'Todas');

        if (!$proyecto || !$capitulo) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan parámetros.']);
            break;
        }

        $proyectoId = folderIdByName(CARPETA_RAIZ_ID, $proyecto);
        if (!$proyectoId) {
            echo json_encode(['exito' => false, 'mensaje' => 'Proyecto no encontrado.']);
            break;
        }

        $todasEtapas = ["01. RAWs", "02. Traducción", "03. Limpieza y Redibujo", "04. Typos", "05. Control de Calidad"];
        $etapas      = ($etapaBuscada && $etapaBuscada !== 'Todas') ? [$etapaBuscada] : $todasEtapas;
        $resultados  = [];
        $capRegex    = '/cap[_\-\s]?0*' . $capitulo . '(\.|$|[^0-9])/i';

        foreach ($etapas as $etapa) {
            $etapaId = folderIdByName($proyectoId, $etapa);
            if (!$etapaId) continue;

            if ($etapa === "01. RAWs") {
                $archivos   = driveQ("'{$etapaId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 200);
                $encontrado = false;
                foreach ($archivos as $arc) {
                    if (preg_match($capRegex, $arc['name'])) {
                        $resultados[$etapa] = ['nombre' => $arc['name'], 'url' => downloadUrl($arc['id'])];
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    $capId = folderIdByName($etapaId, "Capítulo {$capitulo}");
                    if ($capId) {
                        $capFiles = driveQ("'{$capId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 1);
                        if ($capFiles) $resultados[$etapa] = ['nombre' => $capFiles[0]['name'], 'url' => downloadUrl($capFiles[0]['id'])];
                    }
                }
            } else {
                $capId = folderIdByName($etapaId, "Capítulo {$capitulo}");
                if ($capId) {
                    $capFiles = driveQ("'{$capId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 1);
                    if ($capFiles) $resultados[$etapa] = ['nombre' => $capFiles[0]['name'], 'url' => downloadUrl($capFiles[0]['id'])];
                }
            }
        }
        echo json_encode(['exito' => true, 'datos' => $resultados]);
        break;

    // ── PROTEGIDAS (requieren sesión) ────────────────────────────────────────

    case 'historial':
        requireLogin();
        if (APPS_SCRIPT_URL) {
            $res = httpGet(APPS_SCRIPT_URL . '?action=historial');
            if ($res && isset($res['exito']) && $res['exito'] && isset($res['datos'])) {
                echo json_encode($res);
                break;
            }
        }
        $url   = SHEETS_API . '/' . HOJA_CALCULO_ID . '/values/A%3AZ?key=' . GOOGLE_API_KEY;
        $data  = httpGet($url);
        $filas = $data['values'] ?? [];
        if (count($filas) <= 1) { echo json_encode(['exito' => true, 'datos' => []]); break; }
        array_shift($filas);
        $filas = array_filter($filas, fn($f) => !empty(trim($f[0] ?? '')));
        $filas = array_reverse(array_values($filas));
        echo json_encode(['exito' => true, 'datos' => $filas]);
        break;

    // ── SOLO ADMIN ───────────────────────────────────────────────────────────

    case 'crearProyecto':
        requireAdmin();
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { echo json_encode(['exito' => false, 'mensaje' => 'Nombre vacío.']); break; }
        if (!APPS_SCRIPT_URL) { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script URL no configurada.']); break; }

        $url = APPS_SCRIPT_URL . '?action=crearProyecto&nombre=' . urlencode($nombre);
        $res = httpGet($url);
        if (isset($res['__curl_error'])) {
            echo json_encode(['exito' => false, 'mensaje' => 'Error de red: ' . $res['__curl_error']]);
        } elseif (isset($res['__http_error'])) {
            echo json_encode(['exito' => false, 'mensaje' => 'Apps Script: ' . $res['__http_error']]);
        } else {
            echo json_encode($res ?? ['exito' => false, 'mensaje' => 'Sin respuesta.']);
        }
        break;

    case 'editarRegistro':
        requireAdmin();
        $fila  = $_POST['fila']  ?? '';
        $manga = $_POST['manga'] ?? '';
        $cap   = $_POST['cap']   ?? '';
        $etapa = $_POST['etapa'] ?? '';
        if (!$fila || !$manga) { echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']); break; }
        if (!APPS_SCRIPT_URL)  { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no configurado.']); break; }
        $url = APPS_SCRIPT_URL . '?action=editarRegistro&fila=' . urlencode($fila) . '&manga=' . urlencode($manga) . '&cap=' . urlencode($cap) . '&etapa=' . urlencode($etapa);
        echo json_encode(httpGet($url) ?? ['exito' => false, 'mensaje' => 'Error.']);
        break;

    case 'cambiarEstado':
        requireAdmin();
        $fila   = $_POST['fila']   ?? '';
        $estado = $_POST['estado'] ?? '';
        if (!$fila || !$estado) { echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']); break; }
        if (!APPS_SCRIPT_URL)   { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no configurado.']); break; }
        $url = APPS_SCRIPT_URL . '?action=cambiarEstado&fila=' . urlencode($fila) . '&estado=' . urlencode($estado);
        echo json_encode(httpGet($url) ?? ['exito' => false, 'mensaje' => 'Error.']);
        break;

    case 'eliminarRegistro':
        requireAdmin();
        $fila = $_POST['fila'] ?? '';
        if (!APPS_SCRIPT_URL) { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no configurado.']); break; }
        $url = APPS_SCRIPT_URL . '?action=eliminarRegistro&fila=' . urlencode($fila);
        echo json_encode(httpGet($url) ?? ['exito' => false, 'mensaje' => 'Error.']);
        break;

    // ── GESTIÓN DE USUARIOS (solo admin) ────────────────────────────────────

    case 'listarUsuarios':
        requireAdmin();
        $db    = getDB();
        $users = $db->query("SELECT id, usuario, rol, activo, creado FROM usuarios ORDER BY creado DESC")->fetchAll();
        echo json_encode(['exito' => true, 'datos' => $users]);
        break;

    case 'crearUsuario':
        requireAdmin();
        $nuevoUsuario = trim($_POST['usuario']  ?? '');
        $nuevaPass    = $_POST['password'] ?? '';
        $nuevoRol     = $_POST['rol']      ?? 'staff';

        if (!$nuevoUsuario || !$nuevaPass) {
            echo json_encode(['exito' => false, 'mensaje' => 'Usuario y contraseña son requeridos.']);
            break;
        }
        if (!in_array($nuevoRol, ['admin', 'staff'])) {
            echo json_encode(['exito' => false, 'mensaje' => 'Rol inválido.']);
            break;
        }
        if (strlen($nuevaPass) < 6) {
            echo json_encode(['exito' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres.']);
            break;
        }

        try {
            $db = getDB();
            $hash = password_hash($nuevaPass, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, ?)")
               ->execute([$nuevoUsuario, $hash, $nuevoRol]);
            echo json_encode(['exito' => true, 'mensaje' => "Usuario '{$nuevoUsuario}' creado correctamente."]);
        } catch (PDOException $e) {
            $msg = str_contains($e->getMessage(), 'Duplicate') ? "El usuario '{$nuevoUsuario}' ya existe." : 'Error al crear usuario.';
            echo json_encode(['exito' => false, 'mensaje' => $msg]);
        }
        break;

    case 'toggleUsuario':
        requireAdmin();
        $uid     = intval($_POST['id']     ?? 0);
        $activo  = intval($_POST['activo'] ?? 0);

        // No permitir desactivarse a sí mismo
        if ($uid === (int)$_SESSION['user']['id']) {
            echo json_encode(['exito' => false, 'mensaje' => 'No puedes desactivar tu propia cuenta.']);
            break;
        }

        $db = getDB();
        $db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$activo, $uid]);
        echo json_encode(['exito' => true, 'mensaje' => 'Estado actualizado.']);
        break;

    case 'eliminarUsuario':
        requireAdmin();
        $uid = intval($_POST['id'] ?? 0);

        if ($uid === (int)$_SESSION['user']['id']) {
            echo json_encode(['exito' => false, 'mensaje' => 'No puedes eliminar tu propia cuenta.']);
            break;
        }

        $db = getDB();
        $db->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$uid]);
        echo json_encode(['exito' => true, 'mensaje' => 'Usuario eliminado.']);
        break;

    case 'cambiarPassword':
        requireAdmin();
        $uid      = intval($_POST['id']       ?? 0);
        $newPass  = $_POST['password'] ?? '';

        if (!$uid || strlen($newPass) < 6) {
            echo json_encode(['exito' => false, 'mensaje' => 'Contraseña debe tener al menos 6 caracteres.']);
            break;
        }

        $db = getDB();
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $uid]);
        echo json_encode(['exito' => true, 'mensaje' => 'Contraseña actualizada.']);
        break;

    // ── LISTAR STAFF DISCORD (vía bridge) ────────────────────────────────
    case 'listarStaff':
        requireAdmin();
        echo json_encode(bridge_call('listarStaff'));
        break;

    // ── TOGGLE ACTIVO STAFF DISCORD (vía bridge) ──────────────────────────
    case 'toggleStaff':
        requireAdmin();
        $id     = $_POST['discord_id'] ?? '';
        $activo = intval($_POST['activo'] ?? 1);
        if (!$id) { echo json_encode(['exito' => false, 'mensaje' => 'ID requerido']); break; }
        echo json_encode(bridge_call('toggleStaff', ['discord_id' => $id, 'activo' => $activo], 'POST'));
        break;

    // ── RANKING DEL MES (vía bridge) ──────────────────────────────────────
    case 'rankingMes':
        $mes  = intval($_GET['mes']  ?? date('n'));
        $anio = intval($_GET['anio'] ?? date('Y'));
        echo json_encode(bridge_call('rankingMes', ['mes' => $mes, 'anio' => $anio]));
        break;

    // ── TAREAS ACTIVAS (vía bridge) ───────────────────────────────────────
    case 'tareasActivas':
        requireAdmin();
        echo json_encode(bridge_call('tareasActivas'));
        break;

    // ── HISTORIAL ERRORES STAFF (vía bridge) ──────────────────────────────
    case 'erroresStaff':
        requireAdmin();
        $params = [];
        if (!empty($_GET['discord_id'])) $params['discord_id'] = $_GET['discord_id'];
        echo json_encode(bridge_call('erroresStaff', $params));
        break;

    // ── ESTADÍSTICAS GLOBALES (vía bridge) ───────────────────────────────
    case 'estadisticasGlobales':
        echo json_encode(bridge_call('estadisticas'));
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
