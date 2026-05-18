<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

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

    default:
        http_response_code(400);
        echo json_encode(['exito'=>false, 'mensaje'=>'Acción no válida']);
}
