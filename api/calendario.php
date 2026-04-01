<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];

switch ($action) {
    case 'create':
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? 'evento');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '') ?: null;
        $color = trim($_POST['color'] ?? 'nexo');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
        $asignado_a = !empty($_POST['asignado_a']) ? (int)$_POST['asignado_a'] : null;
        $todo_el_dia = (int)($_POST['todo_el_dia'] ?? 0);

        if (!$titulo || !$fecha_inicio) {
            echo json_encode(['error' => 'Título y fecha requeridos']);
            exit;
        }

        $allowedTipos = ['reunion','llamada','tarea','recordatorio','seguimiento','entrega','evento','feriado'];
        if (!in_array($tipo, $allowedTipos)) $tipo = 'evento';

        $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descripcion, tipo, fecha_inicio, fecha_fin, color, todo_el_dia, usuario_id, cliente_id, asignado_a) VALUES (:t, :d, :tp, :fi, :ff, :c, :td, :u, :cl, :aa)");
        $stmt->execute([
            't' => $titulo, 'd' => $descripcion, 'tp' => $tipo,
            'fi' => $fecha_inicio, 'ff' => $fecha_fin,
            'c' => $color, 'td' => $todo_el_dia,
            'u' => $uid, 'cl' => $cliente_id, 'aa' => $asignado_a
        ]);

        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? 'evento');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '') ?: null;
        $color = trim($_POST['color'] ?? 'nexo');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
        $asignado_a = !empty($_POST['asignado_a']) ? (int)$_POST['asignado_a'] : null;
        $todo_el_dia = (int)($_POST['todo_el_dia'] ?? 0);

        if (!$id || !$titulo || !$fecha_inicio) {
            echo json_encode(['error' => 'Datos requeridos']);
            exit;
        }

        // Verify ownership or assignment
        $check = $pdo->prepare("SELECT id FROM eventos WHERE id = :id AND (usuario_id = :u OR asignado_a = :u2)");
        $check->execute(['id' => $id, 'u' => $uid, 'u2' => $uid]);
        if (!$check->fetch()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $allowedTipos = ['reunion','llamada','tarea','recordatorio','seguimiento','entrega','evento','feriado'];
        if (!in_array($tipo, $allowedTipos)) $tipo = 'evento';

        $stmt = $pdo->prepare("UPDATE eventos SET titulo = :t, descripcion = :d, tipo = :tp, fecha_inicio = :fi, fecha_fin = :ff, color = :c, todo_el_dia = :td, cliente_id = :cl, asignado_a = :aa WHERE id = :id");
        $stmt->execute([
            't' => $titulo, 'd' => $descripcion, 'tp' => $tipo,
            'fi' => $fecha_inicio, 'ff' => $fecha_fin,
            'c' => $color, 'td' => $todo_el_dia,
            'cl' => $cliente_id, 'aa' => $asignado_a, 'id' => $id
        ]);

        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        $pdo->prepare("DELETE FROM eventos WHERE id = :id AND (usuario_id = :u OR asignado_a = :u2)")->execute(['id' => $id, 'u' => $uid, 'u2' => $uid]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
