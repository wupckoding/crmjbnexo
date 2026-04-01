<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
}
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'No auth']); exit; }

$uid = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = :u ORDER BY creado_en DESC LIMIT 30");
        $stmt->execute(['u' => $uid]);
        $notifs = $stmt->fetchAll();
        $unread = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :u AND leida = 0");
        $unread->execute(['u' => $uid]);
        echo json_encode(['ok'=>true, 'notificaciones'=>$notifs, 'no_leidas'=>(int)$unread->fetchColumn()]);
        break;

    case 'read':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = :id AND usuario_id = :u")->execute(['id'=>$id, 'u'=>$uid]);
        }
        echo json_encode(['ok'=>true]);
        break;

    case 'read_all':
        $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = :u")->execute(['u'=>$uid]);
        echo json_encode(['ok'=>true]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM notificaciones WHERE id = :id AND usuario_id = :u")->execute(['id'=>$id, 'u'=>$uid]);
        }
        echo json_encode(['ok'=>true]);
        break;

    default:
        echo json_encode(['error'=>'Acción inválida']);
}
