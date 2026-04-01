<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

switch ($action) {

    case 'list':
        $stmt = $pdo->query("SELECT a.*, u.nombre as autor
            FROM avisos a
            LEFT JOIN usuarios u ON a.creado_por = u.id
            ORDER BY a.fijado DESC, a.creado_en DESC");
        $avisos = $stmt->fetchAll();
        echo json_encode(['ok' => true, 'avisos' => $avisos, 'is_admin' => $isAdmin]);
        break;

    case 'create':
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Solo administradores']); exit; }
        $titulo = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $prioridad = $_POST['prioridad'] ?? 'normal';
        $fijado = (int)($_POST['fijado'] ?? 0);

        if (!$titulo || !$contenido) { echo json_encode(['error' => 'Título y contenido requeridos']); exit; }
        if (!in_array($prioridad, ['normal','importante','urgente'])) $prioridad = 'normal';

        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (in_array($_FILES['imagen']['type'], $allowed)) {
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $safeName = 'aviso_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/avisos/' . $safeName;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    $imagen = $safeName;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO avisos (titulo, contenido, prioridad, fijado, imagen, creado_por) VALUES (:t, :c, :p, :f, :img, :u)");
        $stmt->execute(['t' => $titulo, 'c' => $contenido, 'p' => $prioridad, 'f' => $fijado, 'img' => $imagen, 'u' => $_SESSION['user_id']]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update':
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Solo administradores']); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $prioridad = $_POST['prioridad'] ?? 'normal';
        $fijado = (int)($_POST['fijado'] ?? 0);
        $removeImg = (int)($_POST['remove_imagen'] ?? 0);

        if (!$id || !$titulo || !$contenido) { echo json_encode(['error' => 'Datos inválidos']); exit; }
        if (!in_array($prioridad, ['normal','importante','urgente'])) $prioridad = 'normal';

        // Handle image removal
        if ($removeImg) {
            $old = $pdo->prepare("SELECT imagen FROM avisos WHERE id = :id");
            $old->execute(['id' => $id]);
            $oldImg = $old->fetchColumn();
            if ($oldImg && file_exists(__DIR__ . '/../uploads/avisos/' . $oldImg)) {
                unlink(__DIR__ . '/../uploads/avisos/' . $oldImg);
            }
            $pdo->prepare("UPDATE avisos SET imagen = NULL WHERE id = :id")->execute(['id' => $id]);
        }

        // Handle new image upload
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (in_array($_FILES['imagen']['type'], $allowed)) {
                // Delete old image
                $old = $pdo->prepare("SELECT imagen FROM avisos WHERE id = :id");
                $old->execute(['id' => $id]);
                $oldImg = $old->fetchColumn();
                if ($oldImg && file_exists(__DIR__ . '/../uploads/avisos/' . $oldImg)) {
                    unlink(__DIR__ . '/../uploads/avisos/' . $oldImg);
                }
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $safeName = 'aviso_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/avisos/' . $safeName;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    $pdo->prepare("UPDATE avisos SET imagen = :img WHERE id = :id")->execute(['img' => $safeName, 'id' => $id]);
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE avisos SET titulo=:t, contenido=:c, prioridad=:p, fijado=:f WHERE id=:id");
        $stmt->execute(['t' => $titulo, 'c' => $contenido, 'p' => $prioridad, 'f' => $fijado, 'id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Solo administradores']); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        // Delete associated image
        $old = $pdo->prepare("SELECT imagen FROM avisos WHERE id = :id");
        $old->execute(['id' => $id]);
        $oldImg = $old->fetchColumn();
        if ($oldImg && file_exists(__DIR__ . '/../uploads/avisos/' . $oldImg)) {
            unlink(__DIR__ . '/../uploads/avisos/' . $oldImg);
        }
        $pdo->prepare("DELETE FROM avisos WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'toggle_pin':
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Solo administradores']); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        $pdo->prepare("UPDATE avisos SET fijado = NOT fijado WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
