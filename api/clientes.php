<?php
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$userRol = $_SESSION['usuario_rol'] ?? 'vendedor';
$isAdmin = ($userRol === 'admin');

// Helper: check if current user owns the client or is admin
function canAccess($pdo, $clientId, $userId, $isAdmin) {
    if ($isAdmin) return true;
    $stmt = $pdo->prepare("SELECT asignado_a FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $clientId]);
    $row = $stmt->fetch();
    return $row && (int)$row['asignado_a'] === $userId;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        
        if (!$nombre) { echo json_encode(['error' => 'Nombre requerido']); exit; }

        // Admin can assign to anyone; non-admin auto-assigns to self
        $assignTo = $userId;
        if ($isAdmin && isset($_POST['asignado_a']) && $_POST['asignado_a'] !== '') {
            $assignTo = (int)$_POST['asignado_a'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, empresa, sitio_web, direccion, notas, asignado_a) VALUES (:n, :e, :t, :em, :sw, :d, :no, :u)");
        $stmt->execute(['n'=>$nombre, 'e'=>$email, 't'=>$telefono, 'em'=>$empresa, 'sw'=>trim($_POST['sitio_web'] ?? ''), 'd'=>trim($_POST['direccion'] ?? ''), 'no'=>trim($_POST['notas'] ?? ''), 'u'=>$assignTo]);
        log_activity($pdo, 'crear', 'clientes', 'Creó cliente: ' . $nombre);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        
        if (!$id || !$nombre) { echo json_encode(['error' => 'Datos inválidos']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        
        // Handle assignment change (admin only)
        $assignSql = '';
        $params = ['n'=>$nombre, 'e'=>$email, 't'=>$telefono, 'em'=>$empresa, 'es'=>$estado, 'sw'=>trim($_POST['sitio_web'] ?? ''), 'd'=>trim($_POST['direccion'] ?? ''), 'no'=>trim($_POST['notas'] ?? ''), 'id'=>$id];
        if ($isAdmin && isset($_POST['asignado_a']) && $_POST['asignado_a'] !== '') {
            $assignSql = ', asignado_a=:aa';
            $params['aa'] = (int)$_POST['asignado_a'];
        }
        $stmt = $pdo->prepare("UPDATE clientes SET nombre=:n, email=:e, telefono=:t, empresa=:em, estado=:es, sitio_web=:sw, direccion=:d, notas=:no{$assignSql} WHERE id=:id");
        $stmt->execute($params);
        log_activity($pdo, 'editar', 'clientes', 'Editó cliente: ' . $nombre);
        echo json_encode(['ok' => true]);
        break;

    case 'update_estado':
        $id = (int)($_POST['id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');
        $valid = ['nuevo','contactado','negociando','propuesta','ganado','perdido'];
        if (!$id || !in_array($estado, $valid)) { echo json_encode(['error' => 'Datos inválidos']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        $pdo->prepare("UPDATE clientes SET estado = :e WHERE id = :id")->execute(['e'=>$estado, 'id'=>$id]);
        $cliName = $pdo->prepare("SELECT nombre FROM clientes WHERE id = :id"); $cliName->execute(['id'=>$id]); $cliName = $cliName->fetchColumn() ?: '#'.$id;
        log_activity($pdo, 'mover', 'pipeline', "Movió \"$cliName\" a $estado");
        echo json_encode(['ok' => true]);
        break;

    case 'update_note':
        $id = (int)($_POST['id'] ?? 0);
        $notas = trim($_POST['notas'] ?? '');
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        $pdo->prepare("UPDATE clientes SET notas = :n WHERE id = :id")->execute(['n'=>$notas, 'id'=>$id]);
        echo json_encode(['ok' => true]);
        break;

    case 'assign':
        if (!$isAdmin) { echo json_encode(['error' => 'Solo administradores']); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $asignadoA = $_POST['asignado_a'] ?? '';
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        $newAssign = $asignadoA !== '' ? (int)$asignadoA : null;
        $cliName = $pdo->prepare("SELECT nombre FROM clientes WHERE id = :id"); $cliName->execute(['id'=>$id]); $cliName = $cliName->fetchColumn() ?: '#'.$id;
        $pdo->prepare("UPDATE clientes SET asignado_a = :a WHERE id = :id")->execute(['a'=>$newAssign, 'id'=>$id]);
        $agentName = 'nadie';
        if ($newAssign) {
            $stName = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = :id");
            $stName->execute(['id' => $newAssign]);
            $agentName = $stName->fetchColumn() ?: 'ID ' . $newAssign;
        }
        log_activity($pdo, 'asignar', 'clientes', "Asignó \"$cliName\" a $agentName");
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        $cliName = $pdo->prepare("SELECT nombre FROM clientes WHERE id = :id"); $cliName->execute(['id'=>$id]); $cliName = $cliName->fetchColumn() ?: '#'.$id;
        $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute(['id'=>$id]);
        log_activity($pdo, 'eliminar', 'clientes', 'Eliminó cliente: ' . $cliName);
        echo json_encode(['ok' => true]);
        break;

    case 'archive':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        $cliName = $pdo->prepare("SELECT nombre FROM clientes WHERE id = :id"); $cliName->execute(['id'=>$id]); $cliName = $cliName->fetchColumn() ?: '#'.$id;
        $pdo->prepare("UPDATE clientes SET archivado = 1 WHERE id = :id")->execute(['id'=>$id]);
        log_activity($pdo, 'archivar', 'clientes', 'Archivó cliente: ' . $cliName);
        echo json_encode(['ok' => true]);
        break;

    case 'unarchive':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        $cliName = $pdo->prepare("SELECT nombre FROM clientes WHERE id = :id"); $cliName->execute(['id'=>$id]); $cliName = $cliName->fetchColumn() ?: '#'.$id;
        $pdo->prepare("UPDATE clientes SET archivado = 0 WHERE id = :id")->execute(['id'=>$id]);
        log_activity($pdo, 'desarchivar', 'clientes', 'Desarchivó cliente: ' . $cliName);
        echo json_encode(['ok' => true]);
        break;

    case 'upload_foto':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        if (!canAccess($pdo, $id, $userId, $isAdmin)) { echo json_encode(['error' => 'Sin permiso']); exit; }
        if (empty($_FILES['foto']['tmp_name'])) { echo json_encode(['error' => 'Archivo requerido']); exit; }
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) { echo json_encode(['error' => 'Solo imágenes JPG, PNG, WebP, GIF']); exit; }
        if ($_FILES['foto']['size'] > 5 * 1024 * 1024) { echo json_encode(['error' => 'Máximo 5MB']); exit; }
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
        $filename = 'cli_' . $id . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../uploads/clientes/' . $filename;
        // Delete old photo
        $old = $pdo->prepare("SELECT foto FROM clientes WHERE id = :id");
        $old->execute(['id'=>$id]);
        $oldFile = $old->fetchColumn();
        if ($oldFile && file_exists(__DIR__ . '/../uploads/clientes/' . $oldFile)) {
            unlink(__DIR__ . '/../uploads/clientes/' . $oldFile);
        }
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) { echo json_encode(['error' => 'Error al subir']); exit; }
        $pdo->prepare("UPDATE clientes SET foto = :f WHERE id = :id")->execute(['f'=>$filename, 'id'=>$id]);
        echo json_encode(['ok' => true, 'foto' => $filename]);
        break;

    case 'bulk_to_pipeline':
        $idsRaw = $_POST['ids'] ?? '';
        $etapa  = trim($_POST['etapa'] ?? '');
        $valid  = ['nuevo','contactado','negociando','propuesta','ganado','perdido'];
        if (!$idsRaw || !in_array($etapa, $valid)) {
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }
        $ids = array_filter(array_map('intval', explode(',', $idsRaw)));
        if (empty($ids)) {
            echo json_encode(['error' => 'Sin clientes seleccionados']);
            exit;
        }
        $moved = 0;
        foreach ($ids as $cid) {
            if (!canAccess($pdo, $cid, $userId, $isAdmin)) continue;
            $pdo->prepare("UPDATE clientes SET estado = :e, archivado = 0 WHERE id = :id")->execute(['e' => $etapa, 'id' => $cid]);
            $moved++;
        }
        log_activity($pdo, 'mover', 'pipeline', "Envió $moved cliente(s) a etapa: $etapa");
        echo json_encode(['ok' => true, 'moved' => $moved]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
