<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

// ─── PROXY APPS SCRIPT ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'initUpload') {
    $input = file_get_contents('php://input');
    
    // Verifica que tengamos la URL configurada
    if (!APPS_SCRIPT_URL) {
        echo json_encode(['exito' => false, 'mensaje' => 'APPS_SCRIPT_URL no está configurada en config.php']);
        exit;
    }
    
    // Redirigir la petición al Apps Script
    $ch = curl_init(APPS_SCRIPT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $input,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POSTREDIR => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($input)
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 400 && $response) {
        // Devolvemos la respuesta del Apps Script tal cual al frontend
        echo $response;
    } else {
        echo json_encode([
            'exito' => false, 
            'mensaje' => 'Error al contactar con Apps Script.',
            'debug_code' => $httpCode,
            'debug_response' => $response
        ]);
    }
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
}
