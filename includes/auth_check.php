<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Aliases for compatibility
$_SESSION['user_id'] = $_SESSION['usuario_id'];
$_SESSION['user_name'] = $_SESSION['usuario_nombre'] ?? '';
$_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

// Load user avatar
if (!isset($_SESSION['usuario_avatar'])) {
    $stmtAv = $pdo->prepare('SELECT avatar FROM usuarios WHERE id = :id');
    $stmtAv->execute(['id' => $_SESSION['usuario_id']]);
    $_SESSION['usuario_avatar'] = $stmtAv->fetchColumn() ?: '';
}

// Get user config
$stmtConf = $pdo->prepare('SELECT tema FROM configuraciones WHERE usuario_id = :uid');
$stmtConf->execute(['uid' => $_SESSION['usuario_id']]);
$config = $stmtConf->fetch();
$tema = $config['tema'] ?? 'dark';

// Load permissions for current user role
$_permisos = [];
$stmtP = $pdo->prepare("SELECT modulo, puede_ver, puede_crear, puede_editar, puede_eliminar FROM permisos WHERE rol = :r");
$stmtP->execute(['r' => $_SESSION['user_role']]);
foreach ($stmtP->fetchAll() as $p) {
    $_permisos[$p['modulo']] = $p;
}

// Load global config (branding)
$_globalConfig = [];
$_gcRows = $pdo->query("SELECT clave, valor FROM configuracion_global")->fetchAll();
foreach ($_gcRows as $_gcR) $_globalConfig[$_gcR['clave']] = $_gcR['valor'];
$_empresaNombre = $_globalConfig['empresa_nombre'] ?? 'NEXO';
$_empresaLogo = $_globalConfig['logo_url'] ?? '';
