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

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $numero = trim($_POST['numero'] ?? '');
        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        $fecha_emision = $_POST['fecha_emision'] ?? date('Y-m-d');
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
        $notas = trim($_POST['notas'] ?? '');
        $items = $_POST['items'] ?? [];

        if (!$cliente_id || !$numero) {
            header('Location: ../facturas.php?error=missing');
            exit;
        }

        $subtotal = 0;
        foreach ($items as $it) {
            $subtotal += ((float)($it['qty'] ?? 0)) * ((float)($it['price'] ?? 0));
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO facturas (numero, cliente_id, usuario_id, fecha_emision, fecha_vencimiento, subtotal, impuesto_monto, total, estado, notas) VALUES (:n, :c, :u, :fe, :fv, :st, 0, :t, 'borrador', :no)");
            $stmt->execute([
                'n'=>$numero, 'c'=>$cliente_id, 'u'=>$_SESSION['user_id'],
                'fe'=>$fecha_emision, 'fv'=>$fecha_vencimiento ?: null,
                'st'=>$subtotal, 't'=>$subtotal, 'no'=>$notas
            ]);
            $factura_id = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO factura_items (factura_id, servicio_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (:f, :s, :d, :q, :p, :t)");
            foreach ($items as $it) {
                $desc = trim($it['desc'] ?? '');
                $qty = (int)($it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                $servicio_id = !empty($it['servicio_id']) ? (int)$it['servicio_id'] : null;
                if (!$desc || $price <= 0) continue;
                $stmtItem->execute(['f'=>$factura_id, 's'=>$servicio_id, 'd'=>$desc, 'q'=>$qty, 'p'=>$price, 't'=>$qty * $price]);
            }
            $pdo->commit();
            log_activity($pdo, 'crear', 'facturas', 'Creó factura: ' . $numero);
            header('Location: ../facturas.php?msg=created');
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: ../facturas.php?error=db');
        }
        exit;

    case 'update_status':
        $id = (int)($_POST['id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');
        $valid = ['borrador','enviada','pagada','vencida','cancelada'];
        if (!$id || !in_array($estado, $valid)) {
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }
        $pdo->prepare("UPDATE facturas SET estado = :e WHERE id = :id")->execute(['e'=>$estado, 'id'=>$id]);
        log_activity($pdo, 'editar', 'facturas', 'Cambió estado factura #' . $id . ' a ' . $estado);
        
        // If marked as paid, create income entry
        if ($estado === 'pagada') {
            $stmtF = $pdo->prepare("SELECT * FROM facturas WHERE id = :id");
            $stmtF->execute(['id'=>$id]);
            $f = $stmtF->fetch(PDO::FETCH_ASSOC);
            if ($f) {
                $pdo->prepare("INSERT INTO ingresos (factura_id, monto, fecha, descripcion, metodo_pago, usuario_id) VALUES (:f, :m, CURDATE(), :d, 'transferencia', :u)")
                    ->execute(['f'=>$id, 'm'=>$f['total'], 'd'=>'Pago factura '.$f['numero'], 'u'=>$_SESSION['user_id']]);
            }
        }
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error'=>'ID requerido']); exit; }
        // Eliminar items primero, luego la factura
        $pdo->prepare("DELETE FROM factura_items WHERE factura_id = :id")->execute(['id'=>$id]);
        $pdo->prepare("DELETE FROM ingresos WHERE factura_id = :id")->execute(['id'=>$id]);
        $pdo->prepare("DELETE FROM facturas WHERE id = :id")->execute(['id'=>$id]);
        log_activity($pdo, 'eliminar', 'facturas', 'Eliminó factura #' . $id);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
