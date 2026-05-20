<?php
require_once __DIR__ . '/auth.php'; // Requiere sesión segura activa y valida tokens CSRF
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido. Solo se admite POST.']);
    exit;
}

if ($action === 'initUpload') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 1. Validar Token CSRF
    if (!csrf_token_verify($data['csrf_token'] ?? '')) {
        echo json_encode(['exito' => false, 'mensaje' => 'Token CSRF inválido o ausente.']);
        exit;
    }
    
    // 2. Validar tipo / extensión de archivo (Seguridad en subida)
    $nombreArchivo = $data['filename'] ?? '';
    $extensionesPermitidas = ['zip', 'rar', 'cbz', 'pdf', 'jpg', 'png', 'webp'];
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $extensionesPermitidas, true)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Tipo de archivo no permitido. Solo se permiten .zip o .rar']);
        exit;
    }
    
    // 3. Limpiar el token de CSRF para enviarlo limpio a Apps Script
    unset($data['csrf_token']);
    $payloadClean = json_encode($data);
    
    // Verifica que tengamos la URL de Apps Script configurada
    if (!APPS_SCRIPT_URL) {
        echo json_encode(['exito' => false, 'mensaje' => 'APPS_SCRIPT_URL no está configurada en config.php']);
        exit;
    }
    
    // Paso 1: POST a Apps Script sin seguir redirects para preservar la URL exacta del echo
    $ch = curl_init(APPS_SCRIPT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => $payloadClean,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_SSL_VERIFYPEER  => true,
        CURLOPT_SSL_VERIFYHOST  => 2,
        CURLOPT_FOLLOWLOCATION  => false,
        CURLOPT_HEADER          => true,
        CURLOPT_TIMEOUT         => 30,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_USERAGENT       => 'CrimsonScan/2.0',
        CURLOPT_HTTPHEADER      => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payloadClean),
        ],
    ]);
    $raw      = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hdrSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error de red: ' . $curlErr]);
        exit;
    }

    // Paso 2: Si Apps Script devuelve un redirect, seguirlo manualmente como GET
    if ($httpCode >= 300 && $httpCode < 400) {
        $responseHeaders = substr($raw, 0, $hdrSize);
        preg_match('/^Location:\s*(.+)$/mi', $responseHeaders, $m);
        $locationUrl = trim($m[1] ?? '');

        if (!$locationUrl) {
            echo json_encode(['exito' => false, 'mensaje' => "Redirect HTTP $httpCode sin Location header."]);
            exit;
        }

        $ch2 = curl_init($locationUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'CrimsonScan/2.0',
        ]);
        $response = curl_exec($ch2);
        $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
    } else {
        $response = substr($raw, $hdrSize);
    }

    if ($httpCode === 200 && $response) {
        echo $response;
    } else {
        $preview = substr($response ?: '', 0, 300);
        if ($httpCode === 401 || $httpCode === 403) {
            $detalle = "Apps Script requiere autenticación (HTTP $httpCode). Ve a Apps Script → Implementar → Editar → Acceso: 'Cualquier persona'.";
        } elseif ($httpCode === 0) {
            $detalle = "Sin respuesta del servidor. Verifica que APPS_SCRIPT_URL esté configurada en Vercel.";
        } else {
            $detalle = "HTTP $httpCode — " . ($preview ?: 'respuesta vacía');
        }
        error_log("Apps Script initUpload — HTTP $httpCode: $preview");
        echo json_encode(['exito' => false, 'mensaje' => $detalle]);
    }
    
} elseif ($action === 'registrarSubida') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 1. Validar Token CSRF
    if (!csrf_token_verify($data['csrf_token'] ?? '')) {
        echo json_encode(['exito' => false, 'mensaje' => 'Token CSRF inválido o ausente.']);
        exit;
    }

    // 2. Obtener usuario autenticado desde JWT
    $usuario = $_SESSION['user']['usuario'] ?? '';
    $proyecto  = trim($data['proyecto']  ?? '');
    $capitulo  = trim($data['capitulo']  ?? '');
    $etapa     = trim($data['etapa']     ?? '');
    $filename  = trim($data['filename']  ?? '');

    if (!$proyecto || !$capitulo || !$etapa || !$filename) {
        echo json_encode(['exito' => false, 'mensaje' => 'Faltan campos requeridos.']);
        exit;
    }

    // 3. Guardar en MySQL (tabla subidas)
    try {
        require_once __DIR__ . '/database/db.php';
        $db = getDB();
        $db->prepare("INSERT INTO subidas (proyecto, capitulo, etapa, archivo, usuario) VALUES (?, ?, ?, ?, ?)")
           ->execute([$proyecto, $capitulo, $etapa, $filename, $usuario]);
    } catch (PDOException $e) {
        error_log("MySQL registrarSubida error: " . $e->getMessage());
        echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar en base de datos.']);
        exit;
    }

    // 4. Notificar Discord directamente desde PHP
    $webhook = defined('DISCORD_WEBHOOK') ? DISCORD_WEBHOOK : '';
    if ($webhook) {
        try {
            $payload = json_encode([
                'embeds' => [[
                    'title'       => '📤 Nueva Subida — Crimson Scan',
                    'description' => 'Archivo procesado desde el Panel Web.',
                    'color'       => 15158332,
                    'fields'      => [
                        ['name' => 'Proyecto',   'value' => $proyecto,  'inline' => true],
                        ['name' => 'Capítulo',   'value' => $capitulo,  'inline' => true],
                        ['name' => 'Etapa',      'value' => $etapa,     'inline' => true],
                        ['name' => 'Archivo',    'value' => $filename,  'inline' => false],
                        ['name' => 'Subido por', 'value' => $usuario ?: 'Desconocido', 'inline' => true],
                    ],
                    'footer'    => ['text' => 'Crimson Scan'],
                    'timestamp' => date('c'),
                ]]
            ]);
            $ch = curl_init($webhook);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            error_log("Discord webhook error: " . $e->getMessage());
        }
    }

    echo json_encode(['exito' => true, 'mensaje' => 'Registro completado correctamente.']);
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
