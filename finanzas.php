<?php
require_once 'includes/auth_check.php';
$pageTitle = __('fin_titulo');
$currentPage = 'finanzas';

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$userId = $_SESSION['user_id'];

// Month nav
$mesActual = (int)date('m');
$anioActual = (int)date('Y');
$mesVer = isset($_GET['mes']) ? (int)$_GET['mes'] : $mesActual;
$anioVer = isset($_GET['anio']) ? (int)$_GET['anio'] : $anioActual;
$filtroCategoria = trim($_GET['cat'] ?? '');
$filtroMetodo = trim($_GET['metodo'] ?? '');
$filtroBusqueda = trim($_GET['q'] ?? '');
$filtroTipo = trim($_GET['tipo'] ?? ''); // gasto|ingreso

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
if (function_exists('__')) {
    $mesesI18n = [
        'es' => ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        'pt' => ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
        'en' => ['','January','February','March','April','May','June','July','August','September','October','November','December'],
    ];
    $meses = $mesesI18n[$_idioma ?? 'es'] ?? $meses;
}

$prevM = $mesVer - 1; $prevY = $anioVer;
if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $mesVer + 1; $nextY = $anioVer;
if ($nextM > 12) { $nextM = 1; $nextY++; }

// Categories
$categoriasAll = $pdo->query("SELECT * FROM categorias_financieras WHERE activo = 1 ORDER BY tipo, nombre")->fetchAll();
$categoriasGasto = array_filter($categoriasAll, fn($c) => $c['tipo'] !== 'ingreso');
$categoriasIngreso = array_filter($categoriasAll, fn($c) => $c['tipo'] !== 'gasto');
$catMap = [];
foreach ($categoriasAll as $c) $catMap[$c['nombre']] = $c;

// Clients for income linking
$clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll();

// === STATS ===
$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE MONTH(fecha) = :m AND YEAR(fecha) = :y");
$stmt->execute(['m'=>$mesVer,'y'=>$anioVer]);
$ingresosMes = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM gastos WHERE MONTH(fecha) = :m AND YEAR(fecha) = :y");
$stmt->execute(['m'=>$mesVer,'y'=>$anioVer]);
$gastosMes = (float)$stmt->fetchColumn();

$balance = $ingresosMes - $gastosMes;
$margen = $ingresosMes > 0 ? round(($balance / $ingresosMes) * 100) : 0;

$porRecibir = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado IN ('enviada','vencida')")->fetchColumn();
$factPendCount = (int)$pdo->query("SELECT COUNT(*) FROM facturas WHERE estado IN ('enviada','vencida')")->fetchColumn();

// Previous month comparison
$pmM = $mesVer - 1; $pmY = $anioVer; if ($pmM < 1) { $pmM = 12; $pmY--; }
$stPrev = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y");
$stPrev->execute(['m'=>$pmM,'y'=>$pmY]);
$ingresosPrev = (float)$stPrev->fetchColumn();
$ingresosChange = $ingresosPrev > 0 ? round((($ingresosMes - $ingresosPrev) / $ingresosPrev) * 100) : ($ingresosMes > 0 ? 100 : 0);

$stPrev2 = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM gastos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y");
$stPrev2->execute(['m'=>$pmM,'y'=>$pmY]);
$gastosPrev = (float)$stPrev2->fetchColumn();
$gastosChange = $gastosPrev > 0 ? round((($gastosMes - $gastosPrev) / $gastosPrev) * 100) : ($gastosMes > 0 ? 100 : 0);

// === EXPENSE CATEGORIES BREAKDOWN ===
$stmt = $pdo->prepare("SELECT categoria, COALESCE(SUM(monto),0) as total, COUNT(*) as qty FROM gastos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y GROUP BY categoria ORDER BY total DESC");
$stmt->execute(['m'=>$mesVer,'y'=>$anioVer]);
$catBreakdown = $stmt->fetchAll();

// === INCOME BY METHOD ===
$stmt = $pdo->prepare("SELECT metodo_pago, COALESCE(SUM(monto),0) as total, COUNT(*) as qty FROM ingresos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y GROUP BY metodo_pago ORDER BY total DESC");
$stmt->execute(['m'=>$mesVer,'y'=>$anioVer]);
$metodoBreakdown = $stmt->fetchAll();

// === TRANSACTIONS LIST (combined, filtered) ===
$whereExtras = '';
$params = ['m'=>$mesVer,'y'=>$anioVer];

// Build gastos query
$gastoWhere = "MONTH(g.fecha)=:m AND YEAR(g.fecha)=:y";
$gastoParams = ['m'=>$mesVer,'y'=>$anioVer];
if ($filtroCategoria) { $gastoWhere .= " AND g.categoria = :cat"; $gastoParams['cat'] = $filtroCategoria; }
if ($filtroBusqueda) { $gastoWhere .= " AND g.descripcion LIKE :q"; $gastoParams['q'] = "%$filtroBusqueda%"; }

$stmtG = $pdo->prepare("SELECT g.*, u.nombre as usuario_nombre FROM gastos g LEFT JOIN usuarios u ON g.usuario_id = u.id WHERE $gastoWhere ORDER BY g.fecha DESC, g.id DESC LIMIT 50");
$stmtG->execute($gastoParams);
$gastos = $stmtG->fetchAll();

// Build ingresos query
$ingresoWhere = "MONTH(i.fecha)=:m AND YEAR(i.fecha)=:y";
$ingresoParams = ['m'=>$mesVer,'y'=>$anioVer];
if ($filtroMetodo) { $ingresoWhere .= " AND i.metodo_pago = :met"; $ingresoParams['met'] = $filtroMetodo; }
if ($filtroBusqueda) { $ingresoWhere .= " AND i.descripcion LIKE :q"; $ingresoParams['q'] = "%$filtroBusqueda%"; }

$stmtI = $pdo->prepare("SELECT i.*, f.numero as factura_num, c.nombre as cliente_nombre, u.nombre as usuario_nombre FROM ingresos i LEFT JOIN facturas f ON i.factura_id = f.id LEFT JOIN clientes c ON i.cliente_id = c.id LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE $ingresoWhere ORDER BY i.fecha DESC, i.id DESC LIMIT 50");
$stmtI->execute($ingresoParams);
$ingresos = $stmtI->fetchAll();

// === 12-MONTH CHART ===
$chartLabels = []; $chartIngresos = []; $chartGastos = [];
for ($i = 11; $i >= 0; $i--) {
    $cm = (int)date('m', strtotime("-$i months"));
    $cy = (int)date('Y', strtotime("-$i months"));
    $chartLabels[] = substr($meses[$cm], 0, 3) . ' ' . substr($cy, 2);
    $s = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM ingresos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y");
    $s->execute(['m'=>$cm,'y'=>$cy]);
    $chartIngresos[] = (float)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM gastos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y");
    $s->execute(['m'=>$cm,'y'=>$cy]);
    $chartGastos[] = (float)$s->fetchColumn();
}

// Pending invoices for sidebar
$factPendientes = $pdo->query("SELECT f.*, c.nombre as cn FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.estado IN ('enviada','vencida') ORDER BY f.fecha_vencimiento ASC LIMIT 10")->fetchAll();

// Top gastos del mes
$topGastos = $pdo->prepare("SELECT descripcion, monto, categoria, fecha FROM gastos WHERE MONTH(fecha)=:m AND YEAR(fecha)=:y ORDER BY monto DESC LIMIT 5");
$topGastos->execute(['m'=>$mesVer,'y'=>$anioVer]);
$topGastos = $topGastos->fetchAll();

// Método de pago config
$metodosConfig = [
    'transferencia' => ['label'=>'Transferencia', 'color'=>'#3b82f6', 'icon'=>'bank'],
    'paypal'        => ['label'=>'PayPal',        'color'=>'#0070ba', 'icon'=>'paypal'],
    'stripe'        => ['label'=>'Stripe',        'color'=>'#635bff', 'icon'=>'card'],
    'efectivo'      => ['label'=>'Efectivo',      'color'=>'#22c55e', 'icon'=>'cash'],
    'crypto'        => ['label'=>'Crypto',        'color'=>'#f59e0b', 'icon'=>'bitcoin'],
    'otro'          => ['label'=>'Otro',          'color'=>'#6b7280', 'icon'=>'dots'],
];

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-5" x-data="finanzasApp()" x-cloak>

    <!-- ========== HEADER ========== -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold dark:text-white text-gray-900"><?php echo __('fin_titulo'); ?></h1>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('fin_subtitulo'); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openGasto()" class="px-3.5 py-2 rounded-xl text-sm font-medium text-white bg-red-500 hover:bg-red-600 transition-colors shadow-lg shadow-red-500/20 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                <?php echo __('fin_gasto'); ?>
            </button>
            <button @click="openIngreso()" class="px-3.5 py-2 rounded-xl text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-600/20 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?php echo __('fin_ingreso'); ?>
            </button>
            <button @click="showCatModal = true" class="w-9 h-9 rounded-xl dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors" title="<?php echo __('fin_gestionar_cat'); ?>">
                <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
            </button>
        </div>
    </div>

    <!-- ========== MONTH NAV + FILTERS ========== -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="?mes=<?php echo $prevM; ?>&anio=<?php echo $prevY; ?>" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-base font-bold px-2"><?php echo $meses[$mesVer] . ' ' . $anioVer; ?></h2>
            <a href="?mes=<?php echo $nextM; ?>&anio=<?php echo $nextY; ?>" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <?php if ($mesVer !== $mesActual || $anioVer !== $anioActual): ?>
            <a href="finanzas.php" class="px-2.5 py-1 text-[10px] font-medium rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('fin_actual'); ?></a>
            <?php endif; ?>
        </div>
        <!-- Search bar -->
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="mes" value="<?php echo $mesVer; ?>">
            <input type="hidden" name="anio" value="<?php echo $anioVer; ?>">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/25 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="<?php echo htmlspecialchars($filtroBusqueda); ?>" placeholder="<?php echo __('fin_buscar_trans'); ?>" class="pl-9 pr-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 w-48 sm:w-56 transition-colors">
            </div>
            <?php if ($filtroBusqueda || $filtroCategoria || $filtroMetodo): ?>
            <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>" class="text-[10px] px-2 py-1 rounded-lg bg-red-500/10 text-red-400 font-medium hover:bg-red-500/20 transition-colors"><?php echo __('fin_limpiar'); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ========== KPI CARDS ========== -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <!-- Ingresos -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-8 translate-x-8"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-medium dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_ingresos'); ?></span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold dark:text-emerald-400 text-emerald-600">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-[10px] font-medium <?php echo $ingresosChange >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>"><?php echo ($ingresosChange >= 0 ? '+' : '') . $ingresosChange; ?>%</span>
                <span class="text-[10px] dark:text-white/25 text-gray-300">vs mes ant.</span>
            </div>
        </div>
        <!-- Gastos -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-red-500/5 rounded-full -translate-y-8 translate-x-8"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-medium dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_gastos'); ?></span>
                <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold dark:text-red-400 text-red-600">$<?php echo number_format($gastosMes, 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-[10px] font-medium <?php echo $gastosChange <= 0 ? 'text-emerald-400' : 'text-red-400'; ?>"><?php echo ($gastosChange >= 0 ? '+' : '') . $gastosChange; ?>%</span>
                <span class="text-[10px] dark:text-white/25 text-gray-300">vs mes ant.</span>
            </div>
        </div>
        <!-- Balance -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-medium dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_balance'); ?></span>
                <div class="w-8 h-8 rounded-lg <?php echo $balance >= 0 ? 'bg-emerald-500/10' : 'bg-red-500/10'; ?> flex items-center justify-center">
                    <svg class="w-4 h-4 <?php echo $balance >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold <?php echo $balance >= 0 ? 'dark:text-emerald-400 text-emerald-600' : 'dark:text-red-400 text-red-600'; ?>">$<?php echo number_format(abs($balance), 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-[10px] dark:text-white/30 text-gray-400">Margen: <?php echo $margen; ?>%</span>
            </div>
        </div>
        <!-- Por Recibir -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-medium dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_por_recibir'); ?></span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold dark:text-amber-400 text-amber-600">$<?php echo number_format($porRecibir, 0, ',', '.'); ?></p>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-[10px] dark:text-white/30 text-gray-400"><?php echo $factPendCount; ?> <?php echo __('fin_facturas'); ?></span>
            </div>
        </div>
    </div>

    <!-- ========== CHART ========== -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('fin_flujo_caja'); ?></h3>
                <p class="text-[11px] dark:text-white/30 text-gray-400 mt-0.5"><?php echo __('fin_ultimos_12'); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></div><span class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('fin_ingresos'); ?></span></div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-red-400"></div><span class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('fin_gastos'); ?></span></div>
            </div>
        </div>
        <div class="h-56 sm:h-64"><canvas id="finChart"></canvas></div>
    </div>

    <!-- ========== TABS ========== -->
    <div class="flex items-center gap-1.5 border-b dark:border-white/[0.06] border-gray-200 pb-px">
        <button @click="tab = 'resumen'" :class="tab==='resumen' ? 'text-nexo-400 border-b-2 border-nexo-500 dark:bg-nexo-500/5' : 'dark:text-white/40 text-gray-400 border-b-2 border-transparent'" class="px-3.5 py-2 text-xs font-semibold transition-colors"><?php echo __('fin_resumen'); ?></button>
        <button @click="tab = 'gastos'" :class="tab==='gastos' ? 'text-red-400 border-b-2 border-red-500 dark:bg-red-500/5' : 'dark:text-white/40 text-gray-400 border-b-2 border-transparent'" class="px-3.5 py-2 text-xs font-semibold transition-colors"><?php echo __('fin_gastos'); ?></button>
        <button @click="tab = 'ingresos'" :class="tab==='ingresos' ? 'text-emerald-400 border-b-2 border-emerald-500 dark:bg-emerald-500/5' : 'dark:text-white/40 text-gray-400 border-b-2 border-transparent'" class="px-3.5 py-2 text-xs font-semibold transition-colors"><?php echo __('fin_ingresos'); ?></button>
    </div>

    <!-- ========== TAB: RESUMEN ========== -->
    <div x-show="tab === 'resumen'" class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-5">
        <!-- Left: breakdowns -->
        <div class="space-y-5">
            <!-- Categories breakdown -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('fin_gastos_por_cat'); ?></h3>
                    <span class="text-xs dark:text-white/30 text-gray-400"><?php echo count($catBreakdown); ?> <?php echo __('fin_categorias'); ?></span>
                </div>
                <?php if (empty($catBreakdown)): ?>
                <div class="text-center py-8"><p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('fin_sin_gastos'); ?></p></div>
                <?php else: foreach ($catBreakdown as $cat):
                    $pct = $gastosMes > 0 ? round(($cat['total'] / $gastosMes) * 100) : 0;
                    $catInfo = $catMap[$cat['categoria']] ?? null;
                    $catColor = $catInfo ? $catInfo['color'] : '#6b7280';
                ?>
                <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>&cat=<?php echo urlencode($cat['categoria']); ?>" class="block mb-3 last:mb-0 group">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?php echo $catColor; ?>"></span>
                            <span class="text-xs font-medium dark:text-white/70 text-gray-600 group-hover:text-nexo-400 transition-colors"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                            <span class="text-[10px] dark:text-white/20 text-gray-300">(<?php echo $cat['qty']; ?>)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold dark:text-white/80 text-gray-700">$<?php echo number_format($cat['total'], 0, ',', '.'); ?></span>
                            <span class="text-[10px] dark:text-white/25 text-gray-400"><?php echo $pct; ?>%</span>
                        </div>
                    </div>
                    <div class="h-1.5 rounded-full dark:bg-white/5 bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500" style="width:<?php echo $pct; ?>%; background:<?php echo $catColor; ?>"></div>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>

            <!-- Income by method -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="text-sm font-bold dark:text-white text-gray-900 mb-4"><?php echo __('fin_ingresos_por_metodo'); ?></h3>
                <?php if (empty($metodoBreakdown)): ?>
                <div class="text-center py-8"><p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('fin_sin_ingresos'); ?></p></div>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($metodoBreakdown as $met):
                        $mc = $metodosConfig[$met['metodo_pago']] ?? ['label'=>ucfirst($met['metodo_pago']),'color'=>'#6b7280'];
                    ?>
                    <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>&metodo=<?php echo urlencode($met['metodo_pago']); ?>" class="p-3 rounded-xl dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100 hover:dark:border-white/10 hover:border-gray-200 transition-colors group">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2 h-2 rounded-full" style="background:<?php echo $mc['color']; ?>"></span>
                            <span class="text-[11px] font-medium dark:text-white/50 text-gray-500 group-hover:text-nexo-400 transition-colors"><?php echo $mc['label']; ?></span>
                        </div>
                        <p class="text-sm font-bold dark:text-emerald-400 text-emerald-600">$<?php echo number_format($met['total'], 0, ',', '.'); ?></p>
                        <p class="text-[10px] dark:text-white/25 text-gray-300"><?php echo $met['qty']; ?> transacción<?php echo $met['qty'] > 1 ? 'es' : ''; ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top Gastos -->
            <?php if ($topGastos): ?>
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="text-sm font-bold dark:text-white text-gray-900 mb-3"><?php echo __('fin_mayores_gastos'); ?></h3>
                <div class="space-y-2">
                    <?php foreach ($topGastos as $idx => $tg):
                        $catInfo = $catMap[$tg['categoria']] ?? null;
                        $catColor = $catInfo ? $catInfo['color'] : '#6b7280';
                    ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors">
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold dark:text-white/30 text-gray-400 dark:bg-white/5 bg-gray-100"><?php echo $idx + 1; ?></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate dark:text-white text-gray-900"><?php echo htmlspecialchars($tg['descripcion']); ?></p>
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $catColor; ?>"></span>
                                <span class="text-[10px] dark:text-white/30 text-gray-400"><?php echo htmlspecialchars($tg['categoria']); ?> · <?php echo date('d/m', strtotime($tg['fecha'])); ?></span>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-red-400">-$<?php echo number_format($tg['monto'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right sidebar: Pending invoices -->
        <div class="space-y-4">
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('fin_fact_pendientes'); ?></h3>
                    <a href="facturas.php" class="text-[10px] text-nexo-400 hover:text-nexo-300 font-medium"><?php echo __('fin_ver_todas'); ?></a>
                </div>
                <div class="p-2 max-h-[450px] overflow-y-auto">
                    <?php if (empty($factPendientes)): ?>
                    <div class="text-center py-8"><p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('fin_sin_fact_pend'); ?></p></div>
                    <?php else: foreach ($factPendientes as $fp):
                        $vencida = $fp['estado'] === 'vencida' || ($fp['fecha_vencimiento'] && strtotime($fp['fecha_vencimiento']) < time());
                        $diasRestantes = $fp['fecha_vencimiento'] ? (int)((strtotime($fp['fecha_vencimiento']) - time()) / 86400) : null;
                    ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors">
                        <div class="w-1 h-10 rounded-full shrink-0 <?php echo $vencida ? 'bg-red-500' : 'bg-amber-500'; ?>"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold truncate dark:text-white text-gray-900"><?php echo htmlspecialchars($fp['cn']); ?></p>
                            <p class="text-[10px] dark:text-white/35 text-gray-400"><?php echo $fp['numero']; ?></p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-bold dark:text-white text-gray-900">$<?php echo number_format($fp['total'], 0, ',', '.'); ?></p>
                            <p class="text-[10px] font-medium <?php echo $vencida ? 'text-red-400' : 'text-amber-400'; ?>">
                                <?php if ($vencida): ?><?php echo __('fin_vencida'); ?><?php elseif ($diasRestantes !== null): ?><?php echo $diasRestantes; ?>d<?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Quick donut -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="text-sm font-bold dark:text-white text-gray-900 mb-3"><?php echo __('fin_distribucion'); ?></h3>
                <div class="flex items-center justify-center mb-3">
                    <div class="relative w-32 h-32">
                        <canvas id="donutChart"></canvas>
                        <div class="absolute inset-0 flex items-center justify-center flex-col">
                            <p class="text-sm font-bold <?php echo $balance >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>">
                                <?php echo $margen; ?>%
                            </p>
                            <p class="text-[9px] dark:text-white/30 text-gray-400"><?php echo __('fin_margen'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-emerald-500"></div><span class="dark:text-white/50 text-gray-500"><?php echo __('fin_ingresos'); ?></span></div>
                        <span class="font-semibold text-emerald-400">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-red-400"></div><span class="dark:text-white/50 text-gray-500"><?php echo __('fin_gastos'); ?></span></div>
                        <span class="font-semibold text-red-400">$<?php echo number_format($gastosMes, 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== TAB: GASTOS ========== -->
    <div x-show="tab === 'gastos'" class="space-y-3">
        <!-- Filter pills -->
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[10px] font-semibold uppercase tracking-wider dark:text-white/25 text-gray-400"><?php echo __('fin_filtrar'); ?></span>
            <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>" class="text-[10px] px-2.5 py-1 rounded-lg font-medium transition-colors <?php echo !$filtroCategoria ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 hover:dark:bg-white/10'; ?>"><?php echo __('filtro_todos'); ?></a>
            <?php foreach ($categoriasGasto as $cg): ?>
            <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>&cat=<?php echo urlencode($cg['nombre']); ?>"
               class="text-[10px] px-2.5 py-1 rounded-lg font-medium transition-colors flex items-center gap-1 <?php echo $filtroCategoria === $cg['nombre'] ? 'text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 hover:dark:bg-white/10'; ?>"
               <?php if ($filtroCategoria === $cg['nombre']): ?>style="background:<?php echo $cg['color']; ?>"<?php endif; ?>>
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $cg['color']; ?>"></span>
                <?php echo htmlspecialchars($cg['nombre']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Table -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="dark:bg-white/[0.02] bg-gray-50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_fecha'); ?></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_descripcion'); ?></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_categoria'); ?></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider hidden sm:table-cell"><?php echo __('fin_registrado_por'); ?></th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_monto'); ?></th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-white/[0.04] divide-gray-50">
                        <?php if (empty($gastos)): ?>
                        <tr><td colspan="6" class="px-4 py-8 text-center text-xs dark:text-white/30 text-gray-400">Sin gastos<?php echo $filtroCategoria ? " en \"$filtroCategoria\"" : ''; ?></td></tr>
                        <?php else: foreach ($gastos as $g):
                            $catInfo = $catMap[$g['categoria']] ?? null;
                            $catColor = $catInfo ? $catInfo['color'] : '#6b7280';
                        ?>
                        <tr class="dark:hover:bg-white/[0.02] hover:bg-gray-50 transition-colors group">
                            <td class="px-4 py-3 text-xs dark:text-white/50 text-gray-500 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($g['fecha'])); ?></td>
                            <td class="px-4 py-3 font-medium text-sm"><?php echo htmlspecialchars($g['descripcion']); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-lg font-medium" style="background:<?php echo $catColor; ?>15; color:<?php echo $catColor; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $catColor; ?>"></span>
                                    <?php echo htmlspecialchars($g['categoria']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs dark:text-white/40 text-gray-400 hidden sm:table-cell"><?php echo htmlspecialchars($g['usuario_nombre'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-right font-bold text-red-400 whitespace-nowrap">-$<?php echo number_format($g['monto'], 2, ',', '.'); ?></td>
                            <td class="px-4 py-3">
                                <button @click="deleteTransaction('gasto', <?php echo $g['id']; ?>)" class="w-7 h-7 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500/10 transition-all" title="Eliminar">
                                    <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== TAB: INGRESOS ========== -->
    <div x-show="tab === 'ingresos'" class="space-y-3">
        <!-- Filter pills -->
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[10px] font-semibold uppercase tracking-wider dark:text-white/25 text-gray-400"><?php echo __('fin_metodo'); ?></span>
            <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>" class="text-[10px] px-2.5 py-1 rounded-lg font-medium transition-colors <?php echo !$filtroMetodo ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 hover:dark:bg-white/10'; ?>"><?php echo __('filtro_todos'); ?></a>
            <?php foreach ($metodosConfig as $mk => $mv): ?>
            <a href="?mes=<?php echo $mesVer; ?>&anio=<?php echo $anioVer; ?>&metodo=<?php echo $mk; ?>"
               class="text-[10px] px-2.5 py-1 rounded-lg font-medium transition-colors flex items-center gap-1 <?php echo $filtroMetodo === $mk ? 'text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 hover:dark:bg-white/10'; ?>"
               <?php if ($filtroMetodo === $mk): ?>style="background:<?php echo $mv['color']; ?>"<?php endif; ?>>
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?php echo $mv['color']; ?>"></span>
                <?php echo $mv['label']; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Table -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="dark:bg-white/[0.02] bg-gray-50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_descripcion'); ?></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_cliente'); ?></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider hidden sm:table-cell"><?php echo __('fin_metodo'); ?></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider hidden sm:table-cell"><?php echo __('fin_factura'); ?></th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider"><?php echo __('fin_monto'); ?></th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-white/[0.04] divide-gray-50">
                        <?php if (empty($ingresos)): ?>
                        <tr><td colspan="7" class="px-4 py-8 text-center text-xs dark:text-white/30 text-gray-400">Sin ingresos<?php echo $filtroMetodo ? " con \"$filtroMetodo\"" : ''; ?></td></tr>
                        <?php else: foreach ($ingresos as $ing):
                            $mc = $metodosConfig[$ing['metodo_pago']] ?? ['label'=>ucfirst($ing['metodo_pago']),'color'=>'#6b7280'];
                        ?>
                        <tr class="dark:hover:bg-white/[0.02] hover:bg-gray-50 transition-colors group">
                            <td class="px-4 py-3 text-xs dark:text-white/50 text-gray-500 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                            <td class="px-4 py-3 font-medium text-sm"><?php echo htmlspecialchars($ing['descripcion']); ?></td>
                            <td class="px-4 py-3 text-xs dark:text-white/50 text-gray-500"><?php echo $ing['cliente_nombre'] ? htmlspecialchars($ing['cliente_nombre']) : '<span class="dark:text-white/20 text-gray-300">—</span>'; ?></td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-lg font-medium" style="background:<?php echo $mc['color']; ?>15; color:<?php echo $mc['color']; ?>">
                                    <?php echo $mc['label']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs dark:text-white/40 text-gray-400 hidden sm:table-cell"><?php echo $ing['factura_num'] ?: '—'; ?></td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-400 whitespace-nowrap">+$<?php echo number_format($ing['monto'], 2, ',', '.'); ?></td>
                            <td class="px-4 py-3">
                                <button @click="deleteTransaction('ingreso', <?php echo $ing['id']; ?>)" class="w-7 h-7 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500/10 transition-all" title="Eliminar">
                                    <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: NUEVO GASTO ========== -->
    <div x-show="showGasto" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showGasto = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
            <div class="h-1 bg-red-500"></div>
            <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('fin_nuevo_gasto'); ?></h3>
                <button @click="showGasto = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center"><svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_descripcion'); ?> *</label>
                    <input type="text" x-model="gForm.descripcion" placeholder="Ej: Hosting Mensual" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_monto'); ?> *</label>
                        <input type="number" x-model="gForm.monto" step="0.01" min="0" placeholder="0.00" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_categoria'); ?></label>
                        <select x-model="gForm.categoria" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                            <?php foreach ($categoriasGasto as $cg): ?>
                            <option value="<?php echo htmlspecialchars($cg['nombre']); ?>"><?php echo htmlspecialchars($cg['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_fecha'); ?></label>
                        <input type="date" x-model="gForm.fecha" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_recurrente'); ?></label>
                        <select x-model="gForm.frecuencia" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value="unico"><?php echo __('fin_unico'); ?></option>
                            <option value="mensual"><?php echo __('fin_mensual'); ?></option>
                            <option value="anual"><?php echo __('fin_anual'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 border-t dark:border-white/[0.06] border-gray-100 flex gap-3">
                <button @click="showGasto = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                <button @click="saveGasto()" :disabled="!gForm.descripcion.trim() || !gForm.monto || saving" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500 hover:bg-red-600 disabled:opacity-40 transition-colors flex items-center justify-center gap-2">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <?php echo __('fin_registrar_gasto'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: NUEVO INGRESO ========== -->
    <div x-show="showIngreso" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showIngreso = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
            <div class="h-1 bg-emerald-500"></div>
            <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('fin_nuevo_ingreso'); ?></h3>
                <button @click="showIngreso = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center"><svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_descripcion'); ?> *</label>
                    <input type="text" x-model="iForm.descripcion" placeholder="Ej: Pago servicio web" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_monto'); ?> *</label>
                        <input type="number" x-model="iForm.monto" step="0.01" min="0" placeholder="0.00" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_metodo_pago', 'Método de Pago'); ?></label>
                        <select x-model="iForm.metodo_pago" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value="transferencia"><?php echo __('fin_transferencia', 'Transferencia'); ?></option>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                            <option value="efectivo"><?php echo __('fin_efectivo', 'Efectivo'); ?></option>
                            <option value="crypto">Crypto</option>
                            <option value="otro"><?php echo __('fin_otro', 'Otro'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_fecha'); ?></label>
                        <input type="date" x-model="iForm.fecha" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('fin_cliente'); ?></label>
                        <select x-model="iForm.cliente_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value=""><?php echo __('fin_ninguno', 'Ninguno'); ?></option>
                            <?php foreach ($clientes as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 border-t dark:border-white/[0.06] border-gray-100 flex gap-3">
                <button @click="showIngreso = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                <button @click="saveIngreso()" :disabled="!iForm.descripcion.trim() || !iForm.monto || saving" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 transition-colors flex items-center justify-center gap-2">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <?php echo __('fin_registrar_ingreso'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: CATEGORÍAS ========== -->
    <div x-show="showCatModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showCatModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('fin_gestionar_cat'); ?></h3>
                <button @click="showCatModal = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center"><svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-4 max-h-[60vh] overflow-y-auto space-y-2">
                <?php foreach ($categoriasAll as $ca): ?>
                <div class="flex items-center gap-3 p-2.5 rounded-xl dark:bg-white/[0.03] bg-gray-50">
                    <span class="w-3 h-3 rounded-full shrink-0" style="background:<?php echo $ca['color']; ?>"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($ca['nombre']); ?></p>
                        <p class="text-[10px] dark:text-white/30 text-gray-400 capitalize"><?php echo $ca['tipo']; ?></p>
                    </div>
                    <button @click="deleteCat(<?php echo $ca['id']; ?>)" class="w-6 h-6 rounded-lg flex items-center justify-center hover:bg-red-500/10 transition-colors" title="Eliminar">
                        <svg class="w-3 h-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Add new category -->
            <div class="p-4 border-t dark:border-white/[0.06] border-gray-100">
                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400 mb-2"><?php echo __('fin_nueva_cat'); ?></p>
                <div class="flex items-center gap-2">
                    <input type="text" x-model="newCat.nombre" placeholder="<?php echo __('fin_nombre'); ?>" class="flex-1 px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                    <select x-model="newCat.tipo" class="px-2 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none">
                        <option value="gasto"><?php echo __('fin_gasto'); ?></option>
                        <option value="ingreso"><?php echo __('fin_ingreso'); ?></option>
                        <option value="ambos"><?php echo __('fin_ambos'); ?></option>
                    </select>
                    <input type="color" x-model="newCat.color" class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0">
                    <button @click="saveCat()" :disabled="!newCat.nombre.trim()" class="w-8 h-8 rounded-lg bg-nexo-600 hover:bg-nexo-700 disabled:opacity-40 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== TOAST ========== -->
    <div x-show="toast" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-1 translate-y-0" x-transition:leave="transition ease-in duration-200" x-cloak class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl dark:bg-dark-700 bg-white border dark:border-white/10 border-gray-200 shadow-2xl">
        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium dark:text-white text-gray-900" x-text="toast"></span>
    </div>
</div>
</main>

<script>
function finanzasApp() {
    return {
        tab: '<?php echo $filtroCategoria ? 'gastos' : ($filtroMetodo ? 'ingresos' : 'resumen'); ?>',
        showGasto: false,
        showIngreso: false,
        showCatModal: false,
        saving: false,
        toast: '',
        gForm: { descripcion: '', monto: '', categoria: 'Otros', fecha: '<?php echo date('Y-m-d'); ?>', frecuencia: 'unico' },
        iForm: { descripcion: '', monto: '', metodo_pago: 'transferencia', fecha: '<?php echo date('Y-m-d'); ?>', cliente_id: '' },
        newCat: { nombre: '', tipo: 'gasto', color: '#7c3aed' },

        openGasto() {
            this.gForm = { descripcion: '', monto: '', categoria: 'Otros', fecha: '<?php echo date('Y-m-d'); ?>', frecuencia: 'unico' };
            this.showGasto = true;
        },
        openIngreso() {
            this.iForm = { descripcion: '', monto: '', metodo_pago: 'transferencia', fecha: '<?php echo date('Y-m-d'); ?>', cliente_id: '' };
            this.showIngreso = true;
        },

        async saveGasto() {
            if (!this.gForm.descripcion.trim() || !this.gForm.monto) return;
            this.saving = true;
            try {
                const fd = new FormData();
                fd.append('action', 'gasto');
                fd.append('descripcion', this.gForm.descripcion.trim());
                fd.append('monto', this.gForm.monto);
                fd.append('categoria', this.gForm.categoria);
                fd.append('fecha', this.gForm.fecha);
                fd.append('frecuencia', this.gForm.frecuencia);
                const r = await fetch('api/finanzas.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) { this.showGasto = false; this.showToast('Gasto registrado'); setTimeout(() => location.reload(), 800); }
                else alert(data.error || 'Error');
            } catch(e) { console.error(e); alert('Error de conexión'); }
            this.saving = false;
        },

        async saveIngreso() {
            if (!this.iForm.descripcion.trim() || !this.iForm.monto) return;
            this.saving = true;
            try {
                const fd = new FormData();
                fd.append('action', 'ingreso');
                fd.append('descripcion', this.iForm.descripcion.trim());
                fd.append('monto', this.iForm.monto);
                fd.append('metodo_pago', this.iForm.metodo_pago);
                fd.append('fecha', this.iForm.fecha);
                fd.append('cliente_id', this.iForm.cliente_id);
                const r = await fetch('api/finanzas.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) { this.showIngreso = false; this.showToast('Ingreso registrado'); setTimeout(() => location.reload(), 800); }
                else alert(data.error || 'Error');
            } catch(e) { console.error(e); alert('Error de conexión'); }
            this.saving = false;
        },

        async deleteTransaction(tipo, id) {
            if (!confirm('¿Eliminar ' + (tipo === 'gasto' ? 'este gasto' : 'este ingreso') + '?')) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete_' + tipo);
                fd.append('id', id);
                const r = await fetch('api/finanzas.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) { this.showToast('Eliminado'); setTimeout(() => location.reload(), 600); }
            } catch(e) { console.error(e); }
        },

        async saveCat() {
            if (!this.newCat.nombre.trim()) return;
            try {
                const fd = new FormData();
                fd.append('action', 'create_categoria');
                fd.append('nombre', this.newCat.nombre.trim());
                fd.append('tipo', this.newCat.tipo);
                fd.append('color', this.newCat.color);
                const r = await fetch('api/finanzas.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) { this.showToast('Categoría creada'); setTimeout(() => location.reload(), 600); }
                else alert(data.error || 'Error');
            } catch(e) { console.error(e); }
        },

        async deleteCat(id) {
            if (!confirm('¿Eliminar esta categoría?')) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete_categoria');
                fd.append('id', id);
                const r = await fetch('api/finanzas.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) { this.showToast('Categoría eliminada'); setTimeout(() => location.reload(), 600); }
            } catch(e) { console.error(e); }
        },

        showToast(msg) { this.toast = msg; setTimeout(() => this.toast = '', 3000); },
    };
}

document.addEventListener('DOMContentLoaded', function(){
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';
    const textColor = isDark ? 'rgba(255,255,255,0.35)' : 'rgba(0,0,0,0.4)';

    new Chart(document.getElementById('finChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [
                { label: 'Ingresos', data: <?php echo json_encode($chartIngresos); ?>, backgroundColor: 'rgba(52,211,153,0.7)', borderRadius: 6, barPercentage: 0.5, categoryPercentage: 0.6 },
                { label: 'Gastos', data: <?php echo json_encode(array_map(fn($v)=>-$v, $chartGastos)); ?>, backgroundColor: 'rgba(248,113,113,0.7)', borderRadius: 6, barPercentage: 0.5, categoryPercentage: 0.6 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1a1726' : '#fff',
                    titleColor: isDark ? '#fff' : '#111', bodyColor: isDark ? 'rgba(255,255,255,0.7)' : '#555',
                    borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)', borderWidth: 1,
                    cornerRadius: 12, padding: 12,
                    callbacks: { label: (c) => (c.raw < 0 ? 'Gasto: $' : 'Ingreso: $') + Math.abs(c.raw).toLocaleString() }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 }, callback: v => '$' + Math.abs(v).toLocaleString() } }
            }
        }
    });

    const donutEl = document.getElementById('donutChart');
    if (donutEl) {
        new Chart(donutEl, {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Gastos'],
                datasets: [{ data: [<?php echo $ingresosMes; ?>, <?php echo $gastosMes; ?>], backgroundColor: ['#22c55e', '#ef4444'], borderWidth: 0, cutout: '75%', borderRadius: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
        });
    }
});
</script>
<?php include 'includes/footer.php'; ?>
