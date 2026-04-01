<?php
require_once 'includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pageTitle = 'Permisos';
$currentPage = 'permisos';

// Handle update
$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permisos'])) {
    foreach ($_POST['permisos'] as $id => $perms) {
        $pdo->prepare("UPDATE permisos SET puede_ver=:v, puede_crear=:c, puede_editar=:e, puede_eliminar=:d WHERE id=:id")
            ->execute([
                'v' => isset($perms['ver']) ? 1 : 0,
                'c' => isset($perms['crear']) ? 1 : 0,
                'e' => isset($perms['editar']) ? 1 : 0,
                'd' => isset($perms['eliminar']) ? 1 : 0,
                'id' => (int)$id
            ]);
    }
    $saved = true;
}

$permisos = $pdo->query("SELECT * FROM permisos ORDER BY rol, modulo")->fetchAll();
$roles = array_unique(array_column($permisos, 'rol'));
$modulos = array_unique(array_column($permisos, 'modulo'));

// Stats
$totalPerms = count($permisos);
$activePerms = 0;
foreach ($permisos as $p) {
    $activePerms += ($p['puede_ver'] + $p['puede_crear'] + $p['puede_editar'] + $p['puede_eliminar']);
}
$totalPossible = $totalPerms * 4;
$pctActive = $totalPossible > 0 ? round(($activePerms / $totalPossible) * 100) : 0;

// Role configs
$roleConfig = [
    'admin'    => ['label'=>'Administrador','desc'=>'Acceso completo al sistema','color'=>'nexo','bg'=>'bg-nexo-500/10','text'=>'text-nexo-400','border'=>'border-nexo-500/20','dot'=>'bg-nexo-500','icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    'gerente'  => ['label'=>'Gerente','desc'=>'Gestión de equipos y reportes','color'=>'blue','bg'=>'bg-blue-500/10','text'=>'text-blue-400','border'=>'border-blue-500/20','dot'=>'bg-blue-500','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    'vendedor' => ['label'=>'Vendedor','desc'=>'Gestión de clientes y ventas','color'=>'emerald','bg'=>'bg-emerald-500/10','text'=>'text-emerald-400','border'=>'border-emerald-500/20','dot'=>'bg-emerald-500','icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    'soporte'  => ['label'=>'Soporte','desc'=>'Atención y soporte técnico','color'=>'amber','bg'=>'bg-amber-500/10','text'=>'text-amber-400','border'=>'border-amber-500/20','dot'=>'bg-amber-500','icon'=>'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
];

// Module icons
$moduloConfig = [
    'clientes'   => ['icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','label'=>'Clientes'],
    'pipeline'   => ['icon'=>'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7','label'=>'Pipeline'],
    'facturas'   => ['icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','label'=>'Facturas'],
    'finanzas'   => ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','label'=>'Finanzas'],
    'usuarios'   => ['icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z','label'=>'Usuarios'],
    'calendario' => ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','label'=>'Calendario'],
    'chat'       => ['icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z','label'=>'Chat'],
    'boveda'     => ['icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','label'=>'Bóveda'],
    'servicios'  => ['icon'=>'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z','label'=>'Servicios'],
    'avisos'     => ['icon'=>'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z','label'=>'Avisos'],
    'actividad'  => ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','label'=>'Actividad'],
    'backup'     => ['icon'=>'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10','label'=>'Respaldo'],
    'ajustes'    => ['icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0','label'=>'Ajustes'],
    'permisos'   => ['icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z','label'=>'Permisos'],
];

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto" x-data="{
    saved: <?php echo $saved ? 'true' : 'false'; ?>,
    toast: <?php echo $saved ? 'true' : 'false'; ?>,
    init() {
        if (this.toast) setTimeout(() => this.toast = false, 3500);
    },
    toggleAll(roleId, checked) {
        document.querySelectorAll('[data-role=&quot;' + roleId + '&quot;] input[type=checkbox]').forEach(cb => cb.checked = checked);
    }
}">
<?php include 'includes/topbar.php'; ?>

<!-- Toast notification -->
<div x-show="toast" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="fixed top-20 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 backdrop-blur-sm shadow-lg" x-cloak>
    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="text-sm font-medium text-emerald-400">Permisos guardados correctamente</span>
</div>

<div class="p-4 sm:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-nexo-500/10 border border-nexo-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold dark:text-white text-gray-900">Control de Permisos</h1>
                <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5">Administra el acceso de cada rol a los módulos del sistema</p>
            </div>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-nexo-500/10 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs dark:text-white/40 text-gray-400">Roles</p>
                    <p class="text-lg font-bold dark:text-white text-gray-900"><?php echo count($roles); ?></p>
                </div>
            </div>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </div>
                <div>
                    <p class="text-xs dark:text-white/40 text-gray-400">Módulos</p>
                    <p class="text-lg font-bold dark:text-white text-gray-900"><?php echo count($modulos); ?></p>
                </div>
            </div>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs dark:text-white/40 text-gray-400">Activos</p>
                    <p class="text-lg font-bold dark:text-white text-gray-900"><?php echo $activePerms; ?><span class="text-xs font-normal dark:text-white/30 text-gray-400"> / <?php echo $totalPossible; ?></span></p>
                </div>
            </div>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <p class="text-xs dark:text-white/40 text-gray-400">Cobertura</p>
                    <p class="text-lg font-bold dark:text-white text-gray-900"><?php echo $pctActive; ?>%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission matrix -->
    <form method="POST" class="space-y-5">
    <?php foreach ($roles as $rol):
        $rc = $roleConfig[$rol] ?? ['label'=>ucfirst($rol),'desc'=>'','color'=>'gray','bg'=>'bg-gray-500/10','text'=>'text-gray-400','border'=>'border-gray-500/20','dot'=>'bg-gray-500','icon'=>'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'];
        // Count active for this role
        $roleActive = 0; $roleTotal = 0;
        foreach ($permisos as $p) {
            if ($p['rol'] === $rol) {
                $roleActive += ($p['puede_ver'] + $p['puede_crear'] + $p['puede_editar'] + $p['puede_eliminar']);
                $roleTotal += 4;
            }
        }
        $rolePct = $roleTotal > 0 ? round(($roleActive / $roleTotal) * 100) : 0;
    ?>
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden" data-role="<?php echo htmlspecialchars($rol); ?>">
        <!-- Role header -->
        <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl <?php echo $rc['bg']; ?> border <?php echo $rc['border']; ?> flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 <?php echo $rc['text']; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php echo $rc['icon']; ?>"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-sm dark:text-white text-gray-900"><?php echo $rc['label']; ?></h3>
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full <?php echo $rc['bg']; ?> <?php echo $rc['text']; ?>"><?php echo $roleActive; ?>/<?php echo $roleTotal; ?></span>
                        </div>
                        <p class="text-xs dark:text-white/30 text-gray-400 mt-0.5"><?php echo $rc['desc']; ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Progress bar -->
                    <div class="hidden sm:flex items-center gap-2">
                        <div class="w-24 h-1.5 rounded-full dark:bg-white/5 bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full <?php echo str_replace('/10','', $rc['bg']); ?> transition-all" style="width:<?php echo $rolePct; ?>%"></div>
                        </div>
                        <span class="text-xs dark:text-white/30 text-gray-400"><?php echo $rolePct; ?>%</span>
                    </div>
                    <!-- Quick actions -->
                    <button type="button" onclick="toggleAll('<?php echo htmlspecialchars($rol); ?>', true)" class="text-[11px] font-medium px-2.5 py-1 rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Todos</button>
                    <button type="button" onclick="toggleAll('<?php echo htmlspecialchars($rol); ?>', false)" class="text-[11px] font-medium px-2.5 py-1 rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Ninguno</button>
                </div>
            </div>
        </div>

        <!-- Permission table -->
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b dark:border-white/[0.06] border-gray-100">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400">Módulo</th>
                    <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400">
                        <div class="flex flex-col items-center gap-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>Ver</span>
                        </div>
                    </th>
                    <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400">
                        <div class="flex flex-col items-center gap-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Crear</span>
                        </div>
                    </th>
                    <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400">
                        <div class="flex flex-col items-center gap-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Editar</span>
                        </div>
                    </th>
                    <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400">
                        <div class="flex flex-col items-center gap-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Eliminar</span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php $i=0; foreach ($permisos as $p): if ($p['rol'] !== $rol) continue; $i++;
                $mc = $moduloConfig[$p['modulo']] ?? ['icon'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z','label'=>ucfirst($p['modulo'])];
                $rowPerms = $p['puede_ver'] + $p['puede_crear'] + $p['puede_editar'] + $p['puede_eliminar'];
            ?>
            <tr class="border-b dark:border-white/[0.04] border-gray-50 dark:hover:bg-white/[0.02] hover:bg-gray-50/50 transition-colors group">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <svg class="w-3.5 h-3.5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php echo $mc['icon']; ?>"/></svg>
                        </div>
                        <div>
                            <span class="text-sm font-medium dark:text-white/80 text-gray-700"><?php echo htmlspecialchars($mc['label']); ?></span>
                            <div class="flex gap-0.5 mt-0.5">
                                <?php for($d=0;$d<4;$d++): ?>
                                <div class="w-1 h-1 rounded-full <?php echo $d < $rowPerms ? $rc['dot'] : 'dark:bg-white/10 bg-gray-200'; ?>"></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <?php foreach (['ver'=>'puede_ver','crear'=>'puede_crear','editar'=>'puede_editar','eliminar'=>'puede_eliminar'] as $key=>$col): ?>
                <td class="text-center px-3 py-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="permisos[<?php echo $p['id']; ?>][<?php echo $key; ?>]" <?php echo $p[$col] ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-8 h-[18px] rounded-full dark:bg-white/10 bg-gray-200 peer-checked:bg-<?php echo $rc['color']; ?>-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-[14px] after:w-[14px] after:transition-all peer-checked:after:translate-x-[14px] after:shadow-sm transition-colors"></div>
                    </label>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Submit -->
    <div class="flex items-center justify-between dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 px-5 py-4">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-xs dark:text-white/30 text-gray-400">Los cambios en permisos se aplican inmediatamente al guardar</p>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-nexo-600 hover:bg-nexo-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-colors shadow-lg shadow-nexo-600/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Guardar Permisos
        </button>
    </div>
    </form>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-4 text-xs dark:text-white/25 text-gray-400 pt-2">
        <span class="font-medium dark:text-white/40 text-gray-500">Roles:</span>
        <?php foreach ($roles as $rol):
            $rc = $roleConfig[$rol] ?? ['label'=>ucfirst($rol),'dot'=>'bg-gray-500'];
        ?>
        <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full <?php echo $rc['dot']; ?>"></span>
            <span><?php echo $rc['label']; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleAll(role, checked) {
    document.querySelectorAll('[data-role="' + role + '"] input[type=checkbox]').forEach(cb => {
        cb.checked = checked;
    });
}
</script>
</main>
<?php include 'includes/footer.php'; ?>
