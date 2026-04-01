<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];

switch ($action) {
    case 'complete_onboarding':
        $pdo->prepare("UPDATE usuarios SET onboarding_completado = 1 WHERE id = :id")->execute(['id' => $uid]);
        echo json_encode(['ok' => true]);
        break;

    case 'reset_onboarding':
        $pdo->prepare("UPDATE usuarios SET onboarding_completado = 0 WHERE id = :id")->execute(['id' => $uid]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
