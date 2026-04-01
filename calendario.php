<?php
require_once 'includes/auth_check.php';
$pageTitle = __('cal_titulo');
$currentPage = 'calendario';
$uid = $_SESSION['user_id'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// Tipo config
$tipoConfig = [
    'reunion'      => ['label'=>__('cal_reunion'),      'bg'=>'bg-nexo-500/10',    'text'=>'text-nexo-400',    'dot'=>'bg-nexo-500'],
    'llamada'      => ['label'=>__('cal_llamada'),       'bg'=>'bg-blue-500/10',    'text'=>'text-blue-400',    'dot'=>'bg-blue-500'],
    'tarea'        => ['label'=>__('cal_tarea'),         'bg'=>'bg-emerald-500/10', 'text'=>'text-emerald-400', 'dot'=>'bg-emerald-500'],
    'recordatorio' => ['label'=>__('cal_recordatorio'),  'bg'=>'bg-amber-500/10',   'text'=>'text-amber-400',   'dot'=>'bg-amber-500'],
    'seguimiento'  => ['label'=>__('cal_seguimiento'),   'bg'=>'bg-cyan-500/10',    'text'=>'text-cyan-400',    'dot'=>'bg-cyan-500'],
    'entrega'      => ['label'=>__('cal_entrega'),       'bg'=>'bg-green-500/10',   'text'=>'text-green-400',   'dot'=>'bg-green-500'],
    'evento'       => ['label'=>__('cal_evento'),        'bg'=>'bg-purple-500/10',  'text'=>'text-purple-400',  'dot'=>'bg-purple-500'],
    'feriado'      => ['label'=>__('cal_feriado'),       'bg'=>'bg-red-500/10',     'text'=>'text-red-400',     'dot'=>'bg-red-500'],
];

// Color config
$colorConfig = [
    'nexo'    => ['bg'=>'bg-nexo-500/15',    'text'=>'text-nexo-400',    'dot'=>'bg-nexo-500',    'border'=>'border-nexo-500'],
    'blue'    => ['bg'=>'bg-blue-500/15',    'text'=>'text-blue-400',    'dot'=>'bg-blue-500',    'border'=>'border-blue-500'],
    'emerald' => ['bg'=>'bg-emerald-500/15', 'text'=>'text-emerald-400', 'dot'=>'bg-emerald-500', 'border'=>'border-emerald-500'],
    'red'     => ['bg'=>'bg-red-500/15',     'text'=>'text-red-400',     'dot'=>'bg-red-500',     'border'=>'border-red-500'],
    'amber'   => ['bg'=>'bg-amber-500/15',   'text'=>'text-amber-400',   'dot'=>'bg-amber-500',   'border'=>'border-amber-500'],
    'cyan'    => ['bg'=>'bg-cyan-500/15',    'text'=>'text-cyan-400',    'dot'=>'bg-cyan-500',    'border'=>'border-cyan-500'],
];

// Get events for this month (user sees own + assigned to them)
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$stmt = $pdo->prepare("
    SELECT e.*, c.nombre as cliente_nombre, u.nombre as usuario_nombre, a.nombre as asignado_nombre
    FROM eventos e
    LEFT JOIN clientes c ON e.cliente_id = c.id
    LEFT JOIN usuarios u ON u.id = e.usuario_id
    LEFT JOIN usuarios a ON a.id = e.asignado_a
    WHERE ((MONTH(fecha_inicio) = :m AND YEAR(fecha_inicio) = :y)
       OR (MONTH(fecha_fin) = :m2 AND YEAR(fecha_fin) = :y2))
    " . (!$isAdmin ? " AND (e.usuario_id = :uid OR e.asignado_a = :uid2)" : "") . "
    ORDER BY fecha_inicio
");
$params = ['m'=>$mes,'y'=>$anio,'m2'=>$mes,'y2'=>$anio];
if (!$isAdmin) { $params['uid'] = $uid; $params['uid2'] = $uid; }
$stmt->execute($params);
$eventos = $stmt->fetchAll();

// Build events by day (supporting multi-day events)
$eventosPorDia = [];
foreach ($eventos as $ev) {
    $startDate = new DateTime($ev['fecha_inicio']);
    $endDate = $ev['fecha_fin'] ? new DateTime($ev['fecha_fin']) : clone $startDate;
    for ($dt = clone $startDate; $dt <= $endDate; $dt->modify('+1 day')) {
        if ((int)$dt->format('m') == $mes && (int)$dt->format('Y') == $anio) {
            $eventosPorDia[(int)$dt->format('j')][] = $ev;
        }
    }
}

// Stats
$hoy = date('Y-m-d');
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$finSemana = date('Y-m-d', strtotime('sunday this week'));
$totalMes = count($eventos);

$stmtHoy = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE DATE(fecha_inicio) = :d");
$stmtHoy->execute(['d'=>$hoy]);
$eventosHoy = (int)$stmtHoy->fetchColumn();

$stmtSemana = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE DATE(fecha_inicio) BETWEEN :s AND :e");
$stmtSemana->execute(['s'=>$inicioSemana, 'e'=>$finSemana]);
$eventosSemana = (int)$stmtSemana->fetchColumn();

$stmtTareas = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE tipo = 'tarea' AND fecha_inicio >= CURDATE()");
$stmtTareas->execute();
$tareasPend = (int)$stmtTareas->fetchColumn();

// Upcoming events (sidebar)
$proximos = $pdo->query("
    SELECT e.*, c.nombre as cliente_nombre
    FROM eventos e
    LEFT JOIN clientes c ON e.cliente_id = c.id
    WHERE e.fecha_inicio >= CURDATE()
    ORDER BY e.fecha_inicio LIMIT 10
")->fetchAll();

// Today's events
$stmtHoyEvs = $pdo->prepare("
    SELECT e.*, c.nombre as cliente_nombre
    FROM eventos e
    LEFT JOIN clientes c ON e.cliente_id = c.id
    WHERE DATE(e.fecha_inicio) = :d
    ORDER BY e.fecha_inicio
");
$stmtHoyEvs->execute(['d'=>$hoy]);
$eventosDeHoy = $stmtHoyEvs->fetchAll();

// Clients
$clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll();

// Users (for assigning events)
$usuarios = $pdo->query("SELECT id, nombre, rol FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Calendar calculations
$primerDia = mktime(0, 0, 0, $mes, 1, $anio);
$diasEnMes = (int)date('t', $primerDia);
$diaInicio = (int)date('w', $primerDia); // 0=Sun
$_idioma = $_idioma ?? 'es';
if ($_idioma === 'pt') {
    $meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    $mesesCorto = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    $diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
    $diasCorto = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
} elseif ($_idioma === 'en') {
    $meses = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    $mesesCorto = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $diasSemana = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $diasCorto = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
} else {
    $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $mesesCorto = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $diasCorto = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
}

$prevM = $mes - 1; $prevY = $anio;
if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $mes + 1; $nextY = $anio;
if ($nextM > 12) { $nextM = 1; $nextY++; }

$esMesActual = ($mes == (int)date('m') && $anio == (int)date('Y'));
$hoyDia = (int)date('j');
$prevMonthDays = (int)date('t', mktime(0,0,0, $mes - 1, 1, $anio));

// Events as JSON for Alpine modals
$eventosJson = json_encode(array_map(function($ev) use ($tipoConfig) {
    return [
        'id' => (int)$ev['id'],
        'titulo' => $ev['titulo'],
        'descripcion' => $ev['descripcion'] ?? '',
        'tipo' => $ev['tipo'],
        'tipo_label' => $tipoConfig[$ev['tipo']]['label'] ?? ucfirst($ev['tipo']),
        'color' => $ev['color'] ?? 'nexo',
        'fecha_inicio' => $ev['fecha_inicio'],
        'fecha_fin' => $ev['fecha_fin'],
        'todo_el_dia' => (bool)($ev['todo_el_dia'] ?? false),
        'cliente_id' => $ev['cliente_id'] ? (int)$ev['cliente_id'] : null,
        'cliente_nombre' => $ev['cliente_nombre'] ?? null,
        'usuario_nombre' => $ev['usuario_nombre'] ?? null,
        'usuario_id' => (int)$ev['usuario_id'],
        'asignado_a' => $ev['asignado_a'] ? (int)$ev['asignado_a'] : null,
        'asignado_nombre' => $ev['asignado_nombre'] ?? null,
    ];
}, $eventos));

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-5" x-data="calendarApp()" x-cloak>

    <!-- ========== HEADER ========== -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-nexo-500/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold dark:text-white text-gray-900"><?php echo __('cal_titulo'); ?></h1>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('cal_subtitulo'); ?></p>
            </div>
        </div>
        <button @click="openCreate()" class="px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-nexo-600 hover:bg-nexo-700 transition-colors shadow-lg shadow-nexo-600/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?php echo __('cal_nuevo_evento'); ?>
        </button>
    </div>

    <!-- ========== KPI CARDS ========== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Este Mes -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-nexo-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold dark:text-white text-gray-900"><?php echo $totalMes; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('cal_este_mes'); ?></p>
                </div>
            </div>
        </div>
        <!-- Hoy -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold dark:text-white text-gray-900"><?php echo $eventosHoy; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('cal_hoy'); ?></p>
                </div>
            </div>
        </div>
        <!-- Esta Semana -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold dark:text-white text-gray-900"><?php echo $eventosSemana; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('cal_esta_semana'); ?></p>
                </div>
            </div>
        </div>
        <!-- Tareas Pendientes -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold dark:text-white text-gray-900"><?php echo $tareasPend; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('cal_tareas_pend'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MAIN LAYOUT ========== -->
    <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-5">

        <!-- ===== CALENDAR GRID ===== -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
            <!-- Month nav bar -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b dark:border-white/[0.06] border-gray-200">
                <div class="flex items-center gap-2">
                    <a href="?mes=<?php echo $prevM; ?>&anio=<?php echo $prevY; ?>" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h2 class="text-base font-bold px-2 dark:text-white text-gray-900"><?php echo $meses[$mes] . ' ' . $anio; ?></h2>
                    <a href="?mes=<?php echo $nextM; ?>&anio=<?php echo $nextY; ?>" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <a href="?mes=<?php echo (int)date('m'); ?>&anio=<?php echo (int)date('Y'); ?>" class="px-3 py-1.5 text-xs font-medium rounded-lg dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors dark:text-white/60 text-gray-600"><?php echo __('cal_hoy'); ?></a>
            </div>

            <!-- Day headers -->
            <div class="grid grid-cols-7 dark:bg-dark-900/50 bg-gray-50">
                <?php foreach ($diasCorto as $idx => $d): ?>
                <div class="px-2 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider <?php echo ($idx === 0 || $idx === 6) ? 'dark:text-white/25 text-gray-300' : 'dark:text-white/40 text-gray-400'; ?>"><?php echo $d; ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Day cells -->
            <div class="grid grid-cols-7">
                <?php
                // Previous month trailing days
                for ($i = 0; $i < $diaInicio; $i++):
                    $prevDay = $prevMonthDays - $diaInicio + $i + 1;
                ?>
                <div class="min-h-[90px] sm:min-h-[110px] p-1.5 border-b border-r dark:border-white/[0.04] border-gray-100 dark:bg-dark-900/20 bg-gray-50/50">
                    <span class="text-[11px] dark:text-white/15 text-gray-300"><?php echo $prevDay; ?></span>
                </div>
                <?php endfor;

                for ($d = 1; $d <= $diasEnMes; $d++):
                    $esHoy = $esMesActual && $d === $hoyDia;
                    $evsDia = $eventosPorDia[$d] ?? [];
                    $dow = ($diaInicio + $d - 1) % 7;
                    $isWeekend = ($dow === 0 || $dow === 6);
                ?>
                <div class="min-h-[90px] sm:min-h-[110px] p-1.5 border-b border-r dark:border-white/[0.04] border-gray-100 transition-colors cursor-pointer group <?php echo $esHoy ? 'dark:bg-nexo-500/[0.04] bg-nexo-50/40' : ($isWeekend ? 'dark:bg-dark-900/20 bg-gray-50/30' : 'dark:hover:bg-white/[0.02] hover:bg-gray-50'); ?>"
                     @click="openDay(<?php echo $d; ?>)">
                    <div class="flex items-center justify-between mb-1">
                        <?php if ($esHoy): ?>
                        <span class="w-6 h-6 rounded-full bg-nexo-500 text-white text-[11px] font-bold flex items-center justify-center shadow-sm shadow-nexo-500/30"><?php echo $d; ?></span>
                        <?php else: ?>
                        <span class="text-[11px] font-medium <?php echo $isWeekend ? 'dark:text-white/30 text-gray-400' : 'dark:text-white/60 text-gray-500'; ?>"><?php echo $d; ?></span>
                        <?php endif; ?>
                        <?php if (count($evsDia) > 0): ?>
                        <span class="text-[9px] font-medium dark:text-white/20 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"><?php echo count($evsDia); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php foreach (array_slice($evsDia, 0, 3) as $ev):
                        $cc = $colorConfig[$ev['color'] ?? 'nexo'] ?? $colorConfig['nexo'];
                    ?>
                    <div class="text-[10px] truncate px-1.5 py-[3px] rounded-md mb-0.5 font-medium flex items-center gap-1 <?php echo $cc['bg'] . ' ' . $cc['text']; ?>"
                         @click.stop="openEvent(<?php echo $ev['id']; ?>)">
                        <span class="w-1 h-1 rounded-full shrink-0 <?php echo $cc['dot']; ?>"></span>
                        <span class="truncate"><?php echo htmlspecialchars($ev['titulo']); ?></span>
                    </div>
                    <?php endforeach;
                    if (count($evsDia) > 3): ?>
                    <span class="text-[9px] font-medium dark:text-white/25 text-gray-400 pl-1">+<?php echo count($evsDia) - 3; ?> <?php echo __('cal_mas'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endfor;

                // Next month filling
                $remaining = (7 - ($diaInicio + $diasEnMes) % 7) % 7;
                for ($i = 1; $i <= $remaining; $i++):
                ?>
                <div class="min-h-[90px] sm:min-h-[110px] p-1.5 border-b border-r dark:border-white/[0.04] border-gray-100 dark:bg-dark-900/20 bg-gray-50/50">
                    <span class="text-[11px] dark:text-white/15 text-gray-300"><?php echo $i; ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- ===== SIDEBAR ===== -->
        <div class="space-y-4">

            <!-- Today card -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b dark:border-white/[0.06] border-gray-100 flex items-center gap-3">
                    <div class="w-12 h-14 rounded-xl bg-gradient-to-b from-nexo-500 to-nexo-700 flex flex-col items-center justify-center text-white shadow-lg shadow-nexo-600/20">
                        <span class="text-[9px] font-semibold uppercase leading-none"><?php echo $mesesCorto[(int)date('m')]; ?></span>
                        <span class="text-xl font-bold leading-tight"><?php echo date('d'); ?></span>
                    </div>
                    <div>
                        <p class="text-sm font-bold dark:text-white text-gray-900"><?php echo $diasSemana[(int)date('w')]; ?></p>
                        <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo $eventosHoy; ?> evento<?php echo $eventosHoy !== 1 ? 's' : ''; ?> hoy</p>
                    </div>
                </div>
                <div class="p-3">
                    <?php if (empty($eventosDeHoy)): ?>
                    <div class="text-center py-4">
                        <div class="w-10 h-10 mx-auto rounded-xl dark:bg-white/5 bg-gray-100 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/></svg>
                        </div>
                        <p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('cal_sin_eventos_hoy'); ?></p>
                    </div>
                    <?php else: foreach ($eventosDeHoy as $ev):
                        $tc = $tipoConfig[$ev['tipo'] ?? 'evento'] ?? $tipoConfig['evento'];
                        $cc = $colorConfig[$ev['color'] ?? 'nexo'] ?? $colorConfig['nexo'];
                    ?>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors cursor-pointer"
                         @click="openEvent(<?php echo $ev['id']; ?>)">
                        <div class="w-1 h-9 rounded-full shrink-0 <?php echo $cc['dot']; ?>"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold truncate dark:text-white text-gray-900"><?php echo htmlspecialchars($ev['titulo']); ?></p>
                            <p class="text-[10px] dark:text-white/35 text-gray-400"><?php echo date('H:i', strtotime($ev['fecha_inicio'])); ?><?php echo $ev['fecha_fin'] ? ' - ' . date('H:i', strtotime($ev['fecha_fin'])) : ''; ?></p>
                        </div>
                        <span class="text-[9px] px-1.5 py-0.5 rounded-md <?php echo $tc['bg'] . ' ' . $tc['text']; ?> font-medium"><?php echo $tc['label']; ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold dark:text-white text-gray-900"><?php echo __('cal_proximos'); ?></h3>
                    <span class="text-[10px] px-2 py-0.5 rounded-full dark:bg-white/5 bg-gray-100 dark:text-white/40 text-gray-400 font-medium"><?php echo count($proximos); ?></span>
                </div>
                <div class="p-2 max-h-[420px] overflow-y-auto">
                    <?php if (empty($proximos)): ?>
                    <div class="text-center py-6">
                        <p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('cal_sin_proximos'); ?></p>
                    </div>
                    <?php else: foreach ($proximos as $ev):
                        $tc = $tipoConfig[$ev['tipo'] ?? 'evento'] ?? $tipoConfig['evento'];
                        $cc = $colorConfig[$ev['color'] ?? 'nexo'] ?? $colorConfig['nexo'];
                        $evDate = strtotime($ev['fecha_inicio']);
                        $evDay = date('d', $evDate);
                        $evMon = $mesesCorto[(int)date('m', $evDate)];
                        $evTime = date('H:i', $evDate);
                        $isToday = date('Y-m-d', $evDate) === $hoy;
                        $isTomorrow = date('Y-m-d', $evDate) === date('Y-m-d', strtotime('+1 day'));
                    ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-xl dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors cursor-pointer"
                         @click="openEvent(<?php echo $ev['id']; ?>)">
                        <div class="w-10 h-10 rounded-lg dark:bg-white/5 bg-gray-100 flex flex-col items-center justify-center shrink-0">
                            <span class="text-[8px] font-semibold uppercase dark:text-white/30 text-gray-400 leading-none"><?php echo $evMon; ?></span>
                            <span class="text-sm font-bold dark:text-white/80 text-gray-700 leading-tight"><?php echo $evDay; ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold truncate dark:text-white text-gray-900"><?php echo htmlspecialchars($ev['titulo']); ?></p>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] dark:text-white/35 text-gray-400"><?php echo $evTime; ?></span>
                                <?php if ($ev['cliente_nombre']): ?>
                                <span class="text-[10px] dark:text-white/20 text-gray-300">·</span>
                                <span class="text-[10px] dark:text-white/35 text-gray-400 truncate"><?php echo htmlspecialchars($ev['cliente_nombre']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isToday): ?>
                        <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-nexo-500/15 text-nexo-400 font-semibold"><?php echo __('cal_hoy'); ?></span>
                        <?php elseif ($isTomorrow): ?>
                        <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-blue-500/15 text-blue-400 font-medium"><?php echo __('cal_manana'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Type legend -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400 mb-2.5"><?php echo __('cal_tipos_evento'); ?></p>
                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
                    <?php foreach ($tipoConfig as $tk => $tv): ?>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full <?php echo $tv['dot']; ?>"></span>
                        <span class="text-[11px] dark:text-white/50 text-gray-500"><?php echo $tv['label']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: DAY DETAIL ========== -->
    <div x-show="showDayModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDayModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-nexo-500/10 flex items-center justify-center">
                        <span class="text-sm font-bold text-nexo-400" x-text="selectedDay"></span>
                    </div>
                    <div>
                        <p class="text-sm font-bold dark:text-white text-gray-900" x-text="dayTitle"></p>
                        <p class="text-[11px] dark:text-white/40 text-gray-400" x-text="dayEvents.length + ' evento' + (dayEvents.length !== 1 ? 's' : '')"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="showDayModal = false; openCreate(selectedDay)" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <button @click="showDayModal = false" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-3 max-h-[400px] overflow-y-auto">
                <template x-if="dayEvents.length === 0">
                    <div class="text-center py-8">
                        <div class="w-12 h-12 mx-auto rounded-xl dark:bg-white/5 bg-gray-100 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('cal_sin_eventos_dia'); ?></p>
                        <button @click="showDayModal = false; openCreate(selectedDay)" class="mt-3 text-xs text-nexo-400 hover:text-nexo-300 font-medium"><?php echo __('cal_agregar_evento'); ?></button>
                    </div>
                </template>
                <template x-for="ev in dayEvents" :key="ev.id">
                    <div class="flex items-center gap-3 p-3 rounded-xl dark:hover:bg-white/[0.04] hover:bg-gray-50 transition-colors cursor-pointer"
                         @click="showDayModal = false; openEvent(ev.id)">
                        <div class="w-1 h-10 rounded-full shrink-0" :class="colorDot(ev.color)"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate dark:text-white text-gray-900" x-text="ev.titulo"></p>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] dark:text-white/40 text-gray-400" x-text="formatTime(ev.fecha_inicio) + (ev.fecha_fin ? ' - ' + formatTime(ev.fecha_fin) : '')"></span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-md font-medium" :class="tipoBg(ev.tipo) + ' ' + tipoText(ev.tipo)" x-text="ev.tipo_label"></span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 dark:text-white/20 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: EVENT DETAIL ========== -->
    <div x-show="showEventModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showEventModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
            <template x-if="selectedEvent">
                <div>
                    <!-- Color bar -->
                    <div class="h-1.5" :class="colorDot(selectedEvent.color)"></div>
                    <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                        <h3 class="text-base font-bold dark:text-white text-gray-900 truncate pr-4" x-text="selectedEvent.titulo"></h3>
                        <div class="flex items-center gap-1">
                            <button @click="openEdit()" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors" title="Editar">
                                <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="deleteEvent()" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center hover:bg-red-500/10 transition-colors" title="Eliminar">
                                <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <button @click="showEventModal = false" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                                <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Tipo badge -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2.5 py-1 rounded-lg font-medium" :class="tipoBg(selectedEvent.tipo) + ' ' + tipoText(selectedEvent.tipo)" x-text="selectedEvent.tipo_label"></span>
                            <span class="w-3 h-3 rounded-full" :class="colorDot(selectedEvent.color)"></span>
                        </div>
                        <!-- Date/time -->
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium dark:text-white text-gray-900" x-text="formatDate(selectedEvent.fecha_inicio)"></p>
                                <p class="text-xs dark:text-white/40 text-gray-400" x-text="formatTime(selectedEvent.fecha_inicio) + (selectedEvent.fecha_fin ? ' - ' + formatTime(selectedEvent.fecha_fin) : '')"></p>
                            </div>
                        </div>
                        <!-- Client -->
                        <div x-show="selectedEvent.cliente_nombre" class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium dark:text-white text-gray-900" x-text="selectedEvent.cliente_nombre"></p>
                                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('cal_cliente_vinculado'); ?></p>
                            </div>
                        </div>
                        <!-- Description -->
                        <div x-show="selectedEvent.descripcion" class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            </div>
                            <p class="text-sm dark:text-white/70 text-gray-600 whitespace-pre-wrap" x-text="selectedEvent.descripcion"></p>
                        </div>
                        <!-- Assigned to -->
                        <div x-show="selectedEvent.asignado_nombre" class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-nexo-500/10 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm dark:text-white/70 text-gray-600" x-text="selectedEvent.asignado_nombre"></p>
                                <p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('cal_asignado_a'); ?></p>
                            </div>
                        </div>
                        <!-- Creator -->
                        <div x-show="selectedEvent.usuario_nombre" class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm dark:text-white/70 text-gray-600" x-text="selectedEvent.usuario_nombre"></p>
                                <p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('cal_creado_por'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========== MODAL: CREATE / EDIT EVENT ========== -->
    <div x-show="showCreateModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showCreateModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold dark:text-white text-gray-900" x-text="form.id ? '<?php echo __('cal_editar_evento'); ?>' : '<?php echo __('cal_nuevo_evento'); ?>'"></h3>
                <button @click="showCreateModal = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <!-- Titulo -->
                <div>
                    <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_titulo_evento'); ?> *</label>
                    <input type="text" x-model="form.titulo" placeholder="<?php echo __('cal_nombre_placeholder'); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                </div>
                <!-- Tipo + Cliente -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_tipo'); ?></label>
                        <select x-model="form.tipo" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value="reunion"><?php echo __('cal_reunion'); ?></option>
                            <option value="llamada"><?php echo __('cal_llamada'); ?></option>
                            <option value="tarea"><?php echo __('cal_tarea'); ?></option>
                            <option value="recordatorio"><?php echo __('cal_recordatorio'); ?></option>
                            <option value="seguimiento"><?php echo __('cal_seguimiento'); ?></option>
                            <option value="entrega"><?php echo __('cal_entrega'); ?></option>
                            <option value="evento"><?php echo __('cal_evento'); ?></option>
                            <option value="feriado"><?php echo __('cal_feriado'); ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_cliente'); ?></label>
                        <select x-model="form.cliente_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value=""><?php echo __('cal_ninguno'); ?></option>
                            <?php foreach ($clientes as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Asignar a -->
                <div>
                    <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_asignar_a'); ?></label>
                    <select x-model="form.asignado_a" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
                        <option value=""><?php echo __('cal_yo_mismo'); ?></option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?> (<?php echo $u['rol']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Todo el dia toggle -->
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" x-model="form.todo_el_dia" class="sr-only peer">
                        <div class="w-9 h-5 rounded-full dark:bg-white/10 bg-gray-200 peer-checked:bg-nexo-600 transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></div>
                    </div>
                    <span class="text-xs font-medium dark:text-white/60 text-gray-600"><?php echo __('cal_todo_dia'); ?></span>
                </label>
                <!-- Dates -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_fecha_inicio'); ?> *</label>
                        <input :type="form.todo_el_dia ? 'date' : 'datetime-local'" x-model="form.fecha_inicio" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                    <div>
                        <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_fecha_fin'); ?></label>
                        <input :type="form.todo_el_dia ? 'date' : 'datetime-local'" x-model="form.fecha_fin" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                </div>
                <!-- Color picker -->
                <div>
                    <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_color'); ?></label>
                    <div class="flex gap-2">
                        <?php
                        $colores = ['nexo'=>'bg-nexo-500','blue'=>'bg-blue-500','emerald'=>'bg-emerald-500','red'=>'bg-red-500','amber'=>'bg-amber-500','cyan'=>'bg-cyan-500'];
                        foreach ($colores as $ck => $ccl): ?>
                        <button type="button" @click="form.color = '<?php echo $ck; ?>'" class="w-8 h-8 rounded-full <?php echo $ccl; ?> transition-all" :class="form.color === '<?php echo $ck; ?>' ? 'ring-2 ring-offset-2 dark:ring-offset-dark-800 ring-offset-white ring-white/50 scale-110 opacity-100' : 'opacity-40 hover:opacity-70'"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Descripcion -->
                <div>
                    <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block"><?php echo __('cal_descripcion'); ?></label>
                    <textarea x-model="form.descripcion" rows="3" placeholder="<?php echo __('cal_detalles_placeholder'); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 resize-none transition-colors"></textarea>
                </div>
            </div>
            <!-- Actions -->
            <div class="px-5 py-4 border-t dark:border-white/[0.06] border-gray-100 flex gap-3">
                <button @click="showCreateModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors dark:text-white/70 text-gray-600"><?php echo __('btn_cancelar'); ?></button>
                <button @click="saveEvent()" :disabled="!form.titulo.trim() || !form.fecha_inicio || saving" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-nexo-600 hover:bg-nexo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="form.id ? '<?php echo __('cal_guardar_cambios'); ?>' : '<?php echo __('cal_crear_evento'); ?>'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========== TOAST ========== -->
    <div x-show="toast" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-1 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-1" x-transition:leave-end="opacity-0" class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl dark:bg-dark-700 bg-white border dark:border-white/10 border-gray-200 shadow-2xl" x-cloak>
        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium dark:text-white text-gray-900" x-text="toast"></span>
    </div>
</div>

<script>
function calendarApp() {
    const tipoMap = {
        reunion:      { label: <?php echo json_encode(__('cal_reunion')); ?>,      bg: 'bg-nexo-500/10',    text: 'text-nexo-400',    dot: 'bg-nexo-500' },
        llamada:      { label: <?php echo json_encode(__('cal_llamada')); ?>,       bg: 'bg-blue-500/10',    text: 'text-blue-400',    dot: 'bg-blue-500' },
        tarea:        { label: <?php echo json_encode(__('cal_tarea')); ?>,         bg: 'bg-emerald-500/10', text: 'text-emerald-400', dot: 'bg-emerald-500' },
        recordatorio: { label: <?php echo json_encode(__('cal_recordatorio')); ?>,  bg: 'bg-amber-500/10',   text: 'text-amber-400',   dot: 'bg-amber-500' },
        seguimiento:  { label: <?php echo json_encode(__('cal_seguimiento')); ?>,   bg: 'bg-cyan-500/10',    text: 'text-cyan-400',    dot: 'bg-cyan-500' },
        entrega:      { label: <?php echo json_encode(__('cal_entrega')); ?>,       bg: 'bg-green-500/10',   text: 'text-green-400',   dot: 'bg-green-500' },
        evento:       { label: <?php echo json_encode(__('cal_evento')); ?>,        bg: 'bg-purple-500/10',  text: 'text-purple-400',  dot: 'bg-purple-500' },
        feriado:      { label: <?php echo json_encode(__('cal_feriado')); ?>,       bg: 'bg-red-500/10',     text: 'text-red-400',     dot: 'bg-red-500' },
    };
    const colorDots = {
        nexo:    'bg-nexo-500',    blue:    'bg-blue-500',    emerald: 'bg-emerald-500',
        red:     'bg-red-500',     amber:   'bg-amber-500',   cyan:    'bg-cyan-500',
    };
    const meses = <?php echo json_encode($meses); ?>;

    return {
        eventos: <?php echo $eventosJson; ?>,
        mes: <?php echo $mes; ?>,
        anio: <?php echo $anio; ?>,
        showDayModal: false,
        showEventModal: false,
        showCreateModal: false,
        selectedDay: null,
        selectedEvent: null,
        dayEvents: [],
        dayTitle: '',
        saving: false,
        toast: '',
        form: { id: null, titulo: '', tipo: 'reunion', fecha_inicio: '', fecha_fin: '', color: 'nexo', descripcion: '', cliente_id: '', asignado_a: '', todo_el_dia: false },

        tipoBg(tipo) { return (tipoMap[tipo] || tipoMap.evento).bg; },
        tipoText(tipo) { return (tipoMap[tipo] || tipoMap.evento).text; },
        colorDot(color) { return colorDots[color] || colorDots.nexo; },

        formatTime(dt) {
            if (!dt) return '';
            return dt.substring(11, 16);
        },
        formatDate(dt) {
            if (!dt) return '';
            const d = new Date(dt);
            return d.getDate() + ' de ' + meses[d.getMonth() + 1] + ' ' + d.getFullYear();
        },

        openDay(day) {
            this.selectedDay = day;
            const dateStr = `${this.anio}-${String(this.mes).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            this.dayEvents = this.eventos.filter(ev => {
                const start = ev.fecha_inicio.substring(0, 10);
                const end = ev.fecha_fin ? ev.fecha_fin.substring(0, 10) : start;
                return dateStr >= start && dateStr <= end;
            });
            this.dayTitle = day + ' de ' + meses[this.mes] + ' ' + this.anio;
            this.showDayModal = true;
        },

        openEvent(id) {
            this.selectedEvent = this.eventos.find(e => e.id === id);
            if (this.selectedEvent) this.showEventModal = true;
        },

        openCreate(day) {
            const d = day || new Date().getDate();
            const dateStr = `${this.anio}-${String(this.mes).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            this.form = {
                id: null, titulo: '', tipo: 'reunion',
                fecha_inicio: dateStr + 'T09:00',
                fecha_fin: dateStr + 'T10:00',
                color: 'nexo', descripcion: '', cliente_id: '', asignado_a: '', todo_el_dia: false
            };
            this.showCreateModal = true;
        },

        openEdit() {
            const ev = this.selectedEvent;
            if (!ev) return;
            this.form = {
                id: ev.id,
                titulo: ev.titulo,
                tipo: ev.tipo,
                fecha_inicio: ev.fecha_inicio ? ev.fecha_inicio.replace(' ', 'T').substring(0, 16) : '',
                fecha_fin: ev.fecha_fin ? ev.fecha_fin.replace(' ', 'T').substring(0, 16) : '',
                color: ev.color || 'nexo',
                descripcion: ev.descripcion || '',
                cliente_id: ev.cliente_id || '',
                asignado_a: ev.asignado_a || '',
                todo_el_dia: ev.todo_el_dia || false,
            };
            this.showEventModal = false;
            this.showCreateModal = true;
        },

        async saveEvent() {
            if (!this.form.titulo.trim() || !this.form.fecha_inicio) return;
            this.saving = true;
            try {
                const fd = new FormData();
                fd.append('action', this.form.id ? 'update' : 'create');
                if (this.form.id) fd.append('id', this.form.id);
                fd.append('titulo', this.form.titulo.trim());
                fd.append('tipo', this.form.tipo);
                fd.append('fecha_inicio', this.form.fecha_inicio.replace('T', ' '));
                fd.append('fecha_fin', this.form.fecha_fin ? this.form.fecha_fin.replace('T', ' ') : '');
                fd.append('color', this.form.color);
                fd.append('descripcion', this.form.descripcion);
                fd.append('cliente_id', this.form.cliente_id);
                fd.append('asignado_a', this.form.asignado_a);
                fd.append('todo_el_dia', this.form.todo_el_dia ? '1' : '0');
                const r = await fetch('api/calendario.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) {
                    this.showCreateModal = false;
                    this.showToast(this.form.id ? 'Evento actualizado' : 'Evento creado');
                    setTimeout(() => location.reload(), 800);
                } else {
                    alert(data.error || 'Error al guardar');
                }
            } catch(e) { console.error(e); alert('Error de conexión'); }
            this.saving = false;
        },

        async deleteEvent() {
            if (!this.selectedEvent) return;
            if (!confirm('¿Eliminar este evento?')) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', this.selectedEvent.id);
                const r = await fetch('api/calendario.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) {
                    this.showEventModal = false;
                    this.showToast('Evento eliminado');
                    setTimeout(() => location.reload(), 800);
                }
            } catch(e) { console.error(e); }
        },

        showToast(msg) {
            this.toast = msg;
            setTimeout(() => this.toast = '', 3000);
        },
    };
}
</script>
</main>
<?php include 'includes/footer.php'; ?>
