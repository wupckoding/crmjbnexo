<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'No auth']); exit; }

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $modulo = $_GET['modulo'] ?? '';
        $where = '1=1';
        $params = [];
        if ($modulo) { $where .= ' AND a.modulo = :m'; $params['m'] = $modulo; }

        $stmt = $pdo->prepare("SELECT a.*, u.nombre as usuario_nombre FROM actividad_log a LEFT JOIN usuarios u ON a.usuario_id = u.id WHERE $where ORDER BY a.creado_en DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $total = $pdo->prepare("SELECT COUNT(*) FROM actividad_log a WHERE $where");
        $total->execute($params);

        echo json_encode(['ok'=>true, 'logs'=>$logs, 'total'=>(int)$total->fetchColumn(), 'page'=>$page]);
        break;

    case 'stats':
        // Activity stats for dashboard
        $hoy = $pdo->query("SELECT COUNT(*) FROM actividad_log WHERE DATE(creado_en) = CURDATE()")->fetchColumn();
        $semana = $pdo->query("SELECT COUNT(*) FROM actividad_log WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $topUsers = $pdo->query("SELECT u.nombre, COUNT(*) as total FROM actividad_log a JOIN usuarios u ON a.usuario_id = u.id WHERE a.creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY a.usuario_id ORDER BY total DESC LIMIT 5")->fetchAll();
        $byModule = $pdo->query("SELECT modulo, COUNT(*) as total FROM actividad_log WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY modulo ORDER BY total DESC")->fetchAll();
        echo json_encode(['ok'=>true, 'hoy'=>(int)$hoy, 'semana'=>(int)$semana, 'top_users'=>$topUsers, 'by_module'=>$byModule]);
        break;

    default:
        echo json_encode(['error'=>'Acción inválida']);
}
