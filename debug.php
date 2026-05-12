<?php
// BORRAR ESTE ARCHIVO DESPUÉS DE DIAGNOSTICAR
define('GOOGLE_API_KEY', 'AIzaSyB6p7MfwKvdtqShL64YOOiNcDvvSVEaRjM');
define('CARPETA_RAIZ_ID', '1MEkmLbc2xbvZ6KxL-Dqlhw4JgqAy5Lzp');

header('Content-Type: application/json; charset=utf-8');

$url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
    'q'        => "'" . CARPETA_RAIZ_ID . "' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
    'fields'   => 'files(id,name),error',
    'pageSize' => 50,
    'key'      => GOOGLE_API_KEY,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo json_encode([
    'http_code'  => $httpCode,
    'curl_error' => $curlError,
    'url_usada'  => $url,
    'respuesta'  => json_decode($response, true),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
