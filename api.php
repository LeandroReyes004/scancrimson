<?php
define('AUTH_NO_GUARD', 1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/bridge_client.php';

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function(Throwable $e) {
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    error_log('api.php exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode(['exito' => false, 'mensaje' => 'Error interno del servidor.']);
});

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
    $token    = $_SERVER['HTTP_X_BOT_TOKEN'] ?? '';
    $expected = getenv('BOT_SECRET') ?: '';
    if (!$expected || !hash_equals($expected, $token)) {
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
    // Búsqueda exacta primero (más rápida)
    $files = driveQ("'{$parentId}' in parents and name='" . addslashes($name) . "' and mimeType='application/vnd.google-apps.folder' and trashed=false", 'files(id,name)', 1);
    if (!empty($files[0]['id'])) return $files[0]['id'];
    // Fallback case-insensitive: listar todas y comparar en PHP
    $all = driveQ("'{$parentId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false", 'files(id,name)', 200);
    $needle = mb_strtolower(trim($name));
    foreach ($all as $f) {
        if (mb_strtolower(trim($f['name'])) === $needle) return $f['id'];
    }
    return null;
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
        $db    = getDB();
        $rows  = $db->query("SELECT nombre FROM proyectos WHERE estado='activo' ORDER BY nombre")->fetchAll();
        $datos = array_column($rows, 'nombre');
        echo json_encode(['exito' => true, 'datos' => $datos]);
        break;

    case 'enlaces':
        $proyecto     = trim($_GET['proyecto'] ?? '');
        $capitulo     = trim($_GET['capitulo'] ?? '');
        $etapaBuscada = trim($_GET['etapa'] ?? 'Todas');

        if (!$proyecto || !$capitulo) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan parámetros.']);
            break;
        }

        // ── Prioridad: Apps Script (accede a Drive privado con OAuth) ────────
        $db     = getDB();
        $pstmt  = $db->prepare("SELECT carpeta_drive_id FROM proyectos WHERE nombre = ? AND estado = 'activo'");
        $pstmt->execute([$proyecto]);
        $proyRow        = $pstmt->fetch();
        $carpetaDriveId = $proyRow['carpeta_drive_id'] ?? null;

        if ($carpetaDriveId && APPS_SCRIPT_URL) {
            $asUrl = APPS_SCRIPT_URL . '?' . http_build_query([
                'action'            => 'buscarCapituloConEnlaces',
                'proyecto_drive_id' => $carpetaDriveId,
                'capitulo'          => $capitulo,
                'etapa'             => $etapaBuscada,
            ]);
            $asRes = httpGet($asUrl, 30);
            if (!empty($asRes['exito'])) {
                $mapaEtapas = [
                    'raw'   => '01. RAWs',
                    'trad'  => '02. Traducción',
                    'clean' => '03. Limpieza y Redibujo',
                    'type'  => '04. Typos',
                    'proof' => '05. Control de Calidad',
                ];
                $resultados = [];
                foreach ($asRes['etapas'] as $clave => $info) {
                    if (!empty($info['encontrado']) && !empty($info['id'])) {
                        $nombreEtapa = $mapaEtapas[$clave] ?? $clave;
                        $resultados[$nombreEtapa] = [
                            'nombre' => $info['nombre'],
                            'url'    => downloadUrl($info['id']),
                        ];
                    }
                }
                echo json_encode(['exito' => true, 'datos' => $resultados]);
                break;
            }
            // Apps Script falló → caer al fallback con API Key
        }

        // ── Fallback: API Key (solo funciona si Drive es público) ────────────
        $capInt   = intval($capitulo);
        $capRegex = '/cap[_\-\s]?0*' . $capInt . '(\.|$|[^0-9])/i';

        $proyectoId = folderIdByName(CARPETA_RAIZ_ID, $proyecto);
        if (!$proyectoId) {
            echo json_encode(['exito' => false, 'mensaje' => 'Proyecto no encontrado en Drive.']);
            break;
        }

        $todasEtapas = ["01. RAWs", "02. Traducción", "03. Limpieza y Redibujo", "04. Typos", "05. Control de Calidad"];
        $etapas      = ($etapaBuscada && $etapaBuscada !== 'Todas') ? [$etapaBuscada] : $todasEtapas;
        $resultados  = [];

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
                    $capId = folderIdByName($etapaId, "Capítulo {$capInt}");
                    if ($capId) {
                        $capFiles = driveQ("'{$capId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 1);
                        if ($capFiles) $resultados[$etapa] = ['nombre' => $capFiles[0]['name'], 'url' => downloadUrl($capFiles[0]['id'])];
                    }
                }
            } else {
                $capId = folderIdByName($etapaId, "Capítulo {$capInt}");
                if ($capId) {
                    $capFiles = driveQ("'{$capId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 1);
                    if ($capFiles) $resultados[$etapa] = ['nombre' => $capFiles[0]['name'], 'url' => downloadUrl($capFiles[0]['id'])];
                }
            }
        }
        echo json_encode(['exito' => true, 'datos' => $resultados]);
        break;

    // ── PROTEGIDAS (requieren sesión) ────────────────────────────────────────

    // ── HISTORIAL DE SUBIDAS (BD MySQL, ya no Google Sheets) ─────────────
    case 'historial':
        requireLogin();
        $db   = getDB();
        $rows = $db->query("SELECT * FROM subidas ORDER BY creado DESC LIMIT 500")->fetchAll();
        // Formato que espera el frontend: [fecha, usuario, proyecto, etapa, capitulo, archivo]
        $datos = array_map(fn($r) => [
            $r['creado'], $r['usuario'], $r['proyecto'],
            $r['etapa'],  $r['capitulo'], $r['archivo'],
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

    // ── EDITAR / CAMBIAR ESTADO / ELIMINAR SUBIDA (BD MySQL) ─────────────
    case 'editarRegistro':
        requireAdmin();
        $id    = intval($_POST['fila']  ?? 0); // 'fila' ahora es el ID de la tabla subidas
        $manga = trim($_POST['manga'] ?? '');
        $cap   = trim($_POST['cap']   ?? '');
        $etapa = trim($_POST['etapa'] ?? '');
        if (!$id || !$manga) { echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']); break; }
        $db = getDB();
        $db->prepare("UPDATE subidas SET proyecto=?, capitulo=?, etapa=? WHERE id=?")
           ->execute([$manga, $cap, $etapa, $id]);
        echo json_encode(['exito' => true, 'mensaje' => 'Registro actualizado.']);
        break;

    case 'cambiarEstado':
        requireAdmin();
        $id     = intval($_POST['fila']   ?? 0);
        $estado = trim($_POST['estado'] ?? '');
        if (!$id || !$estado) { echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']); break; }
        $db = getDB();
        $db->prepare("UPDATE subidas SET estado=? WHERE id=?")->execute([$estado, $id]);
        echo json_encode(['exito' => true, 'mensaje' => 'Estado actualizado.']);
        break;

    case 'eliminarRegistro':
        requireAdmin();
        $id = intval($_POST['fila'] ?? 0);
        if (!$id) { echo json_encode(['exito' => false, 'mensaje' => 'ID requerido.']); break; }
        $db = getDB();
        $db->prepare("DELETE FROM subidas WHERE id=?")->execute([$id]);
        echo json_encode(['exito' => true, 'mensaje' => 'Registro eliminado.']);
        break;

    // ── GESTIÓN DE PROYECTOS Y CAPÍTULOS (admin / staff) ───────────────────
    case 'listarProyectosAdmin':
        requireLogin();
        $db = getDB();
        $proys = $db->query("SELECT id, nombre, estado, carpeta_drive_id FROM proyectos ORDER BY nombre")->fetchAll();
        echo json_encode(['exito' => true, 'datos' => $proys]);
        break;

    case 'toggleEstadoProyecto':
        requireAdmin();
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['exito' => false, 'mensaje' => 'ID inválido']); break; }
        $db = getDB();
        $sel = $db->prepare("SELECT estado FROM proyectos WHERE id = ?");
        $sel->execute([$id]);
        $proy = $sel->fetch();
        if (!$proy) { echo json_encode(['exito' => false, 'mensaje' => 'Proyecto no encontrado']); break; }
        $nuevoEstado = ($proy['estado'] === 'activo') ? 'inactivo' : 'activo';
        $db->prepare("UPDATE proyectos SET estado = ? WHERE id = ?")->execute([$nuevoEstado, $id]);
        echo json_encode(['exito' => true, 'estado' => $nuevoEstado]);
        break;

    case 'setProyectoDriveId':
        requireAdmin();
        $id  = intval($_POST['id'] ?? 0);
        $did = trim($_POST['drive_id'] ?? '');
        if (!$id) { echo json_encode(['exito' => false, 'mensaje' => 'ID inválido']); break; }
        $db = getDB();
        $db->prepare("UPDATE proyectos SET carpeta_drive_id = ? WHERE id = ?")->execute([$did ?: null, $id]);
        echo json_encode(['exito' => true]);
        break;
    case 'listarCapitulos':
        requireLogin();
        $proyecto_id = intval($_GET['proyecto_id'] ?? 0);
        if (!$proyecto_id) {
            echo json_encode(['exito' => false, 'mensaje' => 'Proyecto ID requerido']);
            break;
        }
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM capitulos WHERE proyecto_id = ? ORDER BY numero DESC");
        $stmt->execute([$proyecto_id]);
        $caps = $stmt->fetchAll();
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

    case 'crearCapitulosRango':
        requireAdmin();
        $pId   = intval($_POST['proyecto_id'] ?? 0);
        $desde = intval($_POST['desde'] ?? 0);
        $hasta = intval($_POST['hasta'] ?? 0);
        if (!$pId || $desde < 1 || $hasta < $desde || ($hasta - $desde) > 200) {
            echo json_encode(['exito' => false, 'mensaje' => 'Parámetros inválidos']); break;
        }
        $db = getDB();
        $stmt = $db->prepare("INSERT IGNORE INTO capitulos (proyecto_id, numero) VALUES (?, ?)");
        $creados = 0; $omitidos = 0;
        for ($n = $desde; $n <= $hasta; $n++) {
            $stmt->execute([$pId, $n]);
            $stmt->rowCount() > 0 ? $creados++ : $omitidos++;
        }
        echo json_encode(['exito' => true, 'creados' => $creados, 'omitidos' => $omitidos]);
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
        $campo_safe = array_combine($campos_validos, $campos_validos)[$campo];
        $db->prepare("UPDATE capitulos SET `$campo_safe` = ? WHERE id = ?")->execute([$valor, $cap_id]);

        // Revisar si todo está listo
        $capStmt = $db->prepare("SELECT * FROM capitulos WHERE id = ?");
        $capStmt->execute([$cap_id]);
        $cap = $capStmt->fetch();
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

        // No permitir desactivarse a sí mismo (usa JWT, no $_SESSION que no existe en api.php)
        $currentUser = auth_get_user();
        if ($uid === (int)($currentUser['id'] ?? 0)) {
            echo json_encode(['exito' => false, 'mensaje' => 'No puedes desactivar tu propia cuenta.']);
            break;
        }

        $db = getDB();
        $db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$activo, $uid]);
        echo json_encode(['exito' => true, 'mensaje' => 'Estado actualizado.']);
        break;

    case 'adminResetPassword':
        requireAdmin();
        $uid  = intval($_POST['id'] ?? 0);
        $pass = trim($_POST['password'] ?? '');
        if (!$uid || strlen($pass) < 4) {
            echo json_encode(['exito' => false, 'mensaje' => 'Contraseña muy corta (mínimo 4 caracteres)']); break;
        }
        $db = getDB();
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $uid]);
        echo json_encode(['exito' => true, 'mensaje' => 'Contraseña actualizada.']);
        break;

    case 'eliminarUsuario':
        requireAdmin();
        $uid = intval($_POST['id'] ?? 0);

        if ($uid === (int)(auth_get_user()['id'] ?? 0)) {
            echo json_encode(['exito' => false, 'mensaje' => 'No puedes eliminar tu propia cuenta.']);
            break;
        }

        $db = getDB();
        $db->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$uid]);
        echo json_encode(['exito' => true, 'mensaje' => 'Usuario eliminado.']);
        break;

    // ── LISTAR STAFF DISCORD (directo BD) ────────────────────────────────
    case 'listarStaff':
        requireAdmin();
        $db   = getDB();
        $rows = $db->query("SELECT * FROM staff_discord ORDER BY activo DESC, nombre_display ASC")->fetchAll();
        echo json_encode(['exito' => true, 'data' => $rows]);
        break;

    // ── TOGGLE ACTIVO STAFF DISCORD (directo BD) ──────────────────────────
    case 'toggleStaff':
        requireAdmin();
        $id     = trim($_POST['discord_id'] ?? '');
        $activo = intval($_POST['activo'] ?? 1);
        if (!$id) { echo json_encode(['exito' => false, 'mensaje' => 'ID requerido']); break; }
        $db = getDB();
        $db->prepare("UPDATE staff_discord SET activo = ? WHERE discord_id = ?")->execute([$activo, $id]);
        echo json_encode(['exito' => true]);
        break;

    // ── RANKING DEL MES (directo BD) ─────────────────────────────────────
    case 'rankingMes':
        $mes  = intval($_GET['mes']  ?? date('n'));
        $anio = intval($_GET['anio'] ?? date('Y'));
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT e.discord_id, e.puntos, s.nombre_display, s.usuario_form
            FROM expedientes e
            LEFT JOIN staff_discord s ON s.discord_id = e.discord_id
            WHERE e.mes = ? AND e.anio = ? AND e.puntos > 0
            ORDER BY e.puntos DESC
        ");
        $stmt->execute([$mes, $anio]);
        echo json_encode(['exito' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── TAREAS ACTIVAS (directo BD) ───────────────────────────────────────
    case 'tareasActivas':
        requireAdmin();
        $db   = getDB();
        $stmt = $db->query("
            SELECT t.*, s.nombre_display,
                   TIMESTAMPDIFF(HOUR, NOW(), t.limite) AS horas_restantes
            FROM tareas t
            LEFT JOIN staff_discord s ON s.discord_id = t.discord_id
            WHERE t.estado = 'activa'
            ORDER BY t.limite ASC
        ");
        echo json_encode(['exito' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── HISTORIAL ERRORES STAFF (directo BD) ──────────────────────────────
    case 'erroresStaff':
        requireAdmin();
        $db  = getDB();
        $did = trim($_GET['discord_id'] ?? '');
        if ($did) {
            $stmt = $db->prepare("
                SELECT e.id, e.discord_id, e.error AS descripcion, e.reportado_por, e.fecha,
                       s.nombre_display
                FROM errores_hist e
                LEFT JOIN staff_discord s ON s.discord_id = e.discord_id
                WHERE e.discord_id = ?
                ORDER BY e.fecha DESC LIMIT 50
            ");
            $stmt->execute([$did]);
        } else {
            $stmt = $db->query("
                SELECT e.id, e.discord_id, e.error AS descripcion, e.reportado_por, e.fecha,
                       s.nombre_display
                FROM errores_hist e
                LEFT JOIN staff_discord s ON s.discord_id = e.discord_id
                ORDER BY e.fecha DESC LIMIT 50
            ");
        }
        echo json_encode(['exito' => true, 'data' => $stmt->fetchAll()]);
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
        $rolesValidos = ['Traductor', 'Limpiador', 'Typesetter', 'QC', 'Staff', 'Lider', 'Supervisor'];
        if (!$discord_id || !in_array($rol, $rolesValidos, true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos']);
            break;
        }
        $db = getDB();
        $db->prepare("UPDATE staff_discord SET rol = ? WHERE discord_id = ?")->execute([$rol, $discord_id]);
        echo json_encode(['exito' => true]);
        break;

    // ── DEBUG DRIVE ───────────────────────────────────────────────────────
    case 'debugDrive':
        requireAdmin();
        $appsUrl = APPS_SCRIPT_URL;
        $res = $appsUrl ? httpGet($appsUrl . '?action=listarProyectosConId') : null;
        echo json_encode([
            'apps_script_url'      => $appsUrl ?: '(no configurada)',
            'apps_script_respuesta' => $res,
        ]);
        break;

    // ── AUTO-DETECTAR CARPETA DRIVE POR NOMBRE ───────────────────────────
    case 'autoDetectarDriveId':
        requireAdmin();
        $id = intval($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['exito' => false, 'mensaje' => 'ID inválido']); break; }
        $db = getDB();
        $proy = $db->prepare("SELECT nombre FROM proyectos WHERE id = ?");
        $proy->execute([$id]);
        $p = $proy->fetch();
        if (!$p) { echo json_encode(['exito' => false, 'mensaje' => 'Proyecto no encontrado']); break; }
        // Buscar via Apps Script (tiene auth para Drive privado)
        $res = httpGet(APPS_SCRIPT_URL . '?action=listarProyectosConId');
        if (empty($res['datos'])) { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no devolvió proyectos']); break; }
        $needle = mb_strtolower(trim($p['nombre']));
        $fid = null;
        foreach ($res['datos'] as $item) {
            if (mb_strtolower(trim($item['nombre'])) === $needle) { $fid = $item['id']; break; }
        }
        if (!$fid) { echo json_encode(['exito' => false, 'mensaje' => 'Carpeta "' . $p['nombre'] . '" no encontrada en Drive']); break; }
        $db->prepare("UPDATE proyectos SET carpeta_drive_id = ? WHERE id = ?")->execute([$fid, $id]);
        echo json_encode(['exito' => true, 'drive_id' => $fid]);
        break;

    // ── VERIFICAR DRIVE Y SINCRONIZAR ESTADOS EN BD ───────────────────────
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

        $folderId = $proy['carpeta_drive_id'];
        if (!$folderId) {
            echo json_encode(['exito' => false, 'mensaje' => 'Proyecto sin Drive vinculado. Usa "🔍 Auto" en Gestión de Proyectos.']);
            break;
        }

        $capNum = (floor($capitulo_num) == $capitulo_num) ? (int)$capitulo_num : $capitulo_num;

        // Llamar a Apps Script (tiene auth OAuth para Drive privado)
        $url = APPS_SCRIPT_URL . '?action=verificarCapitulo'
             . '&proyecto_drive_id=' . urlencode($folderId)
             . '&capitulo=' . urlencode((string)$capNum);
        $res = httpGet($url);

        if (empty($res['exito'])) {
            echo json_encode(['exito' => false, 'mensaje' => $res['mensaje'] ?? 'Error en Apps Script']);
            break;
        }

        $etapasDb = [
            'raw'   => 'estado_raw',
            'trad'  => 'estado_trad',
            'clean' => 'estado_clean',
            'type'  => 'estado_type',
            'proof' => 'estado_proof',
        ];
        $resultado    = [];
        $dbActualizar = [];
        foreach ($res['etapas'] as $clave => $encontrado) {
            $resultado[$clave] = ['encontrado' => (bool)$encontrado, 'nombre' => $encontrado ? 'Capítulo ' . $capNum : null];
            if ($encontrado && isset($etapasDb[$clave])) $dbActualizar[$etapasDb[$clave]] = 1;
        }

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

    // ── STAFF: mis tareas activas ─────────────────────────────────────────
    case 'misTareas':
        requireLogin();
        $u  = auth_get_user();
        $db = getDB();
        $sd = $db->prepare("SELECT discord_id FROM staff_discord WHERE usuario_form = ?");
        $sd->execute([$u['usuario']]);
        $staff = $sd->fetch();
        if (!$staff) { echo json_encode(['exito' => true, 'vinculado' => false, 'data' => []]); break; }
        $rows = $db->prepare("SELECT * FROM tareas WHERE discord_id = ? AND estado = 'activa' ORDER BY limite ASC");
        $rows->execute([$staff['discord_id']]);
        echo json_encode(['exito' => true, 'vinculado' => true, 'data' => $rows->fetchAll()]);
        break;

    // ── STAFF: marcar tarea entregada ────────────────────────────────────
    case 'entregarTarea':
        requireLogin();
        $u = auth_get_user();
        $tarea_id = trim($_POST['tarea_id'] ?? '');
        if (!$tarea_id) { echo json_encode(['exito' => false, 'mensaje' => 'ID requerido']); break; }
        $db = getDB();
        $sd = $db->prepare("SELECT discord_id FROM staff_discord WHERE usuario_form = ?");
        $sd->execute([$u['usuario']]);
        $staff = $sd->fetch();
        if (!$staff) { echo json_encode(['exito' => false, 'mensaje' => 'Staff no encontrado']); break; }
        $tarea = $db->prepare("SELECT * FROM tareas WHERE id = ? AND discord_id = ? AND estado = 'activa'");
        $tarea->execute([$tarea_id, $staff['discord_id']]);
        if (!$tarea->fetch()) { echo json_encode(['exito' => false, 'mensaje' => 'Tarea no encontrada o no te pertenece']); break; }
        $db->prepare("UPDATE tareas SET estado = 'entregada' WHERE id = ?")->execute([$tarea_id]);
        // Sumar punto al expediente
        $ahora = new DateTime();
        $db->prepare("INSERT INTO expedientes (discord_id, puntos, mes, anio) VALUES (?,1,?,?)
                      ON DUPLICATE KEY UPDATE puntos = puntos + 1")
           ->execute([$staff['discord_id'], $ahora->format('n'), $ahora->format('Y')]);
        echo json_encode(['exito' => true]);
        break;

    // ── STAFF: cambiar contraseña propia ────────────────────────────────
    case 'cambiarPassword':
        requireLogin();
        $u = auth_get_user();
        $actual  = $_POST['actual']  ?? '';
        $nueva   = $_POST['nueva']   ?? '';
        $nueva2  = $_POST['nueva2']  ?? '';
        if (!$actual || !$nueva || !$nueva2) { echo json_encode(['exito' => false, 'mensaje' => 'Completa todos los campos']); break; }
        if ($nueva !== $nueva2) { echo json_encode(['exito' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden']); break; }
        if (strlen($nueva) < 6) { echo json_encode(['exito' => false, 'mensaje' => 'Mínimo 6 caracteres']); break; }
        $db = getDB();
        $row = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
        $row->execute([$u['id']]);
        $urow = $row->fetch();
        $ok = password_verify($actual, $urow['password'] ?? '') || $actual === ($urow['password'] ?? '');
        if (!$ok) { echo json_encode(['exito' => false, 'mensaje' => 'Contraseña actual incorrecta']); break; }
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $u['id']]);
        echo json_encode(['exito' => true, 'mensaje' => 'Contraseña actualizada']);
        break;

    // ── STAFF: mi ranking del mes ────────────────────────────────────────
    case 'miRanking':
        requireLogin();
        $u    = auth_get_user();
        $mes  = intval($_GET['mes']  ?? date('n'));
        $anio = intval($_GET['anio'] ?? date('Y'));
        $db   = getDB();
        $sd   = $db->prepare("SELECT discord_id FROM staff_discord WHERE usuario_form = ?");
        $sd->execute([$u['usuario']]);
        $staff = $sd->fetch();
        if (!$staff) { echo json_encode(['exito' => true, 'puntos' => 0, 'posicion' => null]); break; }
        $pts  = $db->prepare("SELECT puntos FROM expedientes WHERE discord_id = ? AND mes = ? AND anio = ?");
        $pts->execute([$staff['discord_id'], $mes, $anio]);
        $prow   = $pts->fetch();
        $puntos = $prow ? (int)$prow['puntos'] : 0;
        $pos    = $db->prepare("SELECT COUNT(*) + 1 AS pos FROM expedientes WHERE mes = ? AND anio = ? AND puntos > ?");
        $pos->execute([$mes, $anio, $puntos]);
        $posicion = (int)($pos->fetch()['pos'] ?? 1);
        $top = $db->prepare("SELECT sd.nombre_display, COALESCE(e.puntos,0) AS puntos
                             FROM staff_discord sd
                             LEFT JOIN expedientes e ON e.discord_id = sd.discord_id AND e.mes = ? AND e.anio = ?
                             WHERE sd.activo = 1 ORDER BY puntos DESC LIMIT 5");
        $top->execute([$mes, $anio]);
        echo json_encode(['exito' => true, 'puntos' => $puntos, 'posicion' => $posicion, 'top5' => $top->fetchAll()]);
        break;

    // ── ADMIN: configuración del sistema (webhook, etc.) ─────────────────
    case 'getConfigSistema':
        requireAdmin();
        $db     = getDB();
        $rows   = $db->query("SELECT clave, valor FROM config_bot")->fetchAll();
        $config = [];
        foreach ($rows as $r) $config[$r['clave']] = $r['valor'];
        echo json_encode(['exito' => true, 'config' => $config]);
        break;

    case 'setConfigSistema':
        requireAdmin();
        $clave = trim($_POST['clave'] ?? '');
        $valor = $_POST['valor'] ?? '';
        if (!$clave) { echo json_encode(['exito' => false, 'mensaje' => 'Clave requerida']); break; }
        $db = getDB();
        $db->prepare("INSERT INTO config_bot (clave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?")
           ->execute([$clave, $valor, $valor]);
        echo json_encode(['exito' => true]);
        break;

    // ── DASHBOARD STATS ───────────────────────────────────────────────────
    case 'dashboardStats':
        requireAdmin();
        $db = getDB();
        $s  = [];
        $s['proyectos_activos'] = (int)$db->query("SELECT COUNT(*) FROM proyectos WHERE estado='activo'")->fetchColumn();
        $s['staff_disponible']  = (int)$db->query("
            SELECT COUNT(*) FROM staff_discord sd
            WHERE sd.activo = 1
              AND NOT EXISTS (SELECT 1 FROM tareas t WHERE t.discord_id = sd.discord_id AND t.estado = 'activa')
        ")->fetchColumn();
        $s['tareas_activas']    = (int)$db->query("SELECT COUNT(*) FROM tareas WHERE estado='activa'")->fetchColumn();
        $s['atrasados']         = (int)$db->query("
            SELECT COUNT(*) FROM tareas WHERE estado = 'activa' AND limite IS NOT NULL AND limite < NOW()
        ")->fetchColumn();
        echo json_encode(['exito' => true, 'data' => $s]);
        break;

    // ── STAFF DISPONIBLE (sin tareas activas) ─────────────────────────────
    case 'staffDisponible':
        requireAdmin();
        $db   = getDB();
        $stmt = $db->query("
            SELECT sd.discord_id,
                   COALESCE(NULLIF(sd.nombre_display,''), NULLIF(sd.usuario_form,''), sd.discord_id) AS nombre_display,
                   sd.rol
            FROM staff_discord sd
            WHERE sd.activo = 1
              AND NOT EXISTS (
                  SELECT 1 FROM tareas t
                  WHERE t.discord_id = sd.discord_id AND t.estado = 'activa'
              )
            ORDER BY nombre_display
        ");
        echo json_encode(['exito' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── STAFF ATRASADOS / SIN ACTIVIDAD SEMANAL ──────────────────────────
    case 'staffAtrasados':
        requireAdmin();
        $db        = getDB();
        $atrasadas = $db->query("
            SELECT t.id, t.discord_id, t.obra, t.cap, t.rol, t.limite,
                   COALESCE(NULLIF(s.nombre_display,''), NULLIF(s.usuario_form,''), t.discord_id) AS nombre_display,
                   TIMESTAMPDIFF(HOUR, t.limite, NOW()) AS horas_atraso
            FROM tareas t
            LEFT JOIN staff_discord s ON s.discord_id = t.discord_id
            WHERE t.estado = 'activa'
              AND t.limite IS NOT NULL
              AND t.limite < NOW()
            ORDER BY t.limite ASC
            LIMIT 20
        ")->fetchAll();
        $inactivos = $db->query("
            SELECT sd.discord_id,
                   COALESCE(NULLIF(sd.nombre_display,''), NULLIF(sd.usuario_form,''), sd.discord_id) AS nombre_display,
                   sd.rol
            FROM staff_discord sd
            WHERE sd.activo = 1
              AND NOT EXISTS (
                  SELECT 1 FROM tareas t
                  WHERE t.discord_id = sd.discord_id
                    AND t.creado >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              )
            ORDER BY nombre_display
            LIMIT 20
        ")->fetchAll();
        echo json_encode(['exito' => true, 'atrasadas' => $atrasadas, 'inactivos' => $inactivos]);
        break;

    // ── ANUNCIAR SUBIDA (Discord webhook + Telegram) ──────────────────────
    case 'anunciarSubida':
        requireAdmin();
        $mensaje  = trim($_POST['mensaje']  ?? '');
        $link     = trim($_POST['link']     ?? '');
        $discord  = !empty($_POST['discord']);
        $telegram = !empty($_POST['telegram']);
        if (!$mensaje) { echo json_encode(['exito' => false, 'mensaje' => 'Mensaje requerido']); break; }
        // Para Telegram convertir **bold** a <b>bold</b>
        $mensajeTg = preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $mensaje);
        $db             = getDB();
        $webhookUrl     = $db->query("SELECT valor FROM config_bot WHERE clave='discord_webhook_anuncios'")->fetchColumn() ?: '';
        $telegramToken  = $db->query("SELECT valor FROM config_bot WHERE clave='telegram_token'")->fetchColumn()          ?: '';
        $telegramChatId = $db->query("SELECT valor FROM config_bot WHERE clave='telegram_chat_id'")->fetchColumn()        ?: '';
        $resultados     = [];
        if ($discord) {
            if (!$webhookUrl) {
                $resultados['discord'] = false;
            } else {
                $ch = curl_init($webhookUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode(['content' => $mensaje]),
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $resultados['discord'] = ($code >= 200 && $code < 300);
            }
        }
        if ($telegram) {
            if (!$telegramToken || !$telegramChatId) {
                $resultados['telegram'] = false;
            } else {
                $ch = curl_init("https://api.telegram.org/bot{$telegramToken}/sendMessage");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query([
                        'chat_id'    => $telegramChatId,
                        'text'       => $mensajeTg,
                        'parse_mode' => 'HTML',
                    ]),
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $resultados['telegram'] = ($code === 200);
            }
        }
        echo json_encode(['exito' => true, 'resultados' => $resultados]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
