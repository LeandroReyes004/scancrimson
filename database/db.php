<?php
require_once __DIR__ . '/../config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $port = defined('DB_PORT') ? DB_PORT : '3306';
    $dsn  = 'mysql:host=' . DB_HOST . ';port=' . $port
          . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='NO_ENGINE_SUBSTITUTION', time_zone='+00:00'",
    ]);

    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            usuario         VARCHAR(50) NOT NULL UNIQUE,
            password        VARCHAR(255) NOT NULL,
            rol             ENUM('admin','staff') DEFAULT 'staff',
            activo          TINYINT(1) DEFAULT 1,
            intentos        INT DEFAULT 0,
            bloqueado_hasta DATETIME NULL,
            creado          DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Asegurar que las columnas para bloqueo de fuerza bruta existan
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN intentos INT DEFAULT 0, ADD COLUMN bloqueado_hasta DATETIME NULL");
    } catch (PDOException $e) {
        // Ignorar si las columnas ya existen
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS proyectos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                nombre_upper VARCHAR(100) NOT NULL UNIQUE,
                estado VARCHAR(20) DEFAULT 'activo',
                carpeta_drive_id VARCHAR(100) NULL,
                creado DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS capitulos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                proyecto_id INT NOT NULL,
                numero FLOAT NOT NULL,
                estado_raw TINYINT(1) DEFAULT 0,
                estado_trad TINYINT(1) DEFAULT 0,
                estado_clean TINYINT(1) DEFAULT 0,
                estado_type TINYINT(1) DEFAULT 0,
                estado_proof TINYINT(1) DEFAULT 0,
                estado_general ENUM('Pendiente', 'En proceso', 'Retrasado', 'Publicado') DEFAULT 'Pendiente',
                UNIQUE KEY proj_cap (proyecto_id, numero)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS staff_discord (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discord_id VARCHAR(50) NOT NULL UNIQUE,
                usuario_form VARCHAR(100) NULL,
                nombre_display VARCHAR(100) NULL,
                rol VARCHAR(50) DEFAULT 'Staff',
                activo TINYINT(1) DEFAULT 1,
                puntos_mes INT DEFAULT 0,
                creado DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        foreach ([
            "ALTER TABLE staff_discord ADD COLUMN rol       VARCHAR(50) DEFAULT 'Staff'",
            "ALTER TABLE staff_discord ADD COLUMN puntos_mes INT DEFAULT 0",
        ] as $m) {
            try { $pdo->exec($m); } catch (PDOException $e) { }
        }

        // Tabla de historial de subidas (reemplaza Google Sheets)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subidas (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                proyecto   VARCHAR(100) NOT NULL,
                capitulo   VARCHAR(20)  NOT NULL,
                etapa      VARCHAR(60)  NOT NULL,
                archivo    VARCHAR(255) NOT NULL,
                usuario    VARCHAR(100) NOT NULL DEFAULT '',
                estado     VARCHAR(20)  NOT NULL DEFAULT 'Activo',
                creado     DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Asegurar que proyectos.estado admita VARCHAR(20) (puede existir como VARCHAR(6) o ENUM en DBs antiguas)
        try { $pdo->exec("ALTER TABLE proyectos MODIFY COLUMN estado VARCHAR(20) DEFAULT 'activo'"); } catch (PDOException $e) { }

        // Migraciones para columnas de capitulos que pueden faltar en instancias antiguas
        foreach ([
            "ALTER TABLE capitulos ADD COLUMN estado_raw   TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN estado_trad  TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN estado_clean TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN estado_type  TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN estado_proof TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN estado_general ENUM('Pendiente','En proceso','Retrasado','Publicado') DEFAULT 'Pendiente'",
        ] as $m) {
            try { $pdo->exec($m); } catch (PDOException $e) { /* columna ya existe */ }
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tareas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discord_id VARCHAR(50) NOT NULL,
                obra VARCHAR(100) NOT NULL,
                cap VARCHAR(50) NOT NULL,
                rol VARCHAR(50) NOT NULL,
                estado VARCHAR(50) DEFAULT 'activa',
                limite DATETIME NULL,
                creado DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Columnas del bot en capitulos
        foreach ([
            "ALTER TABLE capitulos ADD COLUMN trad_discord_id  VARCHAR(50) NULL",
            "ALTER TABLE capitulos ADD COLUMN clean_discord_id VARCHAR(50) NULL",
            "ALTER TABLE capitulos ADD COLUMN type_discord_id  VARCHAR(50) NULL",
            "ALTER TABLE capitulos ADD COLUMN proof_discord_id VARCHAR(50) NULL",
            "ALTER TABLE capitulos ADD COLUMN traduccion TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN limpieza   TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN typer      TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN proof      TINYINT(1) DEFAULT 0",
            "ALTER TABLE capitulos ADD COLUMN trad_fecha  DATETIME NULL",
            "ALTER TABLE capitulos ADD COLUMN clean_fecha DATETIME NULL",
            "ALTER TABLE capitulos ADD COLUMN type_fecha  DATETIME NULL",
            "ALTER TABLE capitulos ADD COLUMN proof_fecha DATETIME NULL",
            "ALTER TABLE capitulos ADD COLUMN estado      VARCHAR(30) DEFAULT 'Pendiente'",
        ] as $m) {
            try { $pdo->exec($m); } catch (PDOException $e) { }
        }

        // Columnas del bot en tareas
        foreach ([
            "ALTER TABLE tareas ADD COLUMN capitulo_id INT NULL",
            "ALTER TABLE tareas ADD COLUMN canal_id    BIGINT NULL",
        ] as $m) {
            try { $pdo->exec($m); } catch (PDOException $e) { }
        }

        // Tablas que solo usa el bot
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS expedientes (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                discord_id VARCHAR(50) NOT NULL,
                puntos     INT DEFAULT 0,
                mes        INT NOT NULL,
                anio       INT NOT NULL,
                UNIQUE KEY discord_mes_anio (discord_id, mes, anio)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS formularios_procesados (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                timestamp_form VARCHAR(100) NOT NULL UNIQUE,
                creado         DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS errores_hist (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                discord_id    VARCHAR(50) NOT NULL,
                error         TEXT NOT NULL,
                reportado_por VARCHAR(50) NULL,
                fecha         DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS config_bot (
                clave VARCHAR(100) NOT NULL PRIMARY KEY,
                valor TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

    } catch (PDOException $e) {
        // Log error silently if user lacks privileges
    }

    // Seed: crear admin con contraseña aleatoria si la tabla está vacía
    $count = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    if ($count === 0) {
        $tempPass = bin2hex(random_bytes(10)); // 20 chars aleatorios
        $hash     = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, 'admin')")
            ->execute(['admin', $hash]);
        error_log("CRIMSON SCAN — CONTRASEÑA INICIAL ADMIN: {$tempPass} — Cámbiala después del primer login.");
    }

    return $pdo;
}
