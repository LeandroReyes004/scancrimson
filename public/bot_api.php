<?php
session_start();
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/database/db.php';

header('Content-Type: application/json; charset=utf-8');

function verificarTokenBot() {
    $token = $_GET['bot_token'] ?? '';
    if ($token !== 'crimson_bot_secret_2026') {
        http_response_code(401);
        die(json_encode(['exito'=>false, 'mensaje'=>'Token inválido']));
    }
}

verificarTokenBot();

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'botTareaCrear':
        $discord_id = $_POST['discord_id'] ?? '';
        $obra       = $_POST['obra']       ?? '';
        $cap        = $_POST['cap']        ?? '';
        $rol        = $_POST['rol']        ?? '';
        $limite     = $_POST['limite']     ?? '';

        if (!$discord_id || !$obra || !$cap || !$rol || !$limite) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Faltan parámetros']);
            break;
        }

        $db = getDB();

        $stmt = $db->prepare("SELECT id FROM proyectos WHERE nombre_upper=?");
        $stmt->execute([strtoupper($obra)]);
        $proyecto = $stmt->fetch();

        if (!$proyecto) {
            $db->prepare("INSERT INTO proyectos (nombre, nombre_upper) VALUES (?,?)")
               ->execute([$obra, strtoupper($obra)]);
            $proyecto_id = $db->lastInsertId();
        } else {
            $proyecto_id = $proyecto['id'];
        }

        $stmt = $db->prepare("SELECT id FROM capitulos WHERE proyecto_id=? AND numero=?");
        $stmt->execute([$proyecto_id, $cap]);
        $capitulo = $stmt->fetch();

        if (!$capitulo) {
            $db->prepare("INSERT INTO capitulos (proyecto_id, numero, estado) VALUES (?,?,'Pendiente')")
               ->execute([$proyecto_id, $cap]);
            $capitulo_id = $db->lastInsertId();
        } else {
            $capitulo_id = $capitulo['id'];
        }

        $campos = [
            'Traductor'   => 'trad_discord_id',
            'Cleaner'     => 'clean_discord_id',
            'Typer'       => 'type_discord_id',
            'Proofreader' => 'proof_discord_id',
        ];
        if (isset($campos[$rol])) {
            $db->prepare("UPDATE capitulos SET {$campos[$rol]}=? WHERE id=?")
               ->execute([$discord_id, $capitulo_id]);
        }

        $tid = 'T-' . time();
        $db->prepare("INSERT INTO tareas (id, discord_id, obra, cap, rol, limite, estado, capitulo_id, creado)
                      VALUES (?,?,?,?,?,?,'activa',?,NOW())")
           ->execute([$tid, $discord_id, $obra, $cap, $rol, $limite, $capitulo_id]);

        echo json_encode(['exito'=>true, 'tarea_id'=>$tid, 'capitulo_id'=>$capitulo_id]);
        break;

    case 'botTareaCompletar':
        $tarea_id = $_POST['tarea_id'] ?? '';

        if (!$tarea_id) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta tarea_id']);
            break;
        }

        $db = getDB();

        $stmt = $db->prepare("SELECT capitulo_id, rol FROM tareas WHERE id=?");
        $stmt->execute([$tarea_id]);
        $tarea = $stmt->fetch();

        if (!$tarea) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Tarea no encontrada']);
            break;
        }

        $db->prepare("UPDATE tareas SET estado='entregada' WHERE id=?")->execute([$tarea_id]);

        $campos_fecha = [
            'Traductor'   => 'trad_fecha',
            'Cleaner'     => 'clean_fecha',
            'Typer'       => 'type_fecha',
            'Proofreader' => 'proof_fecha',
        ];

        if (isset($campos_fecha[$tarea['rol']]) && $tarea['capitulo_id']) {
            $campo = $campos_fecha[$tarea['rol']];
            $db->prepare("UPDATE capitulos SET {$campo}=NOW() WHERE id=?")
               ->execute([$tarea['capitulo_id']]);

            $stmt = $db->prepare("SELECT trad_fecha, clean_fecha, type_fecha, proof_fecha FROM capitulos WHERE id=?");
            $stmt->execute([$tarea['capitulo_id']]);
            $cap = $stmt->fetch();

            if ($cap['trad_fecha'] && $cap['clean_fecha'] && $cap['type_fecha'] && $cap['proof_fecha']) {
                $db->prepare("UPDATE capitulos SET estado='Terminado' WHERE id=?")
                   ->execute([$tarea['capitulo_id']]);
            }
        }

        echo json_encode(['exito'=>true]);
        break;

    case 'botCapituloObtener':
        $obra = $_GET['obra'] ?? '';
        $cap  = $_GET['cap']  ?? '';

        if (!$obra || !$cap) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Faltan parámetros']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("
            SELECT c.* FROM capitulos c
            JOIN proyectos p ON p.id = c.proyecto_id
            WHERE p.nombre_upper=? AND c.numero=?
        ");
        $stmt->execute([strtoupper($obra), $cap]);
        $capitulo = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['exito'=>true, 'capitulo'=>$capitulo]);
        break;

    case 'botProyectoCrear':
        $nombre = $_POST['nombre'] ?? '';

        if (!$nombre) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta nombre']);
            break;
        }

        $db = getDB();
        $db->prepare("INSERT IGNORE INTO proyectos (nombre, nombre_upper) VALUES (?,?)")
           ->execute([$nombre, strtoupper($nombre)]);

        echo json_encode(['exito'=>true]);
        break;

    case 'botStaffObtener':
        $discord_id = $_GET['discord_id'] ?? '';

        if (!$discord_id) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta discord_id']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM staff_discord WHERE discord_id=?");
        $stmt->execute([$discord_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['exito'=>true, 'staff'=>$staff]);
        break;

    case 'botConfigGet':
        $clave = $_GET['clave'] ?? '';

        if (!$clave) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta clave']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT valor FROM config_bot WHERE clave=?");
        $stmt->execute([$clave]);
        $row = $stmt->fetch();

        echo json_encode(['exito'=>true, 'valor'=>$row ? $row['valor'] : null]);
        break;

    case 'botConfigSet':
        $clave = $_POST['clave'] ?? '';
        $valor = $_POST['valor'] ?? '';

        if (!$clave) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta clave']);
            break;
        }

        $db = getDB();
        $db->prepare("INSERT INTO config_bot (clave, valor) VALUES (?,?)
                      ON DUPLICATE KEY UPDATE valor=?")
           ->execute([$clave, $valor, $valor]);

        echo json_encode(['exito'=>true]);
        break;

    // Comando /cd <usuario_panel>
    // El bot envía: discord_id, nombre_display, usuario_form, roles (JSON array de roles Discord)
    // Registra o actualiza al miembro mapeando sus roles de Discord al rol del sistema
    case 'botConfigurarse':
        $discord_id     = $_POST['discord_id']     ?? '';
        $nombre_display = $_POST['nombre_display'] ?? '';
        $usuario_form   = trim($_POST['usuario_form'] ?? '');
        $roles_json     = $_POST['roles']          ?? '[]';

        if (!$discord_id || !$usuario_form) {
            echo json_encode(['exito'=>false, 'mensaje'=>'Falta discord_id o usuario_form']);
            break;
        }

        $roles = json_decode($roles_json, true) ?: [];

        // Mapa de roles Discord → rol del sistema (case-insensitive)
        $mapa = [
            'admin'      => 'Lider',   'administrator' => 'Lider',
            'rey'        => 'Lider',   'leader'  => 'Lider',
            'lider'      => 'Lider',   'líder'   => 'Lider',
            'supervisor' => 'Supervisor',
            'qc'         => 'QC',      'quality control' => 'QC',
            'typesetter' => 'Typesetter', 'typer' => 'Typesetter',
            'limpiador'  => 'Limpiador',  'cleaner' => 'Limpiador', 'redrawer' => 'Limpiador',
            'traductor'  => 'Traductor',  'translator' => 'Traductor',
        ];
        // Prioridad: Lider > Supervisor > QC > Typesetter > Limpiador > Traductor > Staff
        // Prioridad para saber cuál es el "principal", pero guardaremos todos
        $prioridad  = ['Lider', 'Supervisor', 'QC', 'Typesetter', 'Limpiador', 'Traductor', 'Staff'];
        $rolFinal   = 'Staff';
        $priIdx     = count($prioridad);
        
        $rolesValidos = [];
        foreach ($roles as $r) {
            $key  = strtolower(trim($r));
            $norm = $mapa[$key] ?? null;
            if ($norm) {
                if (!in_array($norm, $rolesValidos)) $rolesValidos[] = $norm;
                $idx = array_search($norm, $prioridad);
                if ($idx !== false && $idx < $priIdx) { $priIdx = $idx; $rolFinal = $norm; }
            }
        }
        
        if (empty($rolesValidos)) $rolesValidos[] = 'Staff';
        $rolString = implode(', ', $rolesValidos);

        $db = getDB();
        $db->prepare("
            INSERT INTO staff_discord (discord_id, nombre_display, usuario_form, rol, activo)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
              nombre_display = VALUES(nombre_display),
              usuario_form   = VALUES(usuario_form),
              rol            = VALUES(rol),
              activo         = 1
        ")->execute([$discord_id, $nombre_display, $usuario_form, $rolString]);

        echo json_encode([
            'exito'        => true,
            'rol_asignado' => $rolString,
            'mensaje'      => "✅ ¡Registrado con los roles: **{$rolString}**! Nombre: {$nombre_display} | Usuario panel: {$usuario_form}",
        ]);
        break;

    case 'botSetHiatus':
        $discord_id = $_POST['discord_id'] ?? '';
        $hiatus     = intval($_POST['hiatus'] ?? 0);

        if (!$discord_id) {
            echo json_encode(['exito' => false, 'mensaje' => 'Falta discord_id']);
            break;
        }

        $db = getDB();
        
        if ($hiatus === 1) {
            $db->prepare("UPDATE staff_discord SET en_hiatus = 1, fecha_hiatus = NOW() WHERE discord_id = ?")
               ->execute([$discord_id]);
            echo json_encode(['exito' => true, 'mensaje' => 'Marcado en hiatus']);
        } else {
            $db->prepare("UPDATE staff_discord SET en_hiatus = 0, fecha_hiatus = NULL WHERE discord_id = ?")
               ->execute([$discord_id]);
            echo json_encode(['exito' => true, 'mensaje' => 'Hiatus removido']);
        }
        break;

    case 'botTareasActivas':
        $discord_id = $_GET['discord_id'] ?? '';
        if (!$discord_id) {
            echo json_encode(['exito' => false, 'mensaje' => 'Falta discord_id']);
            break;
        }
        $db = getDB();
        $stmt = $db->prepare("SELECT id, obra, cap, rol, limite FROM tareas WHERE discord_id=? AND estado='activa'");
        $stmt->execute([$discord_id]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['exito' => true, 'tareas' => $tareas]);
        break;

    case 'botTareaCancelar':
        $tarea_id = $_POST['tarea_id'] ?? '';
        $discord_id = $_POST['discord_id'] ?? '';

        if (!$tarea_id || !$discord_id) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan parámetros (tarea_id, discord_id)']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM tareas WHERE id=? AND discord_id=? AND estado='activa'");
        $stmt->execute([$tarea_id, $discord_id]);
        $tarea = $stmt->fetch();

        if (!$tarea) {
            echo json_encode(['exito' => false, 'mensaje' => 'Tarea no encontrada o no te pertenece.']);
            break;
        }

        // Marcar la tarea como cancelación solicitada (igual que en la web)
        $db->prepare("UPDATE tareas SET cancelacion_solicitada = 1 WHERE id=?")
           ->execute([$tarea_id]);

        // Opcional: Notificar al canal de líderes (el bot puede hacerlo por su cuenta, pero lo hacemos aquí por si acaso)
        $webhookUrl = $db->query("SELECT valor FROM config_bot WHERE clave='discord_webhook_anuncios'")->fetchColumn();
        if (!$webhookUrl && defined('DISCORD_WEBHOOK')) $webhookUrl = DISCORD_WEBHOOK;
        if ($webhookUrl) {
            $payload = json_encode(['content' => "⚠️ El usuario con ID <@{$tarea['discord_id']}> ha solicitado **CANCELAR** su tarea de {$tarea['obra']} Cap {$tarea['cap']} ({$tarea['rol']}) **DESDE EL BOT DE DISCORD**.\nLos líderes deben aceptarla o rechazarla desde el panel web."]);
            $ch = curl_init(trim($webhookUrl));
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0]);
            curl_exec($ch); curl_close($ch);
        }

        echo json_encode(['exito' => true, 'mensaje' => 'Solicitud de cancelación enviada a los líderes.']);
        break;

    case 'botTareaExtender':
        $tarea_id = $_POST['tarea_id'] ?? '';
        $discord_id = $_POST['discord_id'] ?? '';

        if (!$tarea_id || !$discord_id) {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan parámetros (tarea_id, discord_id)']);
            break;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM tareas WHERE id=? AND discord_id=? AND estado='activa'");
        $stmt->execute([$tarea_id, $discord_id]);
        $tarea = $stmt->fetch();

        if (!$tarea) {
            echo json_encode(['exito' => false, 'mensaje' => 'Tarea no encontrada o no te pertenece.']);
            break;
        }

        // Marcar la tarea como extensión solicitada
        $db->prepare("UPDATE tareas SET extension_solicitada = 1 WHERE id=?")
           ->execute([$tarea_id]);

        // Notificar al canal de líderes
        $webhookUrl = $db->query("SELECT valor FROM config_bot WHERE clave='discord_webhook_anuncios'")->fetchColumn();
        if (!$webhookUrl && defined('DISCORD_WEBHOOK')) $webhookUrl = DISCORD_WEBHOOK;
        if ($webhookUrl) {
            $payload = json_encode(['content' => "⚠️ El usuario con ID <@{$tarea['discord_id']}> ha solicitado **EXTENSIÓN DE TIEMPO** para su tarea de {$tarea['obra']} Cap {$tarea['cap']} ({$tarea['rol']}) **DESDE EL BOT DE DISCORD**.\nLos líderes deben aceptarla o rechazarla desde el panel web."]);
            $ch = curl_init(trim($webhookUrl));
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0]);
            curl_exec($ch); curl_close($ch);
        }

        echo json_encode(['exito' => true, 'mensaje' => 'Solicitud de extensión enviada a los líderes.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito'=>false, 'mensaje'=>'Acción no válida']);
}
