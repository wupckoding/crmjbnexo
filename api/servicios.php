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

switch ($action) {

    case 'list':
        $rows = $pdo->query("SELECT s.*,
            (SELECT COUNT(*) FROM cotizacion_items ci WHERE ci.servicio_id = s.id) as veces_cotizado,
            (SELECT COUNT(*) FROM factura_items fi WHERE fi.servicio_id = s.id) as veces_facturado,
            (SELECT COALESCE(SUM(fi.subtotal),0) FROM factura_items fi JOIN facturas f ON f.id = fi.factura_id WHERE fi.servicio_id = s.id AND f.estado = 'pagada') as ingresos
            FROM servicios s ORDER BY s.nombre")->fetchAll();
        echo json_encode(['ok' => true, 'servicios' => $rows]);
        break;

    case 'create':
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio      = (float)($_POST['precio'] ?? 0);
        $categoria   = trim($_POST['categoria'] ?? 'desarrollo_web');

        if (!$nombre || $precio < 0) {
            echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO servicios (nombre, descripcion, precio, categoria) VALUES (:n, :d, :p, :c)");
        $stmt->execute(['n' => $nombre, 'd' => $descripcion, 'p' => $precio, 'c' => $categoria]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update':
        $id          = (int)($_POST['id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio      = (float)($_POST['precio'] ?? 0);
        $categoria   = trim($_POST['categoria'] ?? 'desarrollo_web');

        if (!$id || !$nombre || $precio < 0) {
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE servicios SET nombre = :n, descripcion = :d, precio = :p, categoria = :c WHERE id = :id");
        $stmt->execute(['n' => $nombre, 'd' => $descripcion, 'p' => $precio, 'c' => $categoria, 'id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false]); exit; }
        $pdo->prepare("UPDATE servicios SET activo = NOT activo WHERE id = :id")->execute(['id' => $id]);
        $nuevo = $pdo->prepare("SELECT activo FROM servicios WHERE id = :id");
        $nuevo->execute(['id' => $id]);
        echo json_encode(['ok' => true, 'activo' => (int)$nuevo->fetchColumn()]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false]); exit; }

        // Check if used in invoices/quotes
        $check = $pdo->prepare("SELECT (SELECT COUNT(*) FROM factura_items WHERE servicio_id = :id1) + (SELECT COUNT(*) FROM cotizacion_items WHERE servicio_id = :id2) as total");
        $check->execute(['id1' => $id, 'id2' => $id]);
        if ((int)$check->fetchColumn() > 0) {
            echo json_encode(['ok' => false, 'error' => 'No se puede eliminar: tiene facturas o cotizaciones asociadas']);
            exit;
        }

        $pdo->prepare("DELETE FROM servicios WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
