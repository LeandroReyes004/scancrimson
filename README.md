# Crimson Scan — Panel de Gestión y Subidas

**Crimson Scan** es un sistema full-stack premium para la gestión y publicación automatizada de capítulos de manga y webtoons. Diseñado para funcionar eficientemente en entornos compartidos como Hostinger, se conecta directamente con Google Drive API (a través de un proxy robusto de Apps Script), actualiza registros en Google Sheets y envía notificaciones automáticas a canales de Discord.

---

## 🛡️ Características de Seguridad Premium Implementadas

Tras una rigurosa auditoría y refactorización de seguridad, se han implementado las siguientes protecciones de nivel empresarial:

1. **Aislamiento de Credenciales (`config.local.php`)**: Se han removido todas las claves de API, webhooks de Discord y credenciales de base de datos del control de versiones. Ahora se manejan de manera segura de forma local y aislada de Git.
2. **Cifrado de Contraseñas (BCRYPT)**: Migración completa del almacenamiento de contraseñas de texto plano a hashes seguros usando la función nativa `password_hash()` con el algoritmo de derivación de claves BCRYPT.
   * *Migración Inteligente*: Incluye un sistema de **autohash en el primer inicio de sesión** para que tus usuarios existentes no queden bloqueados; sus contraseñas se convierten automáticamente a BCRYPT en su primer login exitoso.
3. **Protección contra Fuerza Bruta**: Bloqueo automático y temporal de cuentas durante 15 minutos tras 5 intentos fallidos consecutivos de inicio de sesión.
4. **Protección Estricta contra CSRF (Cross-Site Request Forgery)**: Generación e inyección dinámica de tokens criptográficos aleatorios (`csrf_token`) en todos los formularios y validación obligatoria en cada endpoint POST de escritura del panel.
5. **Cookies de Sesión Seguras**: Configuración de flags de seguridad en las cookies de sesión (`HttpOnly`, `Secure` dinámico para HTTPS y `SameSite=Strict`) para mitigar ataques XSS y robos de sesión.
6. **Validación de Archivos en Servidor**: El proxy de subida valida estrictamente que solo se procesen extensiones autorizadas (`.zip`, `.rar`, `.cbz`, `.pdf`, `.png`, `.jpg`, `.webp`) antes de interactuar con la nube.
7. **Conexiones cURL con SSL Habilitado**: Verificación estricta de certificados SSL (`CURLOPT_SSL_VERIFYPEER => true`) en la comunicación con servicios externos.
8. **Restricción de Orígenes CORS**: Bloqueo de cabeceras abiertas (`*`) restringiendo los accesos exclusivamente al dominio del servidor.

---

## ⚙️ Estructura del Proyecto

* **`config.php`**: Archivo de configuración central. Detecta y carga dinámicamente `config.local.php` para variables privadas.
* **`auth.php`**: Guardián de sesión reutilizable que valida el acceso de staff y mantiene inicializados los tokens CSRF.
* **`login.php`**: Interfaz de autenticación con control de fuerza bruta y migración de contraseña transparente.
* **`admin.php`**: Tablero de control de administración para gestionar proyectos, registros y usuarios.
* **`subir.php`**: Panel drag-and-drop premium para la subida directa de capítulos a Google Drive mediante subidas resumibles.
* **`api.php`**: Router del servidor para operaciones AJAX CRUD de administración.
* **`upload_api.php`**: Proxy intermedio que gestiona la comunicación con Google Apps Script sin exponer credenciales al cliente final.
* **`database/`**:
  * **`db.php`**: Inicializador y ayudante de base de datos MySQL (PDO) con migraciones dinámicas para campos de seguridad.
  * **`schema.sql`**: Plano de la estructura SQL seguro listo para producción.

---

## 🚀 Instalación y Configuración Local

### Requisitos
* Servidor Web (Apache/Nginx) con **PHP 8.0+**
* Servidor **MySQL / MariaDB**

### Pasos para Configuración

1. **Clonar el proyecto** en tu directorio raíz de desarrollo.
2. **Crear el archivo `config.local.php`**: Crea un archivo llamado `config.local.php` en el directorio raíz (este archivo está configurado en `.gitignore` para que nunca se suba a Git) y define tus constantes privadas:

   ```php
   <?php
   define('GOOGLE_API_KEY',     'TU_GOOGLE_API_KEY');
   define('CARPETA_RAIZ_ID',    'ID_DE_TU_CARPETA_EN_DRIVE');
   define('HOJA_CALCULO_ID',    'ID_DE_TU_GOOGLE_SHEETS');
   define('DISCORD_WEBHOOK',    'URL_DE_TU_DISCORD_WEBHOOK');

   // --- BASE DE DATOS MySQL ---
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nombre_base_datos');
   define('DB_USER', 'usuario_base_datos');
   define('DB_PASS', 'contrasena_base_datos');

   // --- GOOGLE APPS SCRIPT ---
   define('APPS_SCRIPT_URL', 'URL_DE_TU_APPS_SCRIPT_DESPLEGADO');
   ```

3. **Base de Datos**:
   * Al acceder a cualquier panel que use la base de datos (como `login.php`), el ayudante `database/db.php` creará automáticamente la tabla `usuarios` y sembrará la cuenta administradora por defecto si está vacía.
   * **Credenciales por defecto**:
     * **Usuario**: `admin`
     * **Contraseña**: `crimson2026`
     * *(Recuerda cambiar la contraseña inmediatamente desde el panel de gestión de usuarios por seguridad).*

---

## 🛠️ Desarrollo y Buenas Prácticas

* **Nunca guardes credenciales reales en `config.php`**: Utiliza únicamente `config.local.php`.
* **Protección CSRF en AJAX**: Si creas nuevos formularios o llamadas POST, asegúrate de adjuntar el token `window.csrfToken` en el cuerpo como `csrf_token` o mediante la cabecera `X-CSRF-Token`. El helper `apiFetch` de Javascript lo hace de forma automática para peticiones AJAX estándar.