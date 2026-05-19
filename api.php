<?php
define('AUTH_NO_GUARD', 1);
require_once __DIR__ . '/auth.php';
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

// Acciones del bot (exentas de CSRF, usan token propio)
$accionesBot = ['botTareaCrear', 'botTareaCompletar', 'botCapituloObtener',
                'botProyectoCrear', 'botStaffObtener', 'botConfigGet', 'botConfigSet'];

// Protección CSRF Global para peticiones POST de escritura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($_GET['action'] ?? '', $accionesBot)) {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_token_verify($token)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Token CSRF inválido o ausente.']);
        exit;
    }
}

// ─── Auth helpers ────────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return auth_get_user() !== null;
}
function isAdmin(): bool {
    $u = auth_get_user();
    return $u !== null && $u['rol'] === 'admin';
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
function verificarTokenBot(): void {
    $token = $_SERVER['HTTP_X_BOT_TOKEN'] ?? '';
    if ($token !== 'crimson_bot_secret_2026') {
        http_response_code(401);
        die(json_encode(['exito'=>false, 'mensaje'=>'Token inválido']));
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

if (in_array($action, $accionesBot)) {
    verificarTokenBot();
}

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
        $s['total_proyectos']  = (int)$db->query("SELECT COUNT(*) FROM proyectos WHERE estado='activo'")->fetchColumn();
        $s['total_tareas']     = (int)$db->query("SELECT COUNT(*) FROM tareas")->fetchColumn();
        $s['entregadas']       = (int)$db->query("SELECT COUNT(*) FROM tareas WHERE estado='entregada'")->fetchColumn();
        $s['activas']          = (int)$db->query("SELECT COUNT(*) FROM tareas WHERE estado='activa'")->fetchColumn();
        $s['total_staff']      = (int)$db->query("SELECT COUNT(*) FROM staff_discord WHERE activo=1")->fetchColumn();
        $s['total_capitulos']  = (int)$db->query("SELECT COUNT(*) FROM capitulos")->fetchColumn();
        $s['terminados']       = (int)$db->query("SELECT COUNT(*) FROM capitulos WHERE estado_general='Publicado'")->fetchColumn();
        $s['tasa_entrega']     = $s['total_capitulos'] > 0
            ? round(($s['terminados'] / $s['total_capitulos']) * 100)
            : 0;
        echo json_encode(['exito' => true, 'data' => $s]);
        break;

    // ── ACTUALIZAR ROL STAFF DISCORD ─────────────────────────────────────
    case 'actualizarRolStaff':
        requireAdmin();
        $discord_id = trim($_POST['discord_id'] ?? '');
        $rol        = trim($_POST['rol']        ?? '');
        $rolesValidos = ['Traductor', 'Limpiador', 'Typesetter', 'QC', 'Staff', 'Admin'];
        if (!$discord_id || !in_array($rol, $rolesValidos, true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos']);
            break;
        }
        $db = getDB();
        $db->prepare("UPDATE staff_discord SET rol = ? WHERE discord_id = ?")->execute([$rol, $discord_id]);
        echo json_encode(['exito' => true]);
        break;

    // ── VERIFICAR DRIVE Y SINCRONIZAR ESTADOS EN BD ───────────────────────
    // Estructura real en Drive: Proyecto / "0X. Etapa" / "Capítulo N" (carpeta)
    case 'verificarDriveCapitulo':
        requireLogin();
        $proyecto_id  = intval($_GET['proyecto_id']  ?? 0);
        $capitulo_id  = intval($_GET['capitulo_id']  ?? 0);
        $capitulo_num = floatval($_GET['capitulo_num'] ?? 0);
        $sincronizar  = ($_GET['sync'] ?? '0') === '1';
        if (!$proyecto_id || !$capitulo_num) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan parámetros']);
            break;
        }
        $db = getDB();
        $stmt = $db->prepare("SELECT nombre, carpeta_drive_id FROM proyectos WHERE id = ?");
        $stmt->execute([$proyecto_id]);
        $proy = $stmt->fetch();
        if (!$proy) { echo json_encode(['exito' => false, 'mensaje' => 'Proyecto no encontrado']); break; }

        $folderId = $proy['carpeta_drive_id'] ?: folderIdByName(CARPETA_RAIZ_ID, $proy['nombre']);
        if (!$folderId) { echo json_encode(['exito' => false, 'mensaje' => 'Carpeta "' . $proy['nombre'] . '" no encontrada en Drive']); break; }

        // Nombre de carpeta tal como aparece en Drive: "Capítulo 6"
        $capNum = (floor($capitulo_num) == $capitulo_num) ? (int)$capitulo_num : $capitulo_num;
        $capNombreExacto  = 'Capítulo ' . $capNum;
        $capNombreAlt     = 'Capitulo ' . $capNum;   // sin tilde, por si acaso

        $etapas = [
            'raw'   => ['db' => 'estado_raw',   'drive' => '01. RAWs'],
            'trad'  => ['db' => 'estado_trad',  'drive' => '02. Traducción'],
            'clean' => ['db' => 'estado_clean', 'drive' => '03. Limpieza y Redibujo'],
            'type'  => ['db' => 'estado_type',  'drive' => '04. Typos'],
            'proof' => ['db' => 'estado_proof', 'drive' => '05. Control de Calidad'],
        ];

        $resultado   = [];
        $dbActualizar = [];

        foreach ($etapas as $clave => $info) {
            $etapaId = folderIdByName($folderId, $info['drive']);
            if (!$etapaId) {
                $resultado[$clave] = ['encontrado' => false, 'nombre' => null];
                continue;
            }
            // Buscar subcarpeta "Capítulo N" dentro de la carpeta de etapa
            $q = "'{$etapaId}' in parents and trashed=false and mimeType='application/vnd.google-apps.folder'";
            $carpetas = driveQ($q, 'files(id,name)', 100);
            $match = null;
            foreach ($carpetas as $f) {
                $n = $f['name'];
                if (strcasecmp($n, $capNombreExacto) === 0 || strcasecmp($n, $capNombreAlt) === 0
                    || preg_match('/\b' . preg_quote((string)$capNum, '/') . '\b/', $n)) {
                    $match = $f;
                    break;
                }
            }
            $encontrado = ($match !== null);
            $resultado[$clave] = ['encontrado' => $encontrado, 'nombre' => $match['name'] ?? null];
            if ($encontrado) $dbActualizar[$info['db']] = 1;
        }

        // Si se pide sincronizar y hay capítulo ID, actualizar la BD
        $actualizados = 0;
        if ($sincronizar && $capitulo_id && !empty($dbActualizar)) {
            $sets = implode(', ', array_map(fn($col) => "$col = ?", array_keys($dbActualizar)));
            $vals = array_values($dbActualizar);
            $vals[] = $capitulo_id;
            $db->prepare("UPDATE capitulos SET $sets WHERE id = ?")->execute($vals);
            $actualizados = count($dbActualizar);
        }

        echo json_encode(['exito' => true, 'etapas' => $resultado, 'actualizados' => $actualizados]);
        break;

    // ── ENDPOINTS BOT DISCORD ────────────────────────────────────────────────

    case 'botTareaCrear':
        $discord_id = $_POST['discord_id'] ?? '';
        $obra       = $_POST['obra']       ?? '';
        $cap        = $_POST['cap']        ?? '';
        $rol        = $_POST['rol']        ?? '';
        $limite     = $_POST['limite']     ?? '';

        if (!$discord_id || !$obra || !$cap || !$rol || !$limite) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Faltan parámetros']);
            break;
        }

        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM proyectos WHERE nombre_upper=?");
        $stmt->execute([strtoupper($obra)]);
        $proyecto = $stmt->fetch();

        if (!$proyecto) {
            $db->prepare("INSERT INTO proyectos (nombre, nombre_upper) VALUES (?,?)")
               ->execute([$obra, strtoupper($obra)]);
            $proyecto_id = $db->lastInsertId();
        } else {
            $proyecto_id = $proyecto['id'];
        }

        $stmt = $db->prepare("SELECT id FROM capitulos WHERE proyecto_id=? AND numero=?");
        $stmt->execute([$proyecto_id, $cap]);
        $capitulo = $stmt->fetch();

        if (!$capitulo) {
            $db->prepare("INSERT INTO capitulos (proyecto_id, numero, estado) VALUES (?,?,'Pendiente')")
               ->execute([$proyecto_id, $cap]);
            $capitulo_id = $db->lastInsertId();
        } else {
            $capitulo_id = $capitulo['id'];
        }

        $campos = [
            'Traductor'   => 'trad_discord_id',
            'Cleaner'     => 'clean_discord_id',
            'Typer'       => 'type_discord_id',
            'Proofreader' => 'proof_discord_id',
        ];
        if (isset($campos[$rol])) {
            $db->prepare("UPDATE capitulos SET {$campos[$rol]}=? WHERE id=?")
               ->execute([$discord_id, $capitulo_id]);
        }

        $tid = 'T-' . time();
        $db->prepare("INSERT INTO tareas (id, discord_id, obra, cap, rol, limite, estado, capitulo_id, creado)
                      VALUES (?,?,?,?,?,?,'activa',?,NOW())")
           ->execute([$tid, $discord_id, $obra, $cap, $rol, $limite, $capitulo_id]);

        echo json_encode(['exito'=>true, 'tarea_id'=>$tid, 'capitulo_id'=>$capitulo_id]);
        break;

    case 'botTareaCompletar':
        $tarea_id = $_POST['tarea_id'] ?? '';

        if (!$tarea_id) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta tarea_id']);
            break;
        }

        $db = getDB();

        $stmt = $db->prepare("SELECT capitulo_id, rol FROM tareas WHERE id=?");
        $stmt->execute([$tarea_id]);
        $tarea = $stmt->fetch();

        if (!$tarea) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Tarea no encontrada']);
            break;
        }

        $db->prepare("UPDATE tareas SET estado='entregada' WHERE id=?")->execute([$tarea_id]);

        $campos_fecha = [
            'Traductor'   => 'trad_fecha',
            'Cleaner'     => 'clean_fecha',
            'Typer'       => 'type_fecha',
            'Proofreader' => 'proof_fecha',
        ];

        if (isset($campos_fecha[$tarea['rol']]) && $tarea['capitulo_id']) {
            $campo = $campos_fecha[$tarea['rol']];
            $db->prepare("UPDATE capitulos SET {$campo}=NOW() WHERE id=?")
               ->execute([$tarea['capitulo_id']]);

            $stmt = $db->prepare("SELECT trad_fecha, clean_fecha, type_fecha, proof_fecha FROM capitulos WHERE id=?");
            $stmt->execute([$tarea['capitulo_id']]);
            $cap = $stmt->fetch();

            if ($cap['trad_fecha'] && $cap['clean_fecha'] && $cap['type_fecha'] && $cap['proof_fecha']) {
                $db->prepare("UPDATE capitulos SET estado='Terminado' WHERE id=?")
                   ->execute([$tarea['capitulo_id']]);
            }
        }

        echo json_encode(['exito'=>true]);
        break;

    case 'botCapituloObtener':
        $obra = $_GET['obra'] ?? '';
        $cap  = $_GET['cap']  ?? '';

        if (!$obra || !$cap) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Faltan parámetros']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("
            SELECT c.* FROM capitulos c
            JOIN proyectos p ON p.id = c.proyecto_id
            WHERE p.nombre_upper=? AND c.numero=?
        ");
        $stmt->execute([strtoupper($obra), $cap]);
        $capitulo = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['exito'=>true, 'capitulo'=>$capitulo]);
        break;

    case 'botProyectoCrear':
        $nombre = $_POST['nombre'] ?? '';

        if (!$nombre) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta nombre']);
            break;
        }

        $db = getDB();
        $db->prepare("INSERT IGNORE INTO proyectos (nombre, nombre_upper) VALUES (?,?)")
           ->execute([$nombre, strtoupper($nombre)]);

        echo json_encode(['exito'=>true]);
        break;

    case 'botStaffObtener':
        $discord_id = $_GET['discord_id'] ?? '';

        if (!$discord_id) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta discord_id']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM staff_discord WHERE discord_id=?");
        $stmt->execute([$discord_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['exito'=>true, 'staff'=>$staff]);
        break;

    case 'botConfigGet':
        $clave = $_GET['clave'] ?? '';

        if (!$clave) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta clave']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT valor FROM config_bot WHERE clave=?");
        $stmt->execute([$clave]);
        $row = $stmt->fetch();

        echo json_encode(['exito'=>true, 'valor'=>$row ? $row['valor'] : null]);
        break;

    case 'botConfigSet':
        $clave = $_POST['clave'] ?? '';
        $valor = $_POST['valor'] ?? '';

        if (!$clave) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta clave']);
            break;
        }

        $db = getDB();
        $db->prepare("INSERT INTO config_bot (clave, valor) VALUES (?,?)
                      ON DUPLICATE KEY UPDATE valor=?")
           ->execute([$clave, $valor, $valor]);

        echo json_encode(['exito'=>true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
