<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ─── HTTP helper ────────────────────────────────────────────────────────────
function httpGet(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$res) return null;
    return json_decode($res, true);
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

// ─── ROUTER ─────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {

    // Lista todos los proyectos (carpetas en raíz)
    case 'proyectos':
        $files = driveQ(
            "'" . CARPETA_RAIZ_ID . "' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
            'files(id,name)',
            200
        );
        $nombres = array_column($files, 'name');
        sort($nombres);
        echo json_encode(['exito' => true, 'datos' => $nombres]);
        break;

    // Busca los enlaces de un capítulo en un proyecto
    case 'enlaces':
        $proyecto      = trim($_GET['proyecto'] ?? '');
        $capitulo      = intval($_GET['capitulo'] ?? 0);
        $etapaBuscada  = trim($_GET['etapa'] ?? 'Todas');

        if (!$proyecto || !$capitulo) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan parámetros.']);
            break;
        }

        $proyectoId = folderIdByName(CARPETA_RAIZ_ID, $proyecto);
        if (!$proyectoId) {
            echo json_encode(['exito' => false, 'mensaje' => 'Proyecto no encontrado.']);
            break;
        }

        $todasEtapas = [
            "01. RAWs",
            "02. Traducción",
            "03. Limpieza y Redibujo",
            "04. Typos",
            "05. Control de Calidad",
        ];
        $etapas = ($etapaBuscada && $etapaBuscada !== 'Todas') ? [$etapaBuscada] : $todasEtapas;

        $resultados = [];
        $capRegex   = '/cap[_\-\s]?0*' . $capitulo . '(\.|$|[^0-9])/i';

        foreach ($etapas as $etapa) {
            $etapaId = folderIdByName($proyectoId, $etapa);
            if (!$etapaId) continue;

            if ($etapa === "01. RAWs") {
                // 1️⃣ Buscar archivo directo (Cap_1.zip, Cap_1.rar, etc.)
                $archivos = driveQ(
                    "'{$etapaId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'",
                    'files(id,name)',
                    200
                );
                $encontrado = false;
                foreach ($archivos as $arc) {
                    if (preg_match($capRegex, $arc['name'])) {
                        $resultados[$etapa] = ['nombre' => $arc['name'], 'url' => downloadUrl($arc['id'])];
                        $encontrado = true;
                        break;
                    }
                }

                // 2️⃣ Si no, buscar subcarpeta "Capítulo X"
                if (!$encontrado) {
                    $capId = folderIdByName($etapaId, "Capítulo {$capitulo}");
                    if ($capId) {
                        $capFiles = driveQ("'{$capId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 1);
                        if ($capFiles) {
                            $resultados[$etapa] = ['nombre' => $capFiles[0]['name'], 'url' => downloadUrl($capFiles[0]['id'])];
                        }
                    }
                }

            } else {
                // Otras etapas: subcarpeta "Capítulo X"
                $capId = folderIdByName($etapaId, "Capítulo {$capitulo}");
                if ($capId) {
                    $capFiles = driveQ("'{$capId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'", 'files(id,name)', 1);
                    if ($capFiles) {
                        $resultados[$etapa] = ['nombre' => $capFiles[0]['name'], 'url' => downloadUrl($capFiles[0]['id'])];
                    }
                }
            }
        }

        echo json_encode(['exito' => true, 'datos' => $resultados]);
        break;

    // Historial desde Google Sheets
    case 'historial':
        $url  = SHEETS_API . '/' . HOJA_CALCULO_ID . '/values/A%3AZ?key=' . GOOGLE_API_KEY;
        $data = httpGet($url);
        $filas = $data['values'] ?? [];
        if (count($filas) <= 1) { echo json_encode(['exito' => true, 'datos' => []]); break; }
        array_shift($filas); // quitar encabezado
        $filas = array_filter($filas, fn($f) => !empty(trim($f[0] ?? '')));
        $filas = array_reverse(array_values($filas));
        echo json_encode(['exito' => true, 'datos' => $filas]);
        break;

    // Crear proyecto (requiere APPS_SCRIPT_URL configurado)
    case 'crearProyecto':
        $pass    = $_POST['pass']    ?? '';
        $nombre  = trim($_POST['nombre'] ?? '');
        if ($pass !== CONTRASENA_ADMIN) { echo json_encode(['exito' => false, 'mensaje' => 'Contraseña incorrecta.']); break; }
        if (!$nombre)                   { echo json_encode(['exito' => false, 'mensaje' => 'Nombre vacío.']); break; }
        if (!APPS_SCRIPT_URL)           { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script URL no configurada en config.php']); break; }

        $url = APPS_SCRIPT_URL . '?action=crearProyecto&nombre=' . urlencode($nombre);
        $res = httpGet($url);
        echo json_encode($res ?? ['exito' => false, 'mensaje' => 'Error al contactar Apps Script.']);
        break;

    // Editar registro del historial (requiere soporte en Apps Script)
    case 'editarRegistro':
        $pass   = $_POST['pass']   ?? '';
        $fila   = $_POST['fila']   ?? '';
        $manga  = $_POST['manga']  ?? '';
        $cap    = $_POST['cap']    ?? '';
        $etapa  = $_POST['etapa']  ?? '';

        if ($pass !== CONTRASENA_ADMIN) { echo json_encode(['exito' => false, 'mensaje' => 'No autorizado.']); break; }
        if (!$fila || !$manga) { echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']); break; }
        if (!APPS_SCRIPT_URL) { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no configurado.']); break; }

        $url = APPS_SCRIPT_URL . '?action=editarRegistro&fila=' . urlencode($fila) . '&manga=' . urlencode($manga) . '&cap=' . urlencode($cap) . '&etapa=' . urlencode($etapa);
        $res = httpGet($url);
        echo json_encode($res ?? ['exito' => false, 'mensaje' => 'Error al contactar Apps Script.']);
        break;

    // Cambiar estado (Activo/Inactivo)
    case 'cambiarEstado':
        $pass   = $_POST['pass']   ?? '';
        $fila   = $_POST['fila']   ?? '';
        $estado = $_POST['estado'] ?? '';

        if ($pass !== CONTRASENA_ADMIN) { echo json_encode(['exito' => false, 'mensaje' => 'No autorizado.']); break; }
        if (!$fila || !$estado) { echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos.']); break; }
        if (!APPS_SCRIPT_URL) { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no configurado.']); break; }

        $url = APPS_SCRIPT_URL . '?action=cambiarEstado&fila=' . urlencode($fila) . '&estado=' . urlencode($estado);
        $res = httpGet($url);
        echo json_encode($res ?? ['exito' => false, 'mensaje' => 'Error al contactar Apps Script.']);
        break;

    // Eliminar registro del historial (requiere soporte en Apps Script)
    case 'eliminarRegistro':
        $pass  = $_POST['pass']  ?? '';
        $fila  = $_POST['fila']  ?? ''; // Índice o identificador
        if ($pass !== CONTRASENA_ADMIN) { echo json_encode(['exito' => false, 'mensaje' => 'No autorizado.']); break; }
        if (!APPS_SCRIPT_URL) { echo json_encode(['exito' => false, 'mensaje' => 'Apps Script no configurado.']); break; }
        
        $url = APPS_SCRIPT_URL . '?action=eliminarRegistro&fila=' . urlencode($fila);
        $res = httpGet($url);
        echo json_encode($res ?? ['exito' => false, 'mensaje' => 'Error al contactar Apps Script.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
