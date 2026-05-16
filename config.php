<?php
// --- CONFIGURACIÓN PRINCIPAL ---

// Cargar configuración local privada si existe (aislada de Git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Marcadores de posición / fallbacks en producción si no están definidos localmente
if (!defined('GOOGLE_API_KEY'))  define('GOOGLE_API_KEY',  'AIzaSy_placeholder_key_here');
if (!defined('CARPETA_RAIZ_ID')) define('CARPETA_RAIZ_ID', '1MEkmLbc2xbvZ_placeholder_root_folder_id');
if (!defined('HOJA_CALCULO_ID')) define('HOJA_CALCULO_ID', '15rsdxNP8gcy_placeholder_sheet_id');
if (!defined('DISCORD_WEBHOOK')) define('DISCORD_WEBHOOK', 'https://discordapp.com/api/webhooks/placeholder_webhook_url');

// --- BASE DE DATOS MySQL (Hostinger) ---
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'u687815389_crimson_scan');
if (!defined('DB_USER')) define('DB_USER', 'u687815389_Admin1025');
if (!defined('DB_PASS')) define('DB_PASS', 'Apolo9090###');

// URL de tu Apps Script desplegado (solo para crear proyectos desde admin)
if (!defined('APPS_SCRIPT_URL')) define('APPS_SCRIPT_URL', '');

define('DRIVE_API',  'https://www.googleapis.com/drive/v3');
define('SHEETS_API', 'https://sheets.googleapis.com/v4/spreadsheets');
define('CREDENTIALS_FILE', __DIR__ . '/credentials.json');
