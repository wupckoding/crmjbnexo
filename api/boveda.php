<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Encryption key derived from a constant (in production use env variable)
define('VAULT_KEY', hash('sha256', 'jbnexo-vault-2026-secret', true));

function vault_encrypt($plain) {
    if (!$plain) return null;
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'aes-256-cbc', VAULT_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function vault_decrypt($enc) {
    if (!$enc) return '';
    $data = base64_decode($enc);
    $iv = substr($data, 0, 16);
    $cipher = substr($data, 16);
    return openssl_decrypt($cipher, 'aes-256-cbc', VAULT_KEY, OPENSSL_RAW_DATA, $iv);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'list':
        $catId = $_GET['cat'] ?? '';
        $tipo = $_GET['tipo'] ?? '';
        $q = $_GET['q'] ?? '';

        $where = '1=1';
        $params = [];
        if ($catId) { $where .= ' AND b.categoria_id = :cat'; $params['cat'] = $catId; }
        if ($tipo) { $where .= ' AND b.tipo = :tipo'; $params['tipo'] = $tipo; }
        if ($q) { $where .= ' AND (b.titulo LIKE :q OR b.url LIKE :q OR b.notas LIKE :q)'; $params['q'] = "%$q%"; }

        $stmt = $pdo->prepare("SELECT b.*, c.nombre as cat_nombre, c.color as cat_color, u.nombre as creador
            FROM boveda_items b
            LEFT JOIN boveda_categorias c ON b.categoria_id = c.id
            LEFT JOIN usuarios u ON b.creado_por = u.id
            WHERE $where ORDER BY b.actualizado_en DESC");
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        // Decrypt passwords for response
        foreach ($items as &$item) {
            if ($item['password_enc']) {
                $item['password_dec'] = vault_decrypt($item['password_enc']);
            }
            unset($item['password_enc']);
        }

        // Categories
        $cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM boveda_items b WHERE b.categoria_id = c.id) as total FROM boveda_categorias c ORDER BY c.nombre")->fetchAll();

        echo json_encode(['ok' => true, 'items' => $items, 'categorias' => $cats]);
        break;

    case 'create':
        $tipo = $_POST['tipo'] ?? 'password';
        $titulo = trim($_POST['titulo'] ?? '');
        $catId = $_POST['categoria_id'] ?: null;
        $usuario_campo = trim($_POST['usuario_campo'] ?? '');
        $password = trim($_POST['password_raw'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        if (!$titulo) { echo json_encode(['error' => 'Título requerido']); exit; }

        $password_enc = $password ? vault_encrypt($password) : null;

        // File upload
        $archivo_nombre = null;
        $archivo_ruta = null;
        $archivo_size = null;
        $archivo_mime = null;

        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['archivo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeName = 'vault_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/boveda/' . $safeName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $archivo_nombre = $file['name'];
                $archivo_ruta = $safeName;
                $archivo_size = $file['size'];
                $archivo_mime = $file['type'] ?: mime_content_type($dest);
            }
        }

        $stmt = $pdo->prepare("INSERT INTO boveda_items (categoria_id, tipo, titulo, usuario_campo, password_enc, url, notas, archivo_nombre, archivo_ruta, archivo_size, archivo_mime, creado_por) VALUES (:cat, :tipo, :titulo, :user, :pass, :url, :notas, :an, :ar, :as2, :am, :cp)");
        $stmt->execute([
            'cat' => $catId, 'tipo' => $tipo, 'titulo' => $titulo,
            'user' => $usuario_campo, 'pass' => $password_enc,
            'url' => $url, 'notas' => $notas,
            'an' => $archivo_nombre, 'ar' => $archivo_ruta,
            'as2' => $archivo_size, 'am' => $archivo_mime,
            'cp' => $_SESSION['user_id']
        ]);

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $catId = $_POST['categoria_id'] ?: null;
        $usuario_campo = trim($_POST['usuario_campo'] ?? '');
        $password = trim($_POST['password_raw'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        if (!$id || !$titulo) { echo json_encode(['error' => 'Datos inválidos']); exit; }

        $password_enc = $password ? vault_encrypt($password) : null;

        // If password provided, update it; otherwise keep existing
        if ($password) {
            $stmt = $pdo->prepare("UPDATE boveda_items SET categoria_id=:cat, titulo=:titulo, usuario_campo=:user, password_enc=:pass, url=:url, notas=:notas WHERE id=:id");
            $stmt->execute(['cat'=>$catId, 'titulo'=>$titulo, 'user'=>$usuario_campo, 'pass'=>$password_enc, 'url'=>$url, 'notas'=>$notas, 'id'=>$id]);
        } else {
            $stmt = $pdo->prepare("UPDATE boveda_items SET categoria_id=:cat, titulo=:titulo, usuario_campo=:user, url=:url, notas=:notas WHERE id=:id");
            $stmt->execute(['cat'=>$catId, 'titulo'=>$titulo, 'user'=>$usuario_campo, 'url'=>$url, 'notas'=>$notas, 'id'=>$id]);
        }

        // File upload (replace)
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['archivo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeName = 'vault_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/boveda/' . $safeName;

            // Delete old file
            $old = $pdo->prepare("SELECT archivo_ruta FROM boveda_items WHERE id = :id");
            $old->execute(['id' => $id]);
            $oldFile = $old->fetchColumn();
            if ($oldFile && file_exists(__DIR__ . '/../uploads/boveda/' . $oldFile)) {
                unlink(__DIR__ . '/../uploads/boveda/' . $oldFile);
            }

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $pdo->prepare("UPDATE boveda_items SET archivo_nombre=:an, archivo_ruta=:ar, archivo_size=:as2, archivo_mime=:am WHERE id=:id")
                    ->execute(['an'=>$file['name'], 'ar'=>$safeName, 'as2'=>$file['size'], 'am'=>$file['type'], 'id'=>$id]);
            }
        }

        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }

        // Delete file
        $old = $pdo->prepare("SELECT archivo_ruta FROM boveda_items WHERE id = :id");
        $old->execute(['id' => $id]);
        $oldFile = $old->fetchColumn();
        if ($oldFile && file_exists(__DIR__ . '/../uploads/boveda/' . $oldFile)) {
            unlink(__DIR__ . '/../uploads/boveda/' . $oldFile);
        }

        $pdo->prepare("DELETE FROM boveda_items WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'download':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { http_response_code(400); exit; }

        $stmt = $pdo->prepare("SELECT archivo_nombre, archivo_ruta, archivo_mime FROM boveda_items WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $file = $stmt->fetch();

        if (!$file || !$file['archivo_ruta']) { http_response_code(404); exit; }

        $path = __DIR__ . '/../uploads/boveda/' . $file['archivo_ruta'];
        if (!file_exists($path)) { http_response_code(404); exit; }

        header('Content-Type: ' . ($file['archivo_mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $file['archivo_nombre'] . '"');
        header('Content-Length: ' . filesize($path));
        header_remove('Content-Type');
        header('Content-Type: application/octet-stream');
        readfile($path);
        exit;

    case 'create_cat':
        $nombre = trim($_POST['nombre'] ?? '');
        $color = trim($_POST['color'] ?? '#7c3aed');
        if (!$nombre) { echo json_encode(['error' => 'Nombre requerido']); exit; }
        $pdo->prepare("INSERT INTO boveda_categorias (nombre, color) VALUES (:n, :c)")->execute(['n'=>$nombre, 'c'=>$color]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'delete_cat':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        $pdo->prepare("UPDATE boveda_items SET categoria_id = NULL WHERE categoria_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM boveda_categorias WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
