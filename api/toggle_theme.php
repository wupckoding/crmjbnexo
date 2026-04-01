<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(401); exit; }
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$tema = ($data['tema'] ?? 'dark') === 'light' ? 'light' : 'dark';

$stmt = $pdo->prepare('UPDATE configuraciones SET tema = :tema WHERE usuario_id = :uid');
$stmt->execute(['tema' => $tema, 'uid' => $_SESSION['usuario_id']]);
echo json_encode(['ok' => true]);
