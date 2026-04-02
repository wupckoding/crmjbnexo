<?php
/**
 * CRM JBNEXO - Diagnóstico completo
 * Acessa: jbnexocrm.cloud/check.php
 * DELETAR depois que funcionar!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace;'>";
echo "=== CRM JBNEXO - DIAGNÓSTICO ===\n\n";

// 1. PHP version
echo "PHP: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "Host: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "\n\n";

// 2. Database connection
echo "--- DATABASE ---\n";
try {
    require_once 'config/database.php';
    echo "✅ Conexão OK (user=" . DB_USER . ", db=" . DB_NAME . ")\n";
} catch (Exception $e) {
    echo "❌ ERRO DB: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit;
}

// 3. Check critical tables + columns
echo "\n--- COLUNAS CRÍTICAS ---\n";
$checks = [
    ['usuarios', 'meta_mensual'],
    ['usuarios', 'onboarding_completado'],
    ['usuarios', 'comision_porcentaje'],
    ['eventos', 'asignado_a'],
    ['eventos', 'usuario_id'],
    ['eventos', 'fecha_inicio'],
    ['clientes', 'asignado_a'],
    ['clientes', 'estado'],
    ['facturas', 'usuario_id'],
    ['facturas', 'estado'],
    ['ingresos', 'usuario_id'],
    ['ingresos', 'monto'],
    ['ingresos', 'fecha'],
    ['gastos', 'monto'],
    ['gastos', 'fecha'],
    ['tareas', 'usuario_id'],
    ['tareas', 'estado'],
    ['metas_diarias', 'usuario_id'],
    ['metas_diarias', 'titulo'],
    ['metas_diarias', 'icono'],
    ['metas_diarias', 'meta_cantidad'],
    ['metas_diarias', 'progreso'],
    ['metas_diarias', 'fecha'],
];
$allOk = true;
foreach ($checks as [$table, $col]) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $col]);
    $exists = (int)$stmt->fetchColumn();
    if ($exists) {
        echo "✅ {$table}.{$col}\n";
    } else {
        echo "❌ FALTA: {$table}.{$col}\n";
        $allOk = false;
    }
}

// 4. Check critical files
echo "\n--- ARQUIVOS ---\n";
$files = [
    'includes/auth_check.php',
    'includes/header.php',
    'includes/sidebar.php',
    'includes/topbar.php',
    'includes/footer.php',
    'includes/helpers.php',
    'includes/onboarding.php',
    'config/database.php',
    'config/lang/es.php',
    'config/lang/pt.php',
    'config/lang/en.php',
    'assets/css/custom.css',
    'assets/js/sounds.js',
    '.htaccess',
    '.user.ini',
];
foreach ($files as $f) {
    echo (file_exists(__DIR__ . '/' . $f) ? "✅" : "❌ FALTA") . " {$f}\n";
}

// 5. Try dashboard queries one by one
echo "\n--- QUERIES DO DASHBOARD ---\n";
$queries = [
    ['SELECT nombre, rol, meta_mensual, onboarding_completado, creado_en FROM usuarios LIMIT 1', 'User info'],
    ['SELECT COUNT(*) FROM clientes', 'Count clientes'],
    ['SELECT COUNT(*) FROM facturas', 'Count facturas'],
    ['SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE MONTH(fecha)=MONTH(NOW())', 'Ingresos mes'],
    ['SELECT COALESCE(SUM(monto),0) FROM gastos WHERE MONTH(fecha)=MONTH(NOW())', 'Gastos mes'],
    ['SELECT COUNT(*) FROM eventos WHERE usuario_id = 1 OR asignado_a = 1', 'Eventos (asignado_a)'],
    ['SELECT COUNT(*) FROM tareas WHERE usuario_id = 1', 'Tareas'],
    ['SELECT COUNT(*) FROM clientes WHERE asignado_a = 1', 'Clientes asignados'],
    ['SELECT comision_porcentaje FROM usuarios LIMIT 1', 'Comision'],
    ['SELECT id, titulo, icono, meta_cantidad, progreso FROM metas_diarias LIMIT 1', 'Metas diarias'],
    ["SELECT f.numero, f.total, f.estado, f.fecha_emision, c.nombre as cn FROM facturas f JOIN clientes c ON f.cliente_id = c.id LIMIT 1", 'Facturas JOIN clientes'],
    ['SELECT id, nombre, email, rol, avatar, ultimo_acceso FROM usuarios WHERE activo = 1', 'Team users'],
    ['SELECT estado, COUNT(*) as total FROM clientes GROUP BY estado', 'Pipeline stats'],
];
foreach ($queries as [$sql, $label]) {
    try {
        $pdo->query($sql);
        echo "✅ {$label}\n";
    } catch (Exception $e) {
        echo "❌ {$label}: " . $e->getMessage() . "\n";
        $allOk = false;
    }
}

// 6. Session test
echo "\n--- SESSION ---\n";
session_start();
echo "Session OK. ID: " . session_id() . "\n";

// 7. Check strftime (deprecated PHP 8.1+)
echo "\n--- FUNÇÕES ---\n";
echo "strftime() existe: " . (function_exists('strftime') ? 'SIM' : 'NÃO') . "\n";
echo "IntlDateFormatter: " . (class_exists('IntlDateFormatter') ? 'SIM' : 'NÃO') . "\n";

echo "\n=== " . ($allOk ? "✅ TUDO OK!" : "❌ TEM PROBLEMAS ACIMA") . " ===\n";
echo "</pre>";
