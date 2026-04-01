<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = trim($_POST['rol'] ?? 'vendedor');
        
        if (!$nombre || !$email || !$password) {
            header('Location: ../usuarios.php?error=missing');
            exit;
        }
        
        // Check duplicate email
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :e");
        $check->execute(['e'=>$email]);
        if ($check->fetch()) {
            header('Location: ../usuarios.php?error=duplicate');
            exit;
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (:n, :e, :p, :r)");
        $stmt->execute(['n'=>$nombre, 'e'=>$email, 'p'=>$hash, 'r'=>$rol]);
        
        header('Location: ../usuarios.php?msg=created');
        exit;

    case 'update':
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = trim($_POST['rol'] ?? 'vendedor');
        $password = $_POST['password'] ?? '';

        if (!$id || !$nombre || !$email) {
            echo json_encode(['error' => 'Datos requeridos']);
            exit;
        }

        // Check duplicate email (excluding current user)
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :e AND id != :id");
        $check->execute(['e' => $email, 'id' => $id]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Email ya existe']);
            exit;
        }

        $allowedRoles = ['admin', 'gerente', 'vendedor', 'soporte'];
        if (!in_array($rol, $allowedRoles)) $rol = 'vendedor';

        if ($password && strlen($password) >= 6) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE usuarios SET nombre=:n, email=:e, rol=:r, password=:p WHERE id=:id")
                ->execute(['n'=>$nombre, 'e'=>$email, 'r'=>$rol, 'p'=>$hash, 'id'=>$id]);
        } else {
            $pdo->prepare("UPDATE usuarios SET nombre=:n, email=:e, rol=:r WHERE id=:id")
                ->execute(['n'=>$nombre, 'e'=>$email, 'r'=>$rol, 'id'=>$id]);
        }

        if (function_exists('log_activity')) {
            log_activity($pdo, 'editar', 'usuarios', "Editó usuario: $nombre");
        }
        echo json_encode(['ok' => true]);
        break;

    case 'toggle':
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 0);
        
        if (!$id) { echo json_encode(['error'=>'ID requerido']); exit; }
        
        // Prevent deactivating yourself
        if ($id == $_SESSION['user_id'] && $activo == 0) {
            echo json_encode(['error' => 'No puedes desactivarte a ti mismo']);
            exit;
        }
        
        $pdo->prepare("UPDATE usuarios SET activo = :a WHERE id = :id")->execute(['a'=>$activo, 'id'=>$id]);

        if (function_exists('log_activity')) {
            $actionLabel = $activo ? 'activó' : 'desactivó';
            log_activity($pdo, 'editar', 'usuarios', "Se $actionLabel usuario #$id");
        }
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error'=>'ID requerido']); exit; }
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['error' => 'No puedes eliminarte a ti mismo']);
            exit;
        }
        // Reasignar clientes a null antes de eliminar
        $pdo->prepare("UPDATE clientes SET asignado_a = NULL WHERE asignado_a = :id")->execute(['id'=>$id]);
        $pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute(['id'=>$id]);
        if (function_exists('log_activity')) {
            log_activity($pdo, 'eliminar', 'usuarios', "Eliminó usuario #$id");
        }
        echo json_encode(['ok' => true]);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acción no válida']);
}
