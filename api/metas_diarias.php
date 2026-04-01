<?php
require_once '../includes/auth_check.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

switch ($action) {

    case 'get_today':
        $targetUid = $isAdmin ? (int)($_GET['usuario_id'] ?? $userId) : $userId;
        $stmt = $pdo->prepare("SELECT id, titulo, icono, meta_cantidad, progreso, fecha FROM metas_diarias WHERE usuario_id = :uid AND fecha = CURDATE() ORDER BY id");
        $stmt->execute(['uid' => $targetUid]);
        $metas = $stmt->fetchAll();

        if (empty($metas)) {
            // Check if there's a previous day to copy from
            $stmtPrev = $pdo->prepare("SELECT titulo, icono, meta_cantidad FROM metas_diarias WHERE usuario_id = :uid AND fecha < CURDATE() ORDER BY fecha DESC LIMIT 10");
            $stmtPrev->execute(['uid' => $targetUid]);
            $prevMetas = $stmtPrev->fetchAll();

            // Deduplicate by title
            $seen = [];
            $defaults = [];
            foreach ($prevMetas as $pm) {
                if (!isset($seen[$pm['titulo']])) {
                    $seen[$pm['titulo']] = true;
                    $defaults[] = $pm;
                }
            }

            if (empty($defaults)) {
                $defaults = [
                    ['titulo' => 'Llamadas', 'icono' => 'phone', 'meta_cantidad' => 25],
                    ['titulo' => 'Emails enviados', 'icono' => 'email', 'meta_cantidad' => 10],
                    ['titulo' => 'Propuestas', 'icono' => 'file', 'meta_cantidad' => 5],
                    ['titulo' => 'Seguimientos', 'icono' => 'refresh', 'meta_cantidad' => 15],
                ];
            }

            $stmtIns = $pdo->prepare("INSERT IGNORE INTO metas_diarias (usuario_id, titulo, icono, meta_cantidad, progreso, fecha) VALUES (:uid, :titulo, :icono, :meta, 0, CURDATE())");
            foreach ($defaults as $d) {
                $stmtIns->execute([
                    'uid' => $targetUid,
                    'titulo' => $d['titulo'],
                    'icono' => $d['icono'],
                    'meta' => $d['meta_cantidad']
                ]);
            }

            $stmt->execute(['uid' => $targetUid]);
            $metas = $stmt->fetchAll();
        }

        echo json_encode(['ok' => true, 'metas' => $metas]);
        break;

    case 'update_progress':
        $id = (int)($_POST['id'] ?? 0);
        $change = (int)($_POST['change'] ?? 0);

        if ($change !== 1 && $change !== -1) {
            echo json_encode(['ok' => false, 'error' => 'Cambio inválido']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM metas_diarias WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $meta = $stmt->fetch();

        if (!$meta) {
            echo json_encode(['ok' => false, 'error' => 'Meta no encontrada']);
            exit;
        }

        if (!$isAdmin && (int)$meta['usuario_id'] !== $userId) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']);
            exit;
        }

        $newProgress = max(0, (int)$meta['progreso'] + $change);
        $pdo->prepare("UPDATE metas_diarias SET progreso = :p WHERE id = :id")
            ->execute(['p' => $newProgress, 'id' => $id]);

        echo json_encode([
            'ok' => true,
            'progreso' => $newProgress,
            'completado' => $newProgress >= (int)$meta['meta_cantidad']
        ]);
        break;

    case 'set_goals':
        if (!$isAdmin) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']);
            exit;
        }

        $targetUid = (int)($_POST['usuario_id'] ?? 0);
        $goals = json_decode($_POST['goals'] ?? '[]', true);

        if (!$targetUid || empty($goals)) {
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        // Delete today's goals for this user and recreate
        $pdo->prepare("DELETE FROM metas_diarias WHERE usuario_id = :uid AND fecha = CURDATE()")
            ->execute(['uid' => $targetUid]);

        $stmtIns = $pdo->prepare("INSERT INTO metas_diarias (usuario_id, titulo, icono, meta_cantidad, progreso, fecha, creado_por) VALUES (:uid, :titulo, :icono, :meta, 0, CURDATE(), :admin)");
        foreach ($goals as $g) {
            $titulo = trim($g['titulo'] ?? '');
            if ($titulo === '') continue;
            $stmtIns->execute([
                'uid' => $targetUid,
                'titulo' => $titulo,
                'icono' => $g['icono'] ?? 'check',
                'meta' => max(1, (int)($g['meta'] ?? 1)),
                'admin' => $userId
            ]);
        }

        echo json_encode(['ok' => true]);
        break;

    case 'add_goal':
        $targetUid = $isAdmin ? (int)($_POST['usuario_id'] ?? $userId) : $userId;
        $titulo = trim($_POST['titulo'] ?? '');
        $icono = trim($_POST['icono'] ?? 'check');
        $meta = max(1, (int)($_POST['meta'] ?? 1));

        if ($titulo === '') {
            echo json_encode(['ok' => false, 'error' => 'Título requerido']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO metas_diarias (usuario_id, titulo, icono, meta_cantidad, progreso, fecha) VALUES (:uid, :titulo, :icono, :meta, 0, CURDATE())");
        $stmt->execute(['uid' => $targetUid, 'titulo' => $titulo, 'icono' => $icono, 'meta' => $meta]);

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'delete_goal':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT usuario_id FROM metas_diarias WHERE id = :id AND fecha = CURDATE()");
        $stmt->execute(['id' => $id]);
        $meta = $stmt->fetch();

        if (!$meta) {
            echo json_encode(['ok' => false, 'error' => 'Meta no encontrada']);
            exit;
        }

        if (!$isAdmin && (int)$meta['usuario_id'] !== $userId) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']);
            exit;
        }

        $pdo->prepare("DELETE FROM metas_diarias WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'update_meta':
        $id = (int)($_POST['id'] ?? 0);
        $newMeta = max(1, (int)($_POST['meta_cantidad'] ?? 1));

        $stmt = $pdo->prepare("SELECT usuario_id FROM metas_diarias WHERE id = :id AND fecha = CURDATE()");
        $stmt->execute(['id' => $id]);
        $meta = $stmt->fetch();

        if (!$meta || (!$isAdmin && (int)$meta['usuario_id'] !== $userId)) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado']);
            exit;
        }

        $pdo->prepare("UPDATE metas_diarias SET meta_cantidad = :m WHERE id = :id")
            ->execute(['m' => $newMeta, 'id' => $id]);

        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
