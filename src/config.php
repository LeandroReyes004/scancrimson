<?php
// --- CONFIGURACIÓN PRINCIPAL ---

// Cargar configuración local privada si existe (aislada de Git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Google API — lee de variables de entorno (Vercel) o config.local.php
if (!defined('GOOGLE_API_KEY'))
    define('GOOGLE_API_KEY',  getenv('GOOGLE_API_KEY')  ?: 'AIzaSy_placeholder_key_here');
if (!defined('CARPETA_RAIZ_ID'))
    define('CARPETA_RAIZ_ID', getenv('CARPETA_RAIZ_ID') ?: '1MEkmLbc2xbvZ_placeholder_root_folder_id');
if (!defined('HOJA_CALCULO_ID'))
    define('HOJA_CALCULO_ID', getenv('HOJA_CALCULO_ID') ?: '15rsdxNP8gcy_placeholder_sheet_id');
if (!defined('DISCORD_WEBHOOK'))
    define('DISCORD_WEBHOOK', getenv('DISCORD_WEBHOOK') ?: '');

// Apps Script URL — acepta tanto APPS_SCRIPT_URL como APP_SCRIPT_URL (por compatibilidad con Vercel)
if (!defined('APPS_SCRIPT_URL')) {
    $appsScriptUrl = getenv('APPS_SCRIPT_URL') ?: getenv('APP_SCRIPT_URL') ?: '';
    define('APPS_SCRIPT_URL', $appsScriptUrl);
}

// Generar una ruta base dinámica para cargar assets sin importar si es Vercel, Hostinger o Local
$base_url = isset($_SERVER['PHP_SELF']) ? dirname($_SERVER['PHP_SELF']) : '';
if ($base_url === '/' || $base_url === '\\') $base_url = '';
if (!defined('BASE_URL')) define('BASE_URL', $base_url);

// --- BASE DE DATOS MySQL ---
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'crimson_scan');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');

define('DRIVE_API',  'https://www.googleapis.com/drive/v3');
define('SHEETS_API', 'https://sheets.googleapis.com/v4/spreadsheets');
define('CREDENTIALS_FILE', __DIR__ . '/credentials.json');
