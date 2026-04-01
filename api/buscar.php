<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'No auth']); exit; }

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['results' => []]); exit; }

$like = "%$q%";
$results = [];

// Search clientes
$stmt = $pdo->prepare("SELECT id, nombre, email, empresa, 'cliente' as tipo FROM clientes WHERE nombre LIKE :q OR email LIKE :q OR empresa LIKE :q LIMIT 5");
$stmt->execute(['q' => $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['tipo'=>'cliente','icon'=>'👤','titulo'=>$r['nombre'],'sub'=>$r['empresa'] ?: $r['email'],'url'=>'cliente_detalle.php?id='.$r['id']];
}

// Search facturas
$stmt = $pdo->prepare("SELECT f.id, f.numero, f.total, f.estado, c.nombre as cliente FROM facturas f LEFT JOIN clientes c ON f.cliente_id=c.id WHERE f.numero LIKE :q OR c.nombre LIKE :q LIMIT 5");
$stmt->execute(['q' => $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['tipo'=>'factura','icon'=>'📄','titulo'=>$r['numero'].' - $'.number_format($r['total'],0),'sub'=>$r['cliente'].' · '.ucfirst($r['estado']),'url'=>'factura_detalle.php?id='.$r['id']];
}

// Search servicios
$stmt = $pdo->prepare("SELECT id, nombre, precio FROM servicios WHERE nombre LIKE :q LIMIT 3");
$stmt->execute(['q' => $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['tipo'=>'servicio','icon'=>'⚗️','titulo'=>$r['nombre'],'sub'=>'$'.number_format($r['precio'],0),'url'=>'servicios.php'];
}

// Search usuarios
$stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE nombre LIKE :q OR email LIKE :q LIMIT 3");
$stmt->execute(['q' => $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['tipo'=>'usuario','icon'=>'🧑','titulo'=>$r['nombre'],'sub'=>$r['email'].' · '.ucfirst($r['rol']),'url'=>'usuarios.php'];
}

// Search avisos
$stmt = $pdo->prepare("SELECT id, titulo, prioridad FROM avisos WHERE titulo LIKE :q OR contenido LIKE :q LIMIT 3");
$stmt->execute(['q' => $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['tipo'=>'aviso','icon'=>'📢','titulo'=>$r['titulo'],'sub'=>ucfirst($r['prioridad']),'url'=>'avisos.php'];
}

echo json_encode(['results' => $results]);
