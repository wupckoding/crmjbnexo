<?php
/**
 * i18n helper – returns translated string
 */
$_LANG = [];
function __($key, $fallback = null) {
    global $_LANG;
    return $_LANG[$key] ?? $fallback ?? $key;
}

/**
 * Activity Logger - Include in any API/page to log actions
 * Usage: log_activity($pdo, 'crear', 'clientes', 'Creó cliente: Empresa X');
 */
function log_activity($pdo, $accion, $modulo, $detalle = '') {
    $userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
    if (!$userId) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("INSERT INTO actividad_log (usuario_id, accion, modulo, detalle, ip) VALUES (:u, :a, :m, :d, :ip)");
    $stmt->execute(['u' => $userId, 'a' => $accion, 'm' => $modulo, 'd' => $detalle, 'ip' => $ip]);
}

/**
 * Send notification to a user
 * Usage: send_notification($pdo, $userId, 'info', 'Nuevo cliente asignado', 'Te han asignado...', 'clientes.php');
 */
function send_notification($pdo, $userId, $tipo, $titulo, $mensaje = '', $enlace = '') {
    $stmt = $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace) VALUES (:u, :t, :ti, :m, :e)");
    $stmt->execute(['u' => $userId, 't' => $tipo, 'ti' => $titulo, 'm' => $mensaje, 'e' => $enlace]);
}

/**
 * Send notification to all admins
 */
function notify_admins($pdo, $tipo, $titulo, $mensaje = '', $enlace = '') {
    $admins = $pdo->query("SELECT id FROM usuarios WHERE rol = 'admin' AND activo = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $adminId) {
        send_notification($pdo, $adminId, $tipo, $titulo, $mensaje, $enlace);
    }
}

/**
 * Check module permission
 */
function check_permission($pdo, $modulo, $permiso = 'puede_ver') {
    $role = $_SESSION['user_role'] ?? $_SESSION['usuario_rol'] ?? 'vendedor';
    if ($role === 'admin') return true;
    $stmt = $pdo->prepare("SELECT $permiso FROM permisos WHERE rol = :r AND modulo = :m");
    $stmt->execute(['r' => $role, 'm' => $modulo]);
    return (bool) $stmt->fetchColumn();
}
