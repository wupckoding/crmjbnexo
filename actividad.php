<?php
require_once 'includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pageTitle = __('act_titulo', 'Registro de Actividad');
$currentPage = 'actividad';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 30;
$offset = ($page - 1) * $limit;

// Build WHERE clauses
$conditions = [];
$params = [];
if (!empty($_GET['modulo'])) {
    $conditions[] = 'a.modulo = :m';
    $params['m'] = $_GET['modulo'];
}
if (!empty($_GET['accion'])) {
    $conditions[] = 'a.accion = :ac';
    $params['ac'] = $_GET['accion'];
}
if (!empty($_GET['usuario'])) {
    $conditions[] = 'a.usuario_id = :uid';
    $params['uid'] = (int)$_GET['usuario'];
}
if (!empty($_GET['buscar'])) {
    $conditions[] = 'a.detalle LIKE :buscar';
    $params['buscar'] = '%' . $_GET['buscar'] . '%';
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM actividad_log a {$where}");
$total->execute($params);
$total = (int)$total->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$logs = $pdo->prepare("SELECT a.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar FROM actividad_log a LEFT JOIN usuarios u ON a.usuario_id = u.id {$where} ORDER BY a.creado_en DESC LIMIT {$limit} OFFSET {$offset}");
$logs->execute($params);
$logs = $logs->fetchAll();

$modulos = $pdo->query("SELECT DISTINCT modulo FROM actividad_log ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
$acciones = $pdo->query("SELECT DISTINCT accion FROM actividad_log ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
$usuarios = $pdo->query("SELECT DISTINCT u.id, u.nombre FROM actividad_log a JOIN usuarios u ON a.usuario_id = u.id ORDER BY u.nombre")->fetchAll();

// Stats
$statsHoy = $pdo->query("SELECT COUNT(*) FROM actividad_log WHERE DATE(creado_en) = CURDATE()")->fetchColumn();
$statsSemana = $pdo->query("SELECT COUNT(*) FROM actividad_log WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$statsPorAccion = $pdo->query("SELECT accion, COUNT(*) as cnt FROM actividad_log GROUP BY accion ORDER BY cnt DESC")->fetchAll();
$statsPorModulo = $pdo->query("SELECT modulo, COUNT(*) as cnt FROM actividad_log GROUP BY modulo ORDER BY cnt DESC")->fetchAll();

// Action config
$accionConfig = [
    'crear'       => ['label' => __('act_crear','Crear'),       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>', 'bg' => 'bg-emerald-500/10 dark:bg-emerald-500/15', 'text' => 'text-emerald-600 dark:text-emerald-400', 'dot' => 'bg-emerald-500'],
    'editar'      => ['label' => __('act_editar','Editar'),      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>', 'bg' => 'bg-blue-500/10 dark:bg-blue-500/15', 'text' => 'text-blue-600 dark:text-blue-400', 'dot' => 'bg-blue-500'],
    'eliminar'    => ['label' => __('act_eliminar','Eliminar'),    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>', 'bg' => 'bg-red-500/10 dark:bg-red-500/15', 'text' => 'text-red-600 dark:text-red-400', 'dot' => 'bg-red-500'],
    'mover'       => ['label' => __('act_mover','Mover'),       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>', 'bg' => 'bg-amber-500/10 dark:bg-amber-500/15', 'text' => 'text-amber-600 dark:text-amber-400', 'dot' => 'bg-amber-500'],
    'archivar'    => ['label' => __('act_archivar','Archivar'),    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>', 'bg' => 'bg-slate-500/10 dark:bg-slate-500/15', 'text' => 'text-slate-600 dark:text-slate-400', 'dot' => 'bg-slate-500'],
    'desarchivar' => ['label' => __('act_restaurar','Restaurar'),   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>', 'bg' => 'bg-teal-500/10 dark:bg-teal-500/15', 'text' => 'text-teal-600 dark:text-teal-400', 'dot' => 'bg-teal-500'],
    'asignar'     => ['label' => __('act_asignar','Asignar'),     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>', 'bg' => 'bg-indigo-500/10 dark:bg-indigo-500/15', 'text' => 'text-indigo-600 dark:text-indigo-400', 'dot' => 'bg-indigo-500'],
    'login'       => ['label' => 'Login',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>', 'bg' => 'bg-nexo-500/10 dark:bg-nexo-500/15', 'text' => 'text-nexo-600 dark:text-nexo-400', 'dot' => 'bg-nexo-500'],
];
$defaultConfig = ['label' => '', 'icon' => '<circle cx="12" cy="12" r="3"/>', 'bg' => 'bg-gray-500/10', 'text' => 'text-gray-500 dark:text-gray-400', 'dot' => 'bg-gray-500'];

// Module icons
$moduloIcons = [
    'clientes'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'pipeline'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>',
    'facturas'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>',
    'usuarios'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
];

// Build query string helper
function buildQS($overrides = []) {
    $p = array_merge([
        'modulo'  => $_GET['modulo'] ?? '',
        'accion'  => $_GET['accion'] ?? '',
        'usuario' => $_GET['usuario'] ?? '',
        'buscar'  => $_GET['buscar'] ?? '',
        'page'    => $_GET['page'] ?? 1,
    ], $overrides);
    return http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-5">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-nexo-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-nexo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <?php echo __('act_titulo'); ?>
            </h2>
            <p class="text-xs dark:text-white/40 text-gray-400 mt-1"><?php echo __('act_subtitulo'); ?></p>
        </div>
        <div class="flex items-center gap-2">
            <?php $hasFilters = !empty($_GET['modulo']) || !empty($_GET['accion']) || !empty($_GET['usuario']) || !empty($_GET['buscar']); ?>
            <?php if ($hasFilters): ?>
            <a href="actividad.php" class="px-3 py-1.5 text-xs rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-1.5">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <?php echo __('act_limpiar_filtros'); ?>
            </a>
            <?php endif; ?>
            <span class="text-xs dark:text-white/30 text-gray-400 tabular-nums"><?php echo number_format($total); ?> <?php echo __('act_registros'); ?></span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-nexo-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-nexo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider"><?php echo __('act_total'); ?></span>
            </div>
            <p class="text-2xl font-bold"><?php echo number_format($total); ?></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5"><?php echo __('act_eventos_registrados'); ?></p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider"><?php echo __('act_hoy'); ?></span>
            </div>
            <p class="text-2xl font-bold text-emerald-500"><?php echo number_format($statsHoy); ?></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5"><?php echo __('act_acciones_hoy'); ?></p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider"><?php echo __('act_semana'); ?></span>
            </div>
            <p class="text-2xl font-bold text-blue-500"><?php echo number_format($statsSemana); ?></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5"><?php echo __('act_ultima_semana'); ?></p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider"><?php echo __('act_top'); ?></span>
            </div>
            <p class="text-2xl font-bold text-amber-500 capitalize"><?php echo $statsPorAccion[0]['accion'] ?? '—'; ?></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5"><?php echo __('act_accion_frecuente'); ?></p>
        </div>
    </div>

    <!-- Action breakdown mini-bar -->
    <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-medium dark:text-white/50 text-gray-500"><?php echo __('act_distribucion'); ?></p>
            <p class="text-[10px] dark:text-white/25 text-gray-300"><?php echo count($statsPorAccion); ?> <?php echo __('act_tipos'); ?></p>
        </div>
        <div class="flex gap-1 h-2 rounded-full overflow-hidden mb-3">
            <?php
            $totalAll = array_sum(array_column($statsPorAccion, 'cnt'));
            foreach ($statsPorAccion as $sa):
                $cfg = $accionConfig[$sa['accion']] ?? $defaultConfig;
                $pct = $totalAll ? round($sa['cnt'] / $totalAll * 100, 1) : 0;
            ?>
            <div class="<?php echo $cfg['dot']; ?> rounded-full" style="width:<?php echo max(3, $pct); ?>%" title="<?php echo ucfirst($sa['accion']); ?>: <?php echo $sa['cnt']; ?> (<?php echo $pct; ?>%)"></div>
            <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap gap-x-4 gap-y-1">
            <?php foreach ($statsPorAccion as $sa):
                $cfg = $accionConfig[$sa['accion']] ?? $defaultConfig;
            ?>
            <div class="flex items-center gap-1.5 text-[11px]">
                <span class="w-2 h-2 rounded-full <?php echo $cfg['dot']; ?>"></span>
                <span class="dark:text-white/50 text-gray-500 capitalize"><?php echo $cfg['label'] ?: $sa['accion']; ?></span>
                <span class="dark:text-white/25 text-gray-300 tabular-nums"><?php echo $sa['cnt']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search -->
        <form method="get" class="relative flex-1 min-w-[200px] max-w-xs">
            <?php if (!empty($_GET['modulo'])): ?><input type="hidden" name="modulo" value="<?php echo htmlspecialchars($_GET['modulo']); ?>"><?php endif; ?>
            <?php if (!empty($_GET['accion'])): ?><input type="hidden" name="accion" value="<?php echo htmlspecialchars($_GET['accion']); ?>"><?php endif; ?>
            <?php if (!empty($_GET['usuario'])): ?><input type="hidden" name="usuario" value="<?php echo htmlspecialchars($_GET['usuario']); ?>"><?php endif; ?>
            <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 dark:text-white/25 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="buscar" value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>" placeholder="<?php echo __('act_buscar'); ?>" class="w-full pl-9 pr-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
        </form>
        <!-- Module filter -->
        <select onchange="location.href='actividad.php?'+new URLSearchParams({<?php echo !empty($_GET['accion']) ? "accion:'" . htmlspecialchars($_GET['accion']) . "'," : ''; ?><?php echo !empty($_GET['usuario']) ? "usuario:'" . htmlspecialchars($_GET['usuario']) . "'," : ''; ?><?php echo !empty($_GET['buscar']) ? "buscar:'" . htmlspecialchars($_GET['buscar']) . "'," : ''; ?>modulo:this.value}).toString()" class="px-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
            <option value=""><?php echo __('act_todos_modulos'); ?></option>
            <?php foreach ($modulos as $m): ?>
            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo ($_GET['modulo'] ?? '') === $m ? 'selected' : ''; ?>><?php echo ucfirst($m); ?></option>
            <?php endforeach; ?>
        </select>
        <!-- Action filter -->
        <select onchange="location.href='actividad.php?'+new URLSearchParams({<?php echo !empty($_GET['modulo']) ? "modulo:'" . htmlspecialchars($_GET['modulo']) . "'," : ''; ?><?php echo !empty($_GET['usuario']) ? "usuario:'" . htmlspecialchars($_GET['usuario']) . "'," : ''; ?><?php echo !empty($_GET['buscar']) ? "buscar:'" . htmlspecialchars($_GET['buscar']) . "'," : ''; ?>accion:this.value}).toString()" class="px-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
            <option value=""><?php echo __('act_todas_acciones'); ?></option>
            <?php foreach ($acciones as $a): $cfg = $accionConfig[$a] ?? $defaultConfig; ?>
            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo ($_GET['accion'] ?? '') === $a ? 'selected' : ''; ?>><?php echo $cfg['label'] ?: ucfirst($a); ?></option>
            <?php endforeach; ?>
        </select>
        <!-- User filter -->
        <select onchange="location.href='actividad.php?'+new URLSearchParams({<?php echo !empty($_GET['modulo']) ? "modulo:'" . htmlspecialchars($_GET['modulo']) . "'," : ''; ?><?php echo !empty($_GET['accion']) ? "accion:'" . htmlspecialchars($_GET['accion']) . "'," : ''; ?><?php echo !empty($_GET['buscar']) ? "buscar:'" . htmlspecialchars($_GET['buscar']) . "'," : ''; ?>usuario:this.value}).toString()" class="px-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
            <option value=""><?php echo __('act_todos_usuarios'); ?></option>
            <?php foreach ($usuarios as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php echo ($_GET['usuario'] ?? '') == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Activity Table -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b dark:border-white/[0.06] border-gray-100">
                    <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400"><?php echo __('act_fecha'); ?></th>
                    <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400"><?php echo __('act_usuario'); ?></th>
                    <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400"><?php echo __('act_accion'); ?></th>
                    <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400"><?php echo __('act_modulo'); ?></th>
                    <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400"><?php echo __('act_detalle'); ?></th>
                    <th class="text-right px-4 py-3 text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400"><?php echo __('act_ip'); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-white/[0.04] divide-gray-50">
            <?php
            $prevDate = '';
            foreach ($logs as $log):
                $cfg = $accionConfig[$log['accion']] ?? $defaultConfig;
                $mIcon = $moduloIcons[$log['modulo']] ?? '<circle cx="12" cy="12" r="3"/>';
                $logDate = date('Y-m-d', strtotime($log['creado_en']));
                $isToday = $logDate === date('Y-m-d');
                $isYesterday = $logDate === date('Y-m-d', strtotime('-1 day'));
                $initial = mb_strtoupper(mb_substr($log['usuario_nombre'] ?? '?', 0, 1));

                // Show date separator
                if ($logDate !== $prevDate):
                    $prevDate = $logDate;
                    $dateLabel = $isToday ? __('act_hoy','Hoy') : ($isYesterday ? __('act_ayer','Ayer') : date('d M Y', strtotime($logDate)));
            ?>
            <tr>
                <td colspan="6" class="px-4 py-2 dark:bg-white/[0.02] bg-gray-25">
                    <span class="text-[10px] font-semibold uppercase tracking-wider dark:text-white/25 text-gray-400"><?php echo $dateLabel; ?></span>
                </td>
            </tr>
            <?php endif; ?>
            <tr class="hover:dark:bg-white/[0.02] hover:bg-gray-50/80 transition-colors group">
                <!-- Date & Time -->
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex flex-col">
                        <span class="text-xs font-medium dark:text-white/70 text-gray-600 tabular-nums"><?php echo date('H:i:s', strtotime($log['creado_en'])); ?></span>
                        <span class="text-[10px] dark:text-white/25 text-gray-300 tabular-nums"><?php echo date('d/m/Y', strtotime($log['creado_en'])); ?></span>
                    </div>
                </td>
                <!-- User -->
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <?php if (!empty($log['usuario_avatar'])): ?>
                        <img src="uploads/avatars/<?php echo htmlspecialchars($log['usuario_avatar']); ?>" class="w-6 h-6 rounded-full object-cover shrink-0">
                        <?php else: ?>
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0"><?php echo $initial; ?></div>
                        <?php endif; ?>
                        <span class="text-xs font-medium dark:text-white/80 text-gray-700"><?php echo htmlspecialchars($log['usuario_nombre'] ?? __('act_desconocido','Desconocido')); ?></span>
                    </div>
                </td>
                <!-- Action -->
                <td class="px-4 py-3">
                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg <?php echo $cfg['bg']; ?>">
                        <svg class="w-3 h-3 <?php echo $cfg['text']; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><?php echo $cfg['icon']; ?></svg>
                        <span class="text-[11px] font-medium <?php echo $cfg['text']; ?> capitalize"><?php echo $cfg['label'] ?: $log['accion']; ?></span>
                    </div>
                </td>
                <!-- Module -->
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 dark:text-white/25 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><?php echo $mIcon; ?></svg>
                        <span class="text-xs capitalize dark:text-white/60 text-gray-500"><?php echo htmlspecialchars($log['modulo']); ?></span>
                    </div>
                </td>
                <!-- Detail -->
                <td class="px-4 py-3">
                    <p class="text-xs dark:text-white/50 text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($log['detalle']); ?>"><?php echo htmlspecialchars($log['detalle']); ?></p>
                </td>
                <!-- IP -->
                <td class="px-4 py-3 text-right">
                    <span class="text-[10px] font-mono dark:text-white/20 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"><?php echo htmlspecialchars($log['ip']); ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr>
                <td colspan="6" class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-xl dark:bg-white/5 bg-gray-50 flex items-center justify-center"><svg class="w-5 h-5 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                        <p class="text-sm dark:text-white/30 text-gray-400"><?php echo __('act_sin_registros'); ?></p>
                        <?php if ($hasFilters): ?><a href="actividad.php" class="text-xs text-nexo-500 hover:underline"><?php echo __('act_limpiar_filtros'); ?></a><?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between">
        <p class="text-[10px] dark:text-white/25 text-gray-400"><?php echo __('act_pagina'); ?> <?php echo $page; ?> <?php echo __('act_de'); ?> <?php echo $totalPages; ?> &middot; <?php echo number_format($total); ?> <?php echo __('act_registros'); ?></p>
        <div class="flex items-center gap-1">
            <?php if ($page > 1): ?>
            <a href="actividad.php?<?php echo buildQS(['page' => $page - 1]); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="actividad.php?<?php echo buildQS(['page' => $i]); ?>" 
               class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium <?php echo $i === $page ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200'; ?> transition-colors"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="actividad.php?<?php echo buildQS(['page' => $page + 1]); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Module breakdown -->
    <div class="grid grid-cols-1 sm:grid-cols-<?php echo count($statsPorModulo); ?> gap-3">
        <?php foreach ($statsPorModulo as $sm):
            $mIcon = $moduloIcons[$sm['modulo']] ?? '<circle cx="12" cy="12" r="3"/>';
            $mPct = $totalAll ? round($sm['cnt'] / $totalAll * 100) : 0;
        ?>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><?php echo $mIcon; ?></svg>
                <span class="text-xs font-medium capitalize"><?php echo htmlspecialchars($sm['modulo']); ?></span>
                <span class="text-[10px] dark:text-white/25 text-gray-300 ml-auto"><?php echo $mPct; ?>%</span>
            </div>
            <div class="w-full h-1.5 rounded-full dark:bg-white/5 bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full bg-nexo-500" style="width:<?php echo $mPct; ?>%"></div>
            </div>
            <p class="text-[10px] dark:text-white/25 text-gray-300 mt-1.5"><?php echo number_format($sm['cnt']); ?> <?php echo __('act_eventos'); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
