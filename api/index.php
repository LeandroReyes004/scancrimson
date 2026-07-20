<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si la ruta es la raíz, cargamos index.php
if ($requestUri === '/' || $requestUri === '') {
    $requestUri = '/index.php';
}

$file = __DIR__ . '/../public' . $requestUri;

if (file_exists($file) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    // Ajustar variables de entorno para que los scripts PHP funcionen como si fueran llamados directamente
    chdir(dirname($file));
    $_SERVER['SCRIPT_FILENAME'] = $file;
    $_SERVER['PHP_SELF'] = $requestUri;
    $_SERVER['SCRIPT_NAME'] = $requestUri;
    
    // Incluir y ejecutar el archivo PHP real
    require $file;
} else {
    http_response_code(404);
    echo "404 Not Found: " . htmlspecialchars($requestUri);
}
