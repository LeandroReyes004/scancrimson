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
        $db   = getDB();
        $rows = $db->query("SELECT id, nombre, estado, carpeta_drive_id FROM proyectos
                            WHERE estado='activo' ORDER BY nombre")->fetchAll();
        $datos = array_map(fn($r) => [
            $r['nombre'], '', '', '', $r['estado'], $r['carpeta_drive_id'] ?? ''
        ], $rows);
        echo json_encode(['exito' => true, 'datos' => $datos]);
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
        $db   = getDB();
        $rows = $db->query("
            SELECT t.id, t.obra, t.cap, t.rol, t.estado, t.limite, t.creado,
                   sd.nombre_display
            FROM tareas t
            LEFT JOIN staff_discord sd ON sd.discord_id = t.discord_id
            ORDER BY t.creado DESC LIMIT 200
        ")->fetchAll();
        $datos = array_map(fn($r) => [
            $r['creado'], $r['obra'], $r['cap'], $r['rol'],
            $r['nombre_display'] ?? 'Desconocido', $r['estado'], $r['limite']
        ], $rows);
        echo json_encode(['exito' => true, 'datos' => $datos]);
        break;

    // ── SOLO ADMIN ───────────────────────────────────────────────────────────

    case 'crearProyecto':
        requireAdmin();
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { echo json_encode(['exito' => false, 'mensaje' => 'Nombre requerido']); break; }
        $db = getDB();
        $db->prepare("INSERT IGNORE INTO proyectos (nombre, nombre_upper) VALUES (?,?)")
           ->execute([$nombre, strtoupper($nombre)]);
        // También crear carpeta en Drive si Apps Script está configurado
        if (APPS_SCRIPT_URL) {
            $res = httpGet(APPS_SCRIPT_URL . '?action=crearProyecto&nombre=' . urlencode($nombre));
            if (!empty($res['carpetaId'])) {
                $db->prepare("UPDATE proyectos SET carpeta_drive_id=? WHERE nombre_upper=?")
                   ->execute([$res['carpetaId'], strtoupper($nombre)]);
            }
        }
        echo json_encode(['exito' => true]);
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

    // ── GESTIÓN DE PROYECTOS Y CAPÍTULOS (admin / staff) ───────────────────
    case 'listarProyectosAdmin':
        requireLogin();
        $db = getDB();
        $proys = $db->query("SELECT id, nombre, estado, carpeta_drive_id FROM proyectos ORDER BY nombre")->fetchAll();
        echo json_encode(['exito' => true, 'datos' => $proys]);
        break;
    case 'listarCapitulos':
        requireLogin();
        $proyecto_id = intval($_GET['proyecto_id'] ?? 0);
        if (!$proyecto_id) {
            echo json_encode(['exito' => false, 'mensaje' => 'Proyecto ID requerido']);
            break;
        }
        $db = getDB();
        $caps = $db->query("SELECT * FROM capitulos WHERE proyecto_id = $proyecto_id ORDER BY numero DESC")->fetchAll();
        echo json_encode(['exito' => true, 'datos' => $caps]);
        break;

    case 'crearCapitulo':
        requireAdmin();
        $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
        $numero = floatval($_POST['numero'] ?? 0);
        $db = getDB();
        try {
            $db->prepare("INSERT INTO capitulos (proyecto_id, numero) VALUES (?, ?)")->execute([$proyecto_id, $numero]);
            echo json_encode(['exito' => true]);
        } catch (PDOException $e) {
            echo json_encode(['exito' => false, 'mensaje' => 'El capítulo ya existe o error en DB.']);
        }
        break;

    case 'actualizarEstadoCapitulo':
        requireAdmin();
        $cap_id = intval($_POST['id'] ?? 0);
        $campo = $_POST['campo'] ?? '';
        $valor = intval($_POST['valor'] ?? 0);
        
        $campos_validos = ['estado_raw', 'estado_trad', 'estado_clean', 'estado_type', 'estado_proof'];
        if (!in_array($campo, $campos_validos)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Campo no válido']);
            break;
        }
        
        $db = getDB();
        $db->prepare("UPDATE capitulos SET $campo = ? WHERE id = ?")->execute([$valor, $cap_id]);
        
        // Revisar si todo está listo
        $cap = $db->query("SELECT * FROM capitulos WHERE id = $cap_id")->fetch();
        if ($cap) {
            $nuevo_estado = 'Pendiente';
            $completados = $cap['estado_raw'] + $cap['estado_trad'] + $cap['estado_clean'] + $cap['estado_type'] + $cap['estado_proof'];
            if ($completados == 5) {
                // Sigue en proceso hasta que lo publiquen, o podemos poner un estado "Listo"
            } elseif ($completados > 0) {
                $nuevo_estado = 'En proceso';
            }
            if ($cap['estado_general'] !== 'Publicado') {
                $db->prepare("UPDATE capitulos SET estado_general = ? WHERE id = ?")->execute([$nuevo_estado, $cap_id]);
            }
        }
        
        echo json_encode(['exito' => true]);
        break;

    case 'publicarCapitulo':
        requireAdmin();
        $cap_id = intval($_POST['id'] ?? 0);
        $db = getDB();
        $db->prepare("UPDATE capitulos SET estado_general = 'Publicado' WHERE id = ?")->execute([$cap_id]);
        echo json_encode(['exito' => true]);
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

    // ── ESTADÍSTICAS GLOBALES ─────────────────────────────────────────────
    case 'estadisticasGlobales':
        $db  = getDB();
        $hoy = date('Y-m-d');
        $s   = [];
        $s['total_proyectos'] = $db->query("SELECT COUNT(*) FROM proyectos WHERE estado='activo'")->fetchColumn();
        $s['total_tareas']    = $db->query("SELECT COUNT(*) FROM tareas")->fetchColumn();
        $s['entregadas']      = $db->query("SELECT COUNT(*) FROM tareas WHERE estado='entregada'")->fetchColumn();
        $s['activas']         = $db->query("SELECT COUNT(*) FROM tareas WHERE estado='activa'")->fetchColumn();
        $s['total_staff']     = $db->query("SELECT COUNT(*) FROM staff_discord WHERE activo=1")->fetchColumn();
        $s['subidas_hoy']     = $db->query("SELECT COUNT(*) FROM tareas WHERE DATE(creado)='$hoy'")->fetchColumn();
        echo json_encode(['exito' => true, 'data' => $s]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
