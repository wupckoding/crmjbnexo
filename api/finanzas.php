<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

$allowedMetodos = ['transferencia','paypal','stripe','efectivo','crypto','otro'];
$allowedFreq = ['unico','mensual','anual'];

switch ($action) {

    // ===== GASTO =====
    case 'gasto':
        $desc = trim($_POST['descripcion'] ?? '');
        $monto = (float)($_POST['monto'] ?? 0);
        $cat = trim($_POST['categoria'] ?? 'Otros');
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $freq = $_POST['frecuencia'] ?? 'unico';
        if (!in_array($freq, $allowedFreq)) $freq = 'unico';
        $recurrente = $freq !== 'unico' ? 1 : 0;

        if (!$desc || $monto <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Descripción y monto son requeridos']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO gastos (descripcion, monto, categoria, fecha, recurrente, frecuencia, usuario_id) VALUES (:d, :m, :c, :f, :r, :fr, :u)");
        $stmt->execute(['d'=>$desc, 'm'=>$monto, 'c'=>$cat, 'f'=>$fecha, 'r'=>$recurrente, 'fr'=>$freq, 'u'=>$uid]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;

    case 'delete_gasto':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); exit; }
        $pdo->prepare("DELETE FROM gastos WHERE id = :id")->execute(['id'=>$id]);
        echo json_encode(['ok' => true]);
        exit;

    // ===== INGRESO =====
    case 'ingreso':
        $desc = trim($_POST['descripcion'] ?? '');
        $monto = (float)($_POST['monto'] ?? 0);
        $metodo = trim($_POST['metodo_pago'] ?? 'transferencia');
        if (!in_array($metodo, $allowedMetodos)) $metodo = 'otro';
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $clienteId = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;

        if (!$desc || $monto <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Descripción y monto son requeridos']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO ingresos (descripcion, monto, metodo_pago, fecha, cliente_id, usuario_id) VALUES (:d, :m, :mp, :f, :ci, :u)");
        $stmt->execute(['d'=>$desc, 'm'=>$monto, 'mp'=>$metodo, 'f'=>$fecha, 'ci'=>$clienteId, 'u'=>$uid]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;

    case 'delete_ingreso':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); exit; }
        $pdo->prepare("DELETE FROM ingresos WHERE id = :id")->execute(['id'=>$id]);
        echo json_encode(['ok' => true]);
        exit;

    // ===== CATEGORÍAS =====
    case 'create_categoria':
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo = $_POST['tipo'] ?? 'gasto';
        if (!in_array($tipo, ['gasto','ingreso','ambos'])) $tipo = 'gasto';
        $color = trim($_POST['color'] ?? '#7c3aed');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#7c3aed';

        if (!$nombre) { echo json_encode(['ok'=>false,'error'=>'Nombre requerido']); exit; }

        $exists = $pdo->prepare("SELECT COUNT(*) FROM categorias_financieras WHERE nombre = :n AND activo = 1");
        $exists->execute(['n'=>$nombre]);
        if ((int)$exists->fetchColumn() > 0) { echo json_encode(['ok'=>false,'error'=>'Ya existe']); exit; }

        $stmt = $pdo->prepare("INSERT INTO categorias_financieras (nombre, tipo, color, usuario_id) VALUES (:n, :t, :c, :u)");
        $stmt->execute(['n'=>$nombre, 't'=>$tipo, 'c'=>$color, 'u'=>$uid]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;

    case 'delete_categoria':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); exit; }
        $pdo->prepare("UPDATE categorias_financieras SET activo = 0 WHERE id = :id")->execute(['id'=>$id]);
        echo json_encode(['ok' => true]);
        exit;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
