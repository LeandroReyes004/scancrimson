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
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
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
    
    // Redirigir la petición de inicialización al Apps Script (con SSL habilitado)
    $ch = curl_init(APPS_SCRIPT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payloadClean,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true, // Activar verificación de certificado
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POSTREDIR => 3,
        CURLOPT_USERAGENT => 'CrimsonScan/2.0',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payloadClean)
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 400 && $response) {
        // Devolvemos la respuesta del Apps Script al frontend
        echo $response;
    } else {
        error_log("Apps Script error initUpload — HTTP $httpCode: $response");
        echo json_encode([
            'exito' => false, 
            'mensaje' => 'Error al contactar con el servidor de subidas de Google.'
        ]);
    }
    
} elseif ($action === 'registrarSubida') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // 1. Validar Token CSRF
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['exito' => false, 'mensaje' => 'Token CSRF inválido o ausente.']);
        exit;
    }
    
    // 2. Inyectar de forma segura el usuario autenticado desde la sesión para auditoría
    $data['action'] = 'registrarSubida';
    $data['usuario'] = $_SESSION['user']['usuario'];
    unset($data['csrf_token']);
    
    $payloadClean = json_encode($data);
    
    // Redirigir la petición de registro a Google Sheets / Discord (con SSL habilitado)
    $ch = curl_init(APPS_SCRIPT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payloadClean,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true, // Activar verificación de certificado
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POSTREDIR => 3,
        CURLOPT_USERAGENT => 'CrimsonScan/2.0',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payloadClean)
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 400) {
        echo json_encode(['exito' => true, 'mensaje' => 'Registro completado correctamente.']);
    } else {
        error_log("Apps Script error registrarSubida — HTTP $httpCode: $response");
        echo json_encode([
            'exito' => false, 
            'mensaje' => 'Error al registrar la subida en la hoja de cálculo.'
        ]);
    }
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
