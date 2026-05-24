<?php
// Cliente HTTP para la API puente en el VPS.
// El panel en Hostinger no puede conectar directo a MySQL (puerto 3306 bloqueado),
// por lo que todas las consultas al bot van por este bridge.

define('BRIDGE_URL', getenv('BRIDGE_URL') ?: '');
define('BRIDGE_KEY', getenv('BRIDGE_KEY') ?: '');

function bridge_call(string $action, array $params = [], string $method = 'GET'): array {
    $url = BRIDGE_URL . '?action=' . urlencode($action);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'X-Api-Key: ' . BRIDGE_KEY,
            'Accept: application/json',
        ],
    ]);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } else {
        if ($params) {
            $url .= '&' . http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err)        return ['exito' => false, 'mensaje' => 'Bridge no disponible: ' . $err];
    if ($code !== 200) return ['exito' => false, 'mensaje' => "Bridge respondió HTTP {$code}"];

    $decoded = json_decode($body, true);
    if ($decoded === null) return ['exito' => false, 'mensaje' => 'Respuesta inválida del bridge'];

    return $decoded;
}
