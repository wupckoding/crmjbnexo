<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/auth_check.php';
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$userId = $_SESSION['user_id'];

// ---- User info for personalized dashboard ----
$stmtUser = $pdo->prepare("SELECT nombre, rol, meta_mensual, onboarding_completado, creado_en FROM usuarios WHERE id = :id");
$stmtUser->execute(['id' => $userId]);
$currentUser = $stmtUser->fetch();
$userNombre = $currentUser['nombre'] ?? 'Usuario';
$userMeta = (float)($currentUser['meta_mensual'] ?? 0);
$userOnboarding = (int)($currentUser['onboarding_completado'] ?? 0);

// ---- STATS QUERIES (role-filtered) ----
if ($isAdmin) {
    $totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $clientesNuevos = $pdo->query("SELECT COUNT(*) FROM clientes WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 90 DAY)")->fetchColumn();
    $totalFacturas = $pdo->query("SELECT COUNT(*) FROM facturas")->fetchColumn();
    $facturasPendientes = $pdo->query("SELECT COUNT(*) FROM facturas WHERE estado IN ('enviada','borrador')")->fetchColumn();
    $ingresosMes = $pdo->query("SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")->fetchColumn();
    $gastosMes = $pdo->query("SELECT COALESCE(SUM(monto),0) FROM gastos WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")->fetchColumn();
    $porRecibir = $pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado IN ('enviada','borrador')")->fetchColumn();
} else {
    // Employee: only their assigned clients
    $stmtTC = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE asignado_a = :uid");
    $stmtTC->execute(['uid' => $userId]);
    $totalClientes = (int)$stmtTC->fetchColumn();

    $stmtCN = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE asignado_a = :uid AND creado_en >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $stmtCN->execute(['uid' => $userId]);
    $clientesNuevos = (int)$stmtCN->fetchColumn();

    $stmtTF = $pdo->prepare("SELECT COUNT(*) FROM facturas WHERE usuario_id = :uid");
    $stmtTF->execute(['uid' => $userId]);
    $totalFacturas = (int)$stmtTF->fetchColumn();

    $stmtFP = $pdo->prepare("SELECT COUNT(*) FROM facturas WHERE usuario_id = :uid AND estado IN ('enviada','borrador')");
    $stmtFP->execute(['uid' => $userId]);
    $facturasPendientes = (int)$stmtFP->fetchColumn();

    // Revenue this employee brought in
    $stmtIM = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE usuario_id = :uid AND MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())");
    $stmtIM->execute(['uid' => $userId]);
    $ingresosMes = (float)$stmtIM->fetchColumn();

    $gastosMes = 0; // Employees don't see expenses
    $porRecibir = 0;
}

// Revenue por mes (últimos 6 meses)
if ($isAdmin) {
    $revenueData = $pdo->query("SELECT DATE_FORMAT(fecha,'%Y-%m') as mes, SUM(monto) as total FROM ingresos WHERE fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes")->fetchAll();
    $expenseData = $pdo->query("SELECT DATE_FORMAT(fecha,'%Y-%m') as mes, SUM(monto) as total FROM gastos WHERE fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes")->fetchAll();
} else {
    $stmtRev = $pdo->prepare("SELECT DATE_FORMAT(fecha,'%Y-%m') as mes, SUM(monto) as total FROM ingresos WHERE usuario_id = :uid AND fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes");
    $stmtRev->execute(['uid' => $userId]);
    $revenueData = $stmtRev->fetchAll();
    $expenseData = [];
}

// Últimos clientes
if ($isAdmin) {
    $latestClientes = $pdo->query("SELECT id, nombre, email, empresa, estado, creado_en FROM clientes ORDER BY creado_en DESC LIMIT 5")->fetchAll();
} else {
    $stmtLC = $pdo->prepare("SELECT id, nombre, email, empresa, estado, creado_en FROM clientes WHERE asignado_a = :uid ORDER BY creado_en DESC LIMIT 5");
    $stmtLC->execute(['uid' => $userId]);
    $latestClientes = $stmtLC->fetchAll();
}

// Últimas facturas
if ($isAdmin) {
    $latestFacturas = $pdo->query("SELECT f.numero, f.total, f.estado, f.fecha_emision, c.nombre as cliente_nombre FROM facturas f JOIN clientes c ON f.cliente_id = c.id ORDER BY f.creado_en DESC LIMIT 5")->fetchAll();
} else {
    $stmtLF = $pdo->prepare("SELECT f.numero, f.total, f.estado, f.fecha_emision, c.nombre as cliente_nombre FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.usuario_id = :uid ORDER BY f.creado_en DESC LIMIT 5");
    $stmtLF->execute(['uid' => $userId]);
    $latestFacturas = $stmtLF->fetchAll();
}

// Pipeline breakdown
if ($isAdmin) {
    $pipelineStats = $pdo->query("SELECT estado, COUNT(*) as total FROM clientes GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $stmtPS = $pdo->prepare("SELECT estado, COUNT(*) as total FROM clientes WHERE asignado_a = :uid GROUP BY estado");
    $stmtPS->execute(['uid' => $userId]);
    $pipelineStats = $stmtPS->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Usuarios del equipo (admin only full list)
$teamUsers = $pdo->query("SELECT id, nombre, email, rol, avatar, ultimo_acceso FROM usuarios WHERE activo = 1 ORDER BY ultimo_acceso DESC")->fetchAll();

// Employee: meta progress
$metaProgress = ($userMeta > 0) ? min(100, round(($ingresosMes / $userMeta) * 100)) : 0;

// Employee: upcoming events count
$stmtEvToday = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE (usuario_id = :uid OR asignado_a = :uid2) AND DATE(fecha_inicio) = CURDATE()");
$stmtEvToday->execute(['uid' => $userId, 'uid2' => $userId]);
$misEventosHoy = (int)$stmtEvToday->fetchColumn();

// Employee: tasks pending
$stmtMyTasks = $pdo->prepare("SELECT COUNT(*) FROM tareas WHERE usuario_id = :uid AND estado IN ('pendiente','en_progreso')");
$stmtMyTasks->execute(['uid' => $userId]);
$misTareasPend = (int)$stmtMyTasks->fetchColumn();

// Employee: extended metrics
if (!$isAdmin) {
    // Clients won (all time)
    $stmtWon = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE asignado_a = :uid AND estado = 'ganado'");
    $stmtWon->execute(['uid' => $userId]);
    $clientesGanados = (int)$stmtWon->fetchColumn();

    // Clients lost
    $stmtLost = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE asignado_a = :uid AND estado = 'perdido'");
    $stmtLost->execute(['uid' => $userId]);
    $clientesPerdidos = (int)$stmtLost->fetchColumn();

    // Conversion rate
    $clientesConEstado = $clientesGanados + $clientesPerdidos;
    $tasaConversion = $clientesConEstado > 0 ? round(($clientesGanados / $clientesConEstado) * 100, 1) : 0;

    // Commission
    $stmtCom = $pdo->prepare("SELECT comision_porcentaje FROM usuarios WHERE id = :uid");
    $stmtCom->execute(['uid' => $userId]);
    $comisionPct = (float)($stmtCom->fetchColumn() ?: 20);
    $comisionMes = round($ingresosMes * ($comisionPct / 100), 2);

    // Daily goals for today
    $stmtMD = $pdo->prepare("SELECT id, titulo, icono, meta_cantidad, progreso FROM metas_diarias WHERE usuario_id = :uid AND fecha = CURDATE() ORDER BY id");
    $stmtMD->execute(['uid' => $userId]);
    $metasHoy = $stmtMD->fetchAll();

    // Auto-create if no goals exist for today
    if (empty($metasHoy)) {
        $stmtPrev = $pdo->prepare("SELECT titulo, icono, meta_cantidad FROM metas_diarias WHERE usuario_id = :uid AND fecha < CURDATE() ORDER BY fecha DESC LIMIT 10");
        $stmtPrev->execute(['uid' => $userId]);
        $prevMetas = $stmtPrev->fetchAll();
        $seen = [];
        $defaults = [];
        foreach ($prevMetas as $pm) {
            if (!isset($seen[$pm['titulo']])) { $seen[$pm['titulo']] = true; $defaults[] = $pm; }
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
            $stmtIns->execute(['uid' => $userId, 'titulo' => $d['titulo'], 'icono' => $d['icono'], 'meta' => $d['meta_cantidad']]);
        }
        $stmtMD->execute(['uid' => $userId]);
        $metasHoy = $stmtMD->fetchAll();
    }

    $metasCompletadas = 0;
    foreach ($metasHoy as $m) { if ($m['progreso'] >= $m['meta_cantidad']) $metasCompletadas++; }

    // Revenue this week
    $stmtRW = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE usuario_id = :uid AND YEARWEEK(fecha,1)=YEARWEEK(NOW(),1)");
    $stmtRW->execute(['uid' => $userId]);
    $ingresosSemana = (float)$stmtRW->fetchColumn();

    // Clients won this month
    $stmtWM = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE asignado_a = :uid AND estado = 'ganado' AND MONTH(actualizado_en)=MONTH(NOW()) AND YEAR(actualizado_en)=YEAR(NOW())");
    $stmtWM->execute(['uid' => $userId]);
    $ganadosMes = (int)$stmtWM->fetchColumn();
}

// Prepare chart data
$meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$revenueByMonth = array_fill(0, 12, 0);
$expenseByMonth = array_fill(0, 12, 0);
foreach ($revenueData as $r) { $m = (int)substr($r['mes'], 5, 2) - 1; $revenueByMonth[$m] = (float)$r['total']; }
foreach ($expenseData as $e) { $m = (int)substr($e['mes'], 5, 2) - 1; $expenseByMonth[$m] = (float)$e['total']; }

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main content -->
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>

<div class="p-4 sm:p-6 space-y-6">

    <!-- Welcome header (employee only) -->
    <?php if (!$isAdmin): ?>
    <div class="dark:bg-gradient-to-br dark:from-nexo-900/60 dark:via-dark-800 dark:to-dark-800 bg-gradient-to-br from-nexo-50 via-white to-white rounded-2xl border dark:border-nexo-500/20 border-nexo-200 p-5 sm:p-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-nexo-500/5 rounded-full -translate-y-32 translate-x-32 blur-2xl"></div>
        <div class="relative">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold dark:text-white text-gray-900"><?php echo __('dash_hola'); ?>, <?php echo htmlspecialchars(explode(' ', $userNombre)[0]); ?>! 👋</h2>
                    <p class="text-sm dark:text-white/50 text-gray-500 mt-0.5"><?php echo __('dash_resumen'); ?> — <?php echo strftime('%A %d de %B, %Y', strtotime('today')); ?></p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Conversion rate badge -->
                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl <?php echo $tasaConversion >= 50 ? 'dark:bg-emerald-500/10 bg-emerald-50 border dark:border-emerald-500/20 border-emerald-200' : ($tasaConversion >= 25 ? 'dark:bg-amber-500/10 bg-amber-50 border dark:border-amber-500/20 border-amber-200' : 'dark:bg-red-500/10 bg-red-50 border dark:border-red-500/20 border-red-200'); ?>">
                        <svg class="w-4 h-4 <?php echo $tasaConversion >= 50 ? 'text-emerald-400' : ($tasaConversion >= 25 ? 'text-amber-400' : 'text-red-400'); ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        <div>
                            <p class="text-[10px] uppercase font-medium dark:text-white/40 text-gray-400 leading-none">Conversión</p>
                            <p class="text-sm font-bold <?php echo $tasaConversion >= 50 ? 'text-emerald-400' : ($tasaConversion >= 25 ? 'text-amber-400' : 'text-red-400'); ?> leading-tight"><?php echo $tasaConversion; ?>%</p>
                        </div>
                    </div>
                    <!-- Quick stats -->
                    <div class="flex items-center gap-3">
                        <div class="text-center px-3 py-1.5 rounded-xl dark:bg-white/5 bg-gray-50">
                            <p class="text-[10px] dark:text-white/40 text-gray-400 uppercase font-medium">Eventos hoy</p>
                            <p class="text-lg font-bold dark:text-nexo-400 text-nexo-600"><?php echo $misEventosHoy; ?></p>
                        </div>
                        <div class="text-center px-3 py-1.5 rounded-xl dark:bg-white/5 bg-gray-50">
                            <p class="text-[10px] dark:text-white/40 text-gray-400 uppercase font-medium">Tareas pend.</p>
                            <p class="text-lg font-bold dark:text-amber-400 text-amber-600"><?php echo $misTareasPend; ?></p>
                        </div>
                        <div class="text-center px-3 py-1.5 rounded-xl dark:bg-white/5 bg-gray-50">
                            <p class="text-[10px] dark:text-white/40 text-gray-400 uppercase font-medium">Ganados mes</p>
                            <p class="text-lg font-bold dark:text-emerald-400 text-emerald-600"><?php echo $ganadosMes; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($userMeta > 0): ?>
            <div class="mt-4 pt-4 border-t dark:border-white/10 border-nexo-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium dark:text-white/50 text-gray-500">Meta Mensual: $<?php echo number_format($userMeta, 0, ',', '.'); ?></span>
                    <span class="text-xs font-bold <?php echo $metaProgress >= 100 ? 'text-emerald-400' : ($metaProgress >= 60 ? 'dark:text-nexo-400 text-nexo-600' : 'text-amber-400'); ?>"><?php echo $metaProgress; ?>%</span>
                </div>
                <div class="h-2.5 rounded-full dark:bg-white/10 bg-nexo-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 <?php echo $metaProgress >= 100 ? 'bg-emerald-500' : ($metaProgress >= 60 ? 'bg-nexo-500' : 'bg-amber-500'); ?>" style="width: <?php echo $metaProgress; ?>%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <span class="text-[10px] dark:text-white/30 text-gray-400">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?> generado</span>
                    <span class="text-[10px] dark:text-white/30 text-gray-400">$<?php echo number_format(max(0, $userMeta - $ingresosMes), 0, ',', '.'); ?> restante</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats cards row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Clientes -->
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm dark:text-white/50 text-gray-500"><?php echo $isAdmin ? __('cli_titulo') : __('dash_mis_clientes'); ?></span>
                <div class="w-10 h-10 rounded-xl bg-nexo-600/15 flex items-center justify-center">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold"><?php echo $totalClientes; ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs text-emerald-400 font-medium">+<?php echo $clientesNuevos; ?></span>
                <span class="text-xs dark:text-white/30 text-gray-400">Últimos 90 días</span>
            </div>
        </div>

        <!-- Facturas / Clientes Ganados -->
        <?php if ($isAdmin): ?>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm dark:text-white/50 text-gray-500"><?php echo __('fac_titulo'); ?></span>
                <div class="w-10 h-10 rounded-xl bg-blue-500/15 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold"><?php echo $totalFacturas; ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs text-amber-400 font-medium"><?php echo $facturasPendientes; ?> pendientes</span>
            </div>
        </div>
        <?php else: ?>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 <?php echo $tasaConversion >= 50 ? 'bg-emerald-500/5' : ($tasaConversion >= 25 ? 'bg-amber-500/5' : 'bg-red-500/5'); ?> rounded-full -translate-y-6 translate-x-6"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm dark:text-white/50 text-gray-500"><?php echo __('dash_tasa_conversion'); ?></span>
                <div class="w-10 h-10 rounded-xl <?php echo $tasaConversion >= 50 ? 'bg-emerald-500/15' : ($tasaConversion >= 25 ? 'bg-amber-500/15' : 'bg-red-500/15'); ?> flex items-center justify-center">
                    <svg class="w-5 h-5 <?php echo $tasaConversion >= 50 ? 'text-emerald-400' : ($tasaConversion >= 25 ? 'text-amber-400' : 'text-red-400'); ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold <?php echo $tasaConversion >= 50 ? 'dark:text-emerald-400 text-emerald-600' : ($tasaConversion >= 25 ? 'dark:text-amber-400 text-amber-600' : 'dark:text-red-400 text-red-600'); ?>"><?php echo $tasaConversion; ?>%</p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs font-medium text-emerald-400"><?php echo $clientesGanados; ?> ganados</span>
                <span class="text-xs dark:text-white/30 text-gray-400">/ <?php echo $clientesPerdidos; ?> perdidos</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ingresos del mes -->
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-full -translate-y-8 translate-x-8"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium dark:text-white/70 text-gray-600"><?php echo $isAdmin ? __('dash_ingresos_mes') : __('dash_mi_produccion'); ?></span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold dark:text-emerald-400 text-emerald-600">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs dark:text-white/50 text-gray-500"><?php echo date('F Y'); ?></span>
            </div>
        </div>

        <!-- Por recibir / Facturas -->
        <?php if ($isAdmin): ?>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 rounded-full -translate-y-8 translate-x-8"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium dark:text-white/70 text-gray-600"><?php echo __('dash_por_recibir'); ?></span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold dark:text-amber-400 text-amber-600">$<?php echo number_format($porRecibir, 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs font-medium dark:text-amber-400/70 text-amber-600/70"><?php echo $facturasPendientes; ?> facturas</span>
            </div>
        </div>
        <?php else: ?>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 rounded-full -translate-y-8 translate-x-8"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium dark:text-white/70 text-gray-600"><?php echo __('dash_mi_comision'); ?> (<?php echo (int)$comisionPct; ?>%)</span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold dark:text-amber-400 text-amber-600">$<?php echo number_format($comisionMes, 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs dark:text-white/50 text-gray-500">sobre $<?php echo number_format($ingresosMes, 0, ',', '.'); ?> producido</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Daily Tasks (employee only) -->
    <?php if (!$isAdmin && !empty($metasHoy)): ?>
    <div x-data="dailyTasksApp()" class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-nexo-500 to-nexo-700 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold"><?php echo __('dash_metas_dia'); ?></h3>
                    <p class="text-xs dark:text-white/40 text-gray-400"><?php echo date('l, d M Y'); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium px-3 py-1.5 rounded-xl" :class="completedAll ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500'" x-text="completedCount + '/' + tasks.length + ' completadas'"></span>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <template x-for="task in tasks" :key="task.id">
                <div class="rounded-xl border p-4 transition-all duration-300" :class="task.progreso >= task.meta_cantidad ? 'dark:border-emerald-500/30 border-emerald-200 dark:bg-emerald-500/[0.03] bg-emerald-50/50' : 'dark:border-white/[0.06] border-gray-200 dark:bg-white/[0.02] bg-gray-50/50'">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors" :class="task.progreso >= task.meta_cantidad ? 'bg-emerald-500/15 text-emerald-400' : iconBg(task.icono)">
                                <template x-if="task.icono === 'phone'"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></template>
                                <template x-if="task.icono === 'email'"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></template>
                                <template x-if="task.icono === 'file'"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></template>
                                <template x-if="task.icono === 'refresh'"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></template>
                                <template x-if="!['phone','email','file','refresh'].includes(task.icono)"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></template>
                            </div>
                            <span class="text-sm font-semibold truncate max-w-[100px]" x-text="task.titulo"></span>
                        </div>
                        <!-- Completed badge -->
                        <div x-show="task.progreso >= task.meta_cantidad" class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/10">
                            <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-[10px] font-bold text-emerald-400">OK</span>
                        </div>
                    </div>
                    <!-- Progress bar -->
                    <div class="h-2 rounded-full dark:bg-white/5 bg-gray-200 overflow-hidden mb-3">
                        <div class="h-full rounded-full transition-all duration-500" :class="task.progreso >= task.meta_cantidad ? 'bg-emerald-500' : 'bg-nexo-500'" :style="'width:' + Math.min(100, Math.round((task.progreso / task.meta_cantidad) * 100)) + '%'"></div>
                    </div>
                    <!-- Counter + buttons -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="text-lg font-bold" :class="task.progreso >= task.meta_cantidad ? 'text-emerald-400' : 'dark:text-white text-gray-900'" x-text="task.progreso"></span>
                            <span class="text-xs dark:text-white/30 text-gray-400">/ <span x-text="task.meta_cantidad"></span></span>
                        </div>
                        <div class="flex gap-1.5">
                            <button @click="decrement(task)" :disabled="task.progreso <= 0 || task.loading" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-200 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-300 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                                <svg class="w-3.5 h-3.5 dark:text-white/60 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                            </button>
                            <button @click="increment(task)" :disabled="task.loading" class="w-7 h-7 rounded-lg bg-nexo-500/15 flex items-center justify-center hover:bg-nexo-500/25 transition-colors text-nexo-400 disabled:opacity-30">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <?php endif; ?>

    <!-- Charts + Latest customers row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6">
        
        <!-- Revenue chart (2/3) -->
        <div class="panel-card xl:col-span-2 dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-semibold"><?php echo $isAdmin ? __('dash_reporte_ingresos') : __('dash_produccion_mensual'); ?></h3>
                    <div class="flex items-center gap-4 mt-2">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-nexo-500"></div>
                            <span class="text-xs dark:text-white/50 text-gray-500"><?php echo __('fin_ingresos'); ?></span>
                        </div>
                        <?php if ($isAdmin): ?>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-red-400"></div>
                            <span class="text-xs dark:text-white/50 text-gray-500"><?php echo __('fin_gastos'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <select class="text-xs dark:bg-dark-700 bg-gray-50 border dark:border-white/10 border-gray-200 rounded-lg px-2 py-1.5 outline-none dark:text-white/60 text-gray-500">
                    <option>2026</option>
                    <option>2025</option>
                </select>
            </div>
            <div class="h-64 sm:h-72">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Latest customers (1/3) -->
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold"><?php echo __('dash_ultimos_clientes'); ?></h3>
                <a href="clientes.php" class="text-xs text-nexo-400 hover:text-nexo-300 transition-colors"><?php echo __('dash_ver_todos'); ?></a>
            </div>
            <div class="space-y-3">
                <?php foreach ($latestClientes as $cli): 
                    $initials = strtoupper(substr($cli['nombre'], 0, 1) . substr(strrchr($cli['nombre'], ' ') ?: $cli['nombre'], 1, 1));
                    $colors = ['nuevo'=>'from-blue-500 to-cyan-500','contactado'=>'from-amber-500 to-orange-500','negociando'=>'from-nexo-500 to-nexo-700','ganado'=>'from-emerald-500 to-green-500','perdido'=>'from-red-500 to-rose-500'];
                    $gradient = $colors[$cli['estado']] ?? 'from-gray-500 to-gray-600';
                    $estadoLabels = ['nuevo'=>'Nuevo','contactado'=>'Contactado','negociando'=>'Negociando','ganado'=>'Ganado','perdido'=>'Perdido'];
                ?>
                <div class="flex items-center gap-3 p-2 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br <?php echo $gradient; ?> flex items-center justify-center text-white text-xs font-bold shrink-0">
                        <?php echo $initials; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($cli['nombre']); ?></p>
                        <p class="text-xs dark:text-white/40 text-gray-400 truncate"><?php echo htmlspecialchars($cli['empresa'] ?? $cli['email']); ?></p>
                    </div>
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gradient-to-r <?php echo $gradient; ?> text-white shrink-0">
                        <?php echo $estadoLabels[$cli['estado']] ?? $cli['estado']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Bottom row: Recent invoices + Team + Pipeline -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6">
        
        <!-- Recent invoices -->
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold"><?php echo $isAdmin ? __('dash_facturas_recientes') : __('dash_mis_facturas'); ?></h3>
                <a href="facturas.php" class="text-xs text-nexo-400 hover:text-nexo-300 transition-colors"><?php echo __('dash_ver_todas'); ?></a>
            </div>
            <div class="space-y-3">
                <?php foreach ($latestFacturas as $fac): 
                    $statusColors = ['pagada'=>'text-emerald-400 bg-emerald-400/10','enviada'=>'text-blue-400 bg-blue-400/10','borrador'=>'text-gray-400 bg-gray-400/10','vencida'=>'text-red-400 bg-red-400/10','cancelada'=>'text-red-400 bg-red-400/10'];
                    $sc = $statusColors[$fac['estado']] ?? 'text-gray-400 bg-gray-400/10';
                ?>
                <div class="flex items-center justify-between p-2 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors">
                    <div class="min-w-0">
                        <p class="text-sm font-medium"><?php echo $fac['numero']; ?></p>
                        <p class="text-xs dark:text-white/40 text-gray-400 truncate"><?php echo htmlspecialchars($fac['cliente_nombre']); ?></p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold">$<?php echo number_format($fac['total'], 0, ',', '.'); ?></p>
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full <?php echo $sc; ?>"><?php echo ucfirst($fac['estado']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Equipo / Mi Pipeline -->
        <?php if ($isAdmin): ?>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold"><?php echo __('dash_equipo'); ?></h3>
                <a href="usuarios.php" class="text-xs text-nexo-400 hover:text-nexo-300 transition-colors"><?php echo __('dash_gestionar'); ?></a>
            </div>
            <div class="space-y-3">
                <?php foreach ($teamUsers as $usr): 
                    $online = $usr['ultimo_acceso'] && strtotime($usr['ultimo_acceso']) > strtotime('-15 minutes');
                ?>
                <div class="flex items-center gap-3 p-2 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors">
                    <div class="relative">
                        <?php if (!empty($usr['avatar']) && file_exists(__DIR__ . '/uploads/avatars/' . $usr['avatar'])): ?>
                        <img src="uploads/avatars/<?php echo htmlspecialchars($usr['avatar']); ?>" class="w-9 h-9 rounded-full object-cover">
                        <?php else: ?>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-nexo-500 to-nexo-700 flex items-center justify-center text-white text-xs font-bold">
                            <?php echo strtoupper(substr($usr['nombre'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 dark:border-dark-800 border-white <?php echo $online ? 'bg-emerald-400' : 'bg-gray-400'; ?>"></div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($usr['nombre']); ?></p>
                        <p class="text-xs dark:text-white/40 text-gray-400"><?php echo htmlspecialchars($usr['email']); ?></p>
                    </div>
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-nexo-600/15 text-nexo-400 uppercase shrink-0"><?php echo $usr['rol']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Employee: Mi Pipeline -->
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold"><?php echo __('dash_mi_pipeline'); ?></h3>
                <a href="pipeline.php" class="text-xs text-nexo-400 hover:text-nexo-300 transition-colors"><?php echo __('dash_ver_todos'); ?></a>
            </div>
            <div class="space-y-3">
                <?php
                $stageColors = ['nuevo'=>['bg-blue-500/10','text-blue-400','bg-blue-500'],'contactado'=>['bg-amber-500/10','text-amber-400','bg-amber-500'],'negociando'=>['bg-nexo-500/10','text-nexo-400','bg-nexo-500'],'ganado'=>['bg-emerald-500/10','text-emerald-400','bg-emerald-500'],'perdido'=>['bg-red-500/10','text-red-400','bg-red-500']];
                $stageLabels = ['nuevo'=>'Nuevo','contactado'=>'Contactado','negociando'=>'Negociando','ganado'=>'Ganado','perdido'=>'Perdido'];
                $maxPipeline = max(1, max(array_values($pipelineStats) ?: [1]));
                foreach ($stageLabels as $est => $label):
                    $cnt = $pipelineStats[$est] ?? 0;
                    $pct = round(($cnt / $maxPipeline) * 100);
                    $sc = $stageColors[$est];
                ?>
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full <?php echo $sc[2]; ?>"></span>
                            <span class="text-xs font-medium dark:text-white/70 text-gray-600"><?php echo $label; ?></span>
                        </div>
                        <span class="text-xs font-bold dark:text-white/80 text-gray-700"><?php echo $cnt; ?></span>
                    </div>
                    <div class="h-1.5 rounded-full dark:bg-white/5 bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full <?php echo $sc[2]; ?> transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pipeline / Gastos del mes -->
        <?php if ($isAdmin): ?>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold"><?php echo __('dash_balance_mensual'); ?></h3>
                <a href="finanzas.php" class="text-xs text-nexo-400 hover:text-nexo-300 transition-colors"><?php echo __('btn_detalle', 'Detalle'); ?></a>
            </div>
            
            <!-- Donut chart -->
            <div class="flex items-center justify-center mb-4">
                <div class="relative w-40 h-40">
                    <canvas id="balanceChart"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                        <p class="text-lg font-bold <?php echo ($ingresosMes - $gastosMes) >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>">
                            $<?php echo number_format(abs($ingresosMes - $gastosMes), 0, ',', '.'); ?>
                        </p>
                        <p class="text-[10px] dark:text-white/40 text-gray-400"><?php echo ($ingresosMes - $gastosMes) >= 0 ? __('dash_utilidad') : __('dash_perdida'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                        <span class="dark:text-white/60 text-gray-500"><?php echo __('fin_ingresos'); ?></span>
                    </div>
                    <span class="font-semibold text-emerald-400">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-red-400"></div>
                        <span class="dark:text-white/60 text-gray-500"><?php echo __('fin_gastos'); ?></span>
                    </div>
                    <span class="font-semibold text-red-400">$<?php echo number_format($gastosMes, 0, ',', '.'); ?></span>
                </div>
                <div class="pt-2 mt-2 border-t dark:border-white/[0.06] border-gray-200 flex items-center justify-between text-sm">
                    <span class="dark:text-white/60 text-gray-500 font-medium"><?php echo __('dash_margen'); ?></span>
                    <span class="font-bold"><?php echo $ingresosMes > 0 ? round((($ingresosMes - $gastosMes) / $ingresosMes) * 100) : 0; ?>%</span>
                </div>
            </div>
        </div>
        <?php endif; /* isAdmin balance */ ?>
    </div>

</div>
</main>

<script>
// Revenue chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
const labelColor = isDark ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.4)';

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($meses); ?>,
        datasets: [
            {
                label: 'Ingresos',
                data: <?php echo json_encode($revenueByMonth); ?>,
                backgroundColor: '#7c3aed',
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.5,
                categoryPercentage: 0.6,
            },
            <?php if ($isAdmin): ?>
            {
                label: 'Gastos',
                data: <?php echo json_encode(array_map(function($v){return -$v;}, $expenseByMonth)); ?>,
                backgroundColor: '#ef4444',
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.5,
                categoryPercentage: 0.6,
            }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDark ? '#1a1726' : '#fff',
                titleColor: isDark ? '#fff' : '#111',
                bodyColor: isDark ? 'rgba(255,255,255,0.7)' : '#555',
                borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                cornerRadius: 12, padding: 12,
                callbacks: { label: (c) => (c.raw < 0 ? 'Gasto: $' : 'Ingreso: $') + Math.abs(c.raw).toLocaleString() }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: labelColor, font: { size: 11 } } },
            y: { grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 }, callback: v => '$' + Math.abs(v) } }
        }
    }
});

// Balance donut chart
<?php if ($isAdmin): ?>
new Chart(document.getElementById('balanceChart'), {
    type: 'doughnut',
    data: {
        labels: ['Ingresos', 'Gastos'],
        datasets: [{
            data: [<?php echo $ingresosMes; ?>, <?php echo $gastosMes; ?>],
            backgroundColor: ['#22c55e', '#ef4444'],
            borderWidth: 0,
            cutout: '75%',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
});
<?php endif; /* isAdmin balance chart */ ?>

// Daily tasks Alpine app
<?php if (!$isAdmin): ?>
function dailyTasksApp() {
    return {
        tasks: <?php echo json_encode($metasHoy); ?>.map(t => ({...t, progreso: parseInt(t.progreso), meta_cantidad: parseInt(t.meta_cantidad), loading: false})),
        get completedCount() { return this.tasks.filter(t => t.progreso >= t.meta_cantidad).length; },
        get completedAll() { return this.completedCount === this.tasks.length; },
        iconBg(icono) {
            const map = { phone: 'bg-blue-500/15 text-blue-400', email: 'bg-amber-500/15 text-amber-400', file: 'bg-nexo-500/15 text-nexo-400', refresh: 'bg-cyan-500/15 text-cyan-400' };
            return map[icono] || 'bg-gray-500/15 text-gray-400';
        },
        async increment(task) {
            if (task.loading) return;
            task.loading = true;
            try {
                const fd = new FormData();
                fd.append('action', 'update_progress');
                fd.append('id', task.id);
                fd.append('change', 1);
                const res = await fetch('api/metas_diarias.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.ok) task.progreso = data.progreso;
            } catch(e) { console.error(e); }
            task.loading = false;
        },
        async decrement(task) {
            if (task.loading || task.progreso <= 0) return;
            task.loading = true;
            try {
                const fd = new FormData();
                fd.append('action', 'update_progress');
                fd.append('id', task.id);
                fd.append('change', -1);
                const res = await fetch('api/metas_diarias.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.ok) task.progreso = data.progreso;
            } catch(e) { console.error(e); }
            task.loading = false;
        }
    };
}
<?php endif; ?>
</script>

<?php include 'includes/onboarding.php'; ?>

<?php include 'includes/footer.php'; ?>
