<?php
require_once 'includes/auth_check.php';
require_once 'includes/helpers.php';
$pageTitle = __('nav_pipeline');
$currentPage = 'pipeline';

$isAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';
$userId  = $_SESSION['usuario_id'];

$etapas = $pdo->query("SELECT * FROM pipeline_etapas ORDER BY orden")->fetchAll();

// Admin sees all, others see only their assigned clients
if ($isAdmin) {
    $clientes = $pdo->query("
        SELECT c.id, c.nombre, c.empresa, c.email, c.estado, c.telefono, c.creado_en, c.notas, c.foto, c.archivado,
               c.asignado_a, u.nombre as asignado, u.avatar as asignado_avatar
        FROM clientes c 
        LEFT JOIN usuarios u ON c.asignado_a = u.id 
        ORDER BY c.actualizado_en DESC
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT c.id, c.nombre, c.empresa, c.email, c.estado, c.telefono, c.creado_en, c.notas, c.foto, c.archivado,
               c.asignado_a, u.nombre as asignado, u.avatar as asignado_avatar
        FROM clientes c 
        LEFT JOIN usuarios u ON c.asignado_a = u.id 
        WHERE c.asignado_a = :uid
        ORDER BY c.actualizado_en DESC
    ");
    $stmt->execute(['uid' => $userId]);
    $clientes = $stmt->fetchAll();
}

// Active users for assign dropdown (admin only)
$usuarios = [];
if ($isAdmin) {
    $usuarios = $pdo->query("SELECT id, nombre, rol, avatar FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll();
}

$activeClientes = array_filter($clientes, fn($c) => !$c['archivado']);
$totalDeals  = count($activeClientes);
$enPipeline  = count(array_filter($activeClientes, fn($c) => !in_array($c['estado'], ['ganado','perdido'])));
$ganados     = count(array_filter($activeClientes, fn($c) => $c['estado'] === 'ganado'));
$perdidos    = count(array_filter($activeClientes, fn($c) => $c['estado'] === 'perdido'));
$archivados  = count(array_filter($clientes, fn($c) => (int)$c['archivado'] === 1));
$convRate    = $totalDeals > 0 ? round(($ganados / $totalDeals) * 100) : 0;

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="pipelineApp()">

    <!-- KPI Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('pip_total_deals'); ?></p>
            </div>
            <p class="text-2xl font-bold"><?php echo $totalDeals; ?></p>
        </div>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-lg bg-nexo-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-nexo-400 text-nexo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('pip_en_pipeline'); ?></p>
            </div>
            <p class="text-2xl font-bold dark:text-nexo-400 text-nexo-600" x-text="totalPipeline()"><?php echo $enPipeline; ?></p>
        </div>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-emerald-400 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('pip_ganados'); ?></p>
            </div>
            <p class="text-2xl font-bold dark:text-emerald-400 text-emerald-600"><?php echo $ganados; ?></p>
        </div>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-lg bg-red-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-red-400 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('pip_perdidos'); ?></p>
            </div>
            <p class="text-2xl font-bold dark:text-red-400 text-red-600"><?php echo $perdidos; ?></p>
        </div>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-blue-400 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('pip_conversion'); ?></p>
            </div>
            <p class="text-2xl font-bold dark:text-blue-400 text-blue-600"><?php echo $convRate; ?>%</p>
        </div>
        <div class="panel-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4 cursor-pointer" @click="viewTab = 'archived'" :class="viewTab === 'archived' ? 'ring-2 ring-amber-500/40' : ''">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-amber-400 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('pip_archivados'); ?></p>
            </div>
            <p class="text-2xl font-bold dark:text-amber-400 text-amber-600" x-text="archivedCount()"><?php echo $archivados; ?></p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-3">
        <!-- View tabs -->
        <div class="flex items-center dark:bg-white/5 bg-gray-100 rounded-xl p-0.5">
            <button @click="viewTab = 'pipeline'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="viewTab === 'pipeline' ? 'bg-nexo-600 text-white shadow-sm' : 'dark:text-white/50 text-gray-500 hover:dark:text-white/70'">Pipeline</button>
            <button @click="viewTab = 'archived'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1" :class="viewTab === 'archived' ? 'bg-amber-600 text-white shadow-sm' : 'dark:text-white/50 text-gray-500 hover:dark:text-white/70'">
                <?php echo __('pip_archivados'); ?>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full" :class="viewTab === 'archived' ? 'bg-white/20' : 'dark:bg-white/10 bg-gray-200'" x-text="archivedCount()"></span>
            </button>
        </div>

        <!-- Search -->
        <div class="relative flex-1 min-w-[200px] max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="<?php echo __('pip_buscar'); ?>" class="w-full pl-9 pr-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
        </div>

        <?php if ($isAdmin): ?>
        <!-- Filter by user (admin only) -->
        <select x-model="filterUser" class="text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 px-3 py-2 outline-none focus:border-nexo-500/50 dark:text-white/70 text-gray-600">
            <option value=""><?php echo __('pip_todos_agentes'); ?></option>
            <option value="unassigned"><?php echo __('pip_sin_asignar'); ?></option>
            <?php foreach ($usuarios as $u): ?>
            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?> (<?php echo $u['rol']; ?>)</option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <div class="flex items-center gap-2 ml-auto">
            <p class="text-xs dark:text-white/30 text-gray-400 whitespace-nowrap" x-text="filteredCount() + ' de ' + clientes.length + ' deals'"></p>
            <!-- Add client button -->
            <button @click="showAddModal = true" class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-xl bg-nexo-600 hover:bg-nexo-700 text-white transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline"><?php echo __('pip_nuevo_deal'); ?></span>
            </button>
        </div>
    </div>

    <!-- Kanban Board (Pipeline tab) -->
    <div x-show="viewTab === 'pipeline'" class="flex gap-4 overflow-x-auto pb-4 min-h-[65vh]" style="-webkit-overflow-scrolling: touch;">
        <?php foreach ($etapas as $etapa): ?>
        <div class="flex-shrink-0 w-80 flex flex-col"
             @dragover.prevent="onDragOver($event, '<?php echo htmlspecialchars(addslashes($etapa['nombre'])); ?>')" 
             @dragleave="onDragLeave($event, '<?php echo htmlspecialchars(addslashes($etapa['nombre'])); ?>')"
             @drop.prevent="drop($event, '<?php echo htmlspecialchars($etapa['nombre']); ?>')">
            
            <!-- Column header -->
            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" style="background: <?php echo htmlspecialchars($etapa['color']); ?>"></div>
                    <h3 class="text-sm font-semibold"><?php echo htmlspecialchars($etapa['nombre']); ?></h3>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500" 
                          x-text="countByStage('<?php echo htmlspecialchars(addslashes($etapa['nombre'])); ?>')"></span>
                </div>
            </div>

            <!-- Cards container -->
            <div class="flex-1 space-y-2 p-2 rounded-2xl transition-colors duration-200 min-h-[200px]"
                 :class="dragTarget === '<?php echo htmlspecialchars(addslashes($etapa['nombre'])); ?>' ? 'dark:bg-nexo-950/40 bg-nexo-50 border-2 border-dashed dark:border-nexo-500/30 border-nexo-300' : 'dark:bg-dark-900/50 bg-gray-50/50 border dark:border-white/[0.04] border-gray-100'"
                 :id="'col-<?php echo $etapa['id']; ?>'">
                
                <!-- Empty state -->
                <template x-if="clientesByStage('<?php echo htmlspecialchars(addslashes($etapa['nombre'])); ?>').length === 0">
                    <div class="flex flex-col items-center justify-center h-32 text-center">
                        <svg class="w-8 h-8 dark:text-white/10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <p class="text-[10px] dark:text-white/15 text-gray-300"><?php echo __('pip_arrastra'); ?></p>
                    </div>
                </template>

                <template x-for="cli in clientesByStage('<?php echo htmlspecialchars(addslashes($etapa['nombre'])); ?>')" :key="cli.id">
                    <div class="group dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 cursor-grab active:cursor-grabbing hover:shadow-lg transition-all hover:dark:border-white/[0.12] hover:border-gray-300 relative"
                         draggable="true"
                         @dragstart="dragStart($event, cli.id)"
                         @dragend="dragEnd($event)">
                        
                        <!-- Archive X button (always visible top-right) -->
                        <button @click.stop="archiveClient(cli)" class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center rounded-full dark:bg-white/5 bg-gray-100 dark:hover:bg-red-500/20 hover:bg-red-50 transition-colors z-10 opacity-0 group-hover:opacity-100" title="Archivar">
                            <svg class="w-3 h-3 dark:text-white/40 text-gray-400 dark:group-hover:text-red-400 group-hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        <!-- Quick actions row (on hover, below X) -->
                        <div class="absolute top-9 right-2 flex flex-col gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <button @click.stop="openPreview(cli)" class="w-6 h-6 flex items-center justify-center rounded-lg dark:bg-dark-700 bg-white shadow dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Ver">
                                <svg class="w-3 h-3 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <?php if ($isAdmin): ?>
                            <button @click.stop="openAssign(cli)" class="w-6 h-6 flex items-center justify-center rounded-lg dark:bg-dark-700 bg-white shadow dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Reasignar">
                                <svg class="w-3 h-3 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </button>
                            <?php endif; ?>
                            <button @click.stop="openAddNote(cli)" class="w-6 h-6 flex items-center justify-center rounded-lg dark:bg-dark-700 bg-white shadow dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Nota">
                                <svg class="w-3 h-3 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </div>

                        <!-- Header: photo/initial + name -->
                        <div class="flex items-center gap-2.5 mb-2.5 pr-8">
                            <template x-if="cli.foto">
                                <img :src="'uploads/clientes/' + cli.foto" class="w-9 h-9 rounded-lg object-cover shrink-0 border dark:border-white/10 border-gray-200">
                            </template>
                            <template x-if="!cli.foto">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-nexo-500/30 to-purple-600/30 flex items-center justify-center shrink-0 border dark:border-white/10 border-gray-200">
                                    <span class="text-xs font-bold dark:text-nexo-300 text-nexo-600" x-text="(cli.nombre||'?')[0].toUpperCase()"></span>
                                </div>
                            </template>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate" x-text="cli.nombre"></p>
                                <p x-show="cli.empresa" class="text-[11px] dark:text-white/35 text-gray-400 truncate" x-text="cli.empresa"></p>
                            </div>
                        </div>

                        <!-- Contact row -->
                        <div class="flex flex-col gap-1 mb-2">
                            <div x-show="cli.email" class="flex items-center gap-1.5">
                                <svg class="w-3 h-3 dark:text-white/20 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <p class="text-[11px] dark:text-white/30 text-gray-400 truncate" x-text="cli.email"></p>
                            </div>
                            <div x-show="cli.telefono" class="flex items-center gap-1.5">
                                <svg class="w-3 h-3 dark:text-white/20 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <p class="text-[11px] dark:text-white/30 text-gray-400" x-text="cli.telefono"></p>
                            </div>
                        </div>

                        <!-- Note preview -->
                        <div x-show="cli.notas" class="mb-2.5 px-2 py-1.5 rounded-lg dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100">
                            <p class="text-[10px] dark:text-white/25 text-gray-400 line-clamp-2" x-text="cli.notas"></p>
                        </div>

                        <!-- Footer: assigned user + time -->
                        <div class="flex items-center justify-between pt-2.5 border-t dark:border-white/[0.04] border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <template x-if="cli.asignado">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="cli.asignado_avatar">
                                            <img :src="'uploads/avatars/' + cli.asignado_avatar" class="w-5 h-5 rounded-full object-cover" :title="cli.asignado">
                                        </template>
                                        <template x-if="!cli.asignado_avatar">
                                            <div class="w-5 h-5 rounded-full bg-nexo-600/20 flex items-center justify-center" :title="cli.asignado">
                                                <span class="text-[8px] font-bold text-nexo-400" x-text="(cli.asignado||'')[0]?.toUpperCase()"></span>
                                            </div>
                                        </template>
                                        <span class="text-[10px] dark:text-white/35 text-gray-400 truncate max-w-[80px]" x-text="cli.asignado"></span>
                                    </div>
                                </template>
                                <template x-if="!cli.asignado">
                                    <span class="text-[10px] dark:text-amber-400/60 text-amber-500 italic">Sin asignar</span>
                                </template>
                            </div>
                            <span class="text-[10px] dark:text-white/20 text-gray-300" x-text="timeAgo(cli.creado_en)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Archived View -->
    <div x-show="viewTab === 'archived'" x-cloak>
        <template x-if="getArchivedClientes().length === 0">
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-2xl dark:bg-white/5 bg-gray-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <p class="text-sm dark:text-white/30 text-gray-400"><?php echo __('pip_no_archivados'); ?></p>
                <p class="text-xs dark:text-white/15 text-gray-300 mt-1"><?php echo __('pip_archivados_desc'); ?></p>
            </div>
        </template>

        <!-- Archived filter by original stage -->
        <div x-show="getArchivedClientes().length > 0" class="flex flex-wrap gap-2 mb-4">
            <button @click="archiveFilter = ''" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="archiveFilter === '' ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500'">Todos</button>
            <?php foreach ($etapas as $etapa): ?>
            <button @click="archiveFilter = '<?php echo htmlspecialchars(addslashes(strtolower($etapa['nombre']))); ?>'" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                    :class="archiveFilter === '<?php echo htmlspecialchars(addslashes(strtolower($etapa['nombre']))); ?>' ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500'">
                <div class="w-2 h-2 rounded-full" style="background: <?php echo htmlspecialchars($etapa['color']); ?>"></div>
                <?php echo htmlspecialchars($etapa['nombre']); ?>
                <span class="text-[10px]" x-text="archivedByStage('<?php echo htmlspecialchars(addslashes(strtolower($etapa['nombre']))); ?>')"></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Archived cards grid -->
        <div x-show="getArchivedClientes().length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <template x-for="cli in getFilteredArchivedClientes()" :key="cli.id">
                <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 relative group opacity-75 hover:opacity-100 transition-all">
                    <!-- Stage badge -->
                    <div class="absolute top-3 right-3">
                        <span class="text-[9px] font-medium px-2 py-0.5 rounded-full border" :class="stageColor(cli.estado)" x-text="stageLabel(cli.estado)"></span>
                    </div>

                    <!-- Header: photo + name -->
                    <div class="flex items-center gap-2.5 mb-2.5 pr-16">
                        <template x-if="cli.foto">
                            <img :src="'uploads/clientes/' + cli.foto" class="w-9 h-9 rounded-lg object-cover shrink-0 border dark:border-white/10 border-gray-200">
                        </template>
                        <template x-if="!cli.foto">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-nexo-500/20 to-purple-600/20 flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold dark:text-nexo-300/60 text-nexo-600/60" x-text="(cli.nombre||'?')[0].toUpperCase()"></span>
                            </div>
                        </template>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold truncate" x-text="cli.nombre"></p>
                            <p x-show="cli.empresa" class="text-[11px] dark:text-white/30 text-gray-400 truncate" x-text="cli.empresa"></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-2 border-t dark:border-white/[0.04] border-gray-100">
                        <span class="text-[10px] dark:text-white/20 text-gray-300" x-text="timeAgo(cli.creado_en)"></span>
                        <div class="flex items-center gap-1">
                            <button @click="unarchiveClient(cli)" class="px-2 py-1 text-[10px] font-medium rounded-lg bg-nexo-600/10 dark:text-nexo-400 text-nexo-600 hover:bg-nexo-600/20 transition-colors"><?php echo __('pip_restaurar'); ?></button>
                            <button @click="confirmDelete(cli)" class="px-2 py-1 text-[10px] font-medium rounded-lg bg-red-500/10 dark:text-red-400 text-red-600 hover:bg-red-500/20 transition-colors"><?php echo __('btn_eliminar'); ?></button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL: Add New Client -->
    <template x-if="showAddModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showAddModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddModal = false"></div>
            <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold"><?php echo __('pip_nuevo_deal'); ?></h3>
                    <button @click="showAddModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100">
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block"><?php echo __('usr_nombre'); ?> *</label>
                            <input type="text" x-model="newClient.nombre" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50" placeholder="<?php echo __('usr_nombre'); ?>">
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block"><?php echo __('tabla_empresa'); ?></label>
                            <input type="text" x-model="newClient.empresa" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50" placeholder="Nombre empresa">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block"><?php echo __('usr_email'); ?></label>
                            <input type="email" x-model="newClient.email" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50" placeholder="email@ejemplo.com">
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block"><?php echo __('usr_telefono'); ?></label>
                            <input type="text" x-model="newClient.telefono" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50" placeholder="+1 555 1234">
                        </div>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div>
                        <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block"><?php echo __('pip_asignar_a'); ?></label>
                        <select x-model="newClient.asignado_a" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value=""><?php echo __('pip_sin_asignar'); ?></option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block">Notas</label>
                        <textarea x-model="newClient.notas" rows="2" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none" placeholder="<?php echo __('pip_notas_deal'); ?>"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button @click="showAddModal = false" class="px-4 py-2 text-sm rounded-xl dark:hover:bg-white/5 hover:bg-gray-100 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button @click="createClient()" :disabled="!newClient.nombre.trim() || addingClient" class="px-4 py-2 text-sm font-medium rounded-xl bg-nexo-600 hover:bg-nexo-700 text-white transition-colors disabled:opacity-40">
                        <span x-show="!addingClient"><?php echo __('pip_crear_deal'); ?></span>
                        <span x-show="addingClient"><?php echo __('pip_creando'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: Assign Client (admin only) -->
    <?php if ($isAdmin): ?>
    <template x-if="showAssignModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showAssignModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAssignModal = false"></div>
            <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl p-6">
                <h3 class="text-lg font-bold mb-1"><?php echo __('pip_reasignar'); ?></h3>
                <p class="text-xs dark:text-white/40 text-gray-400 mb-4" x-text="'Cliente: ' + (assignTarget?.nombre || '')"></p>
                <label class="text-xs dark:text-white/40 text-gray-500 mb-1 block"><?php echo __('pip_asignar_a'); ?></label>
                <select x-model="assignUserId" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 mb-4">
                    <option value=""><?php echo __('pip_sin_asignar'); ?></option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?> (<?php echo $u['rol']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="flex justify-end gap-2">
                    <button @click="showAssignModal = false" class="px-4 py-2 text-sm rounded-xl dark:hover:bg-white/5 hover:bg-gray-100 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button @click="doAssign()" :disabled="assigning" class="px-4 py-2 text-sm font-medium rounded-xl bg-nexo-600 hover:bg-nexo-700 text-white transition-colors disabled:opacity-40">
                        <span x-show="!assigning"><?php echo __('pip_asignar_a'); ?></span>
                        <span x-show="assigning"><?php echo __('pip_asignando'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
    <?php endif; ?>

    <!-- MODAL: Quick Note -->
    <template x-if="showNoteModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showNoteModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showNoteModal = false"></div>
            <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl p-6">
                <h3 class="text-lg font-bold mb-1"><?php echo __('pip_nota_rapida'); ?></h3>
                <p class="text-xs dark:text-white/40 text-gray-400 mb-4" x-text="noteTarget?.nombre || ''"></p>
                <textarea x-model="noteText" rows="4" class="w-full px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none" placeholder="<?php echo __('pip_escribe_nota'); ?>"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showNoteModal = false" class="px-4 py-2 text-sm rounded-xl dark:hover:bg-white/5 hover:bg-gray-100 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button @click="saveNote()" :disabled="savingNote" class="px-4 py-2 text-sm font-medium rounded-xl bg-nexo-600 hover:bg-nexo-700 text-white transition-colors disabled:opacity-40"><?php echo __('btn_guardar'); ?></button>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: Delete Confirmation (only from archived) -->
    <template x-if="showDeleteModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showDeleteModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
            <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-lg font-bold mb-1"><?php echo __('pip_eliminar_perm'); ?></h3>
                <p class="text-sm dark:text-white/50 text-gray-500 mb-5">Se eliminará <span class="font-semibold" x-text="deleteTarget?.nombre"></span> y todos sus datos asociados.</p>
                <div class="flex justify-center gap-2">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm rounded-xl dark:hover:bg-white/5 hover:bg-gray-100 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button @click="doDelete()" :disabled="deleting" class="px-4 py-2 text-sm font-medium rounded-xl bg-red-600 hover:bg-red-700 text-white transition-colors disabled:opacity-40"><?php echo __('btn_eliminar'); ?></button>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: Client Preview -->
    <template x-if="showPreviewModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showPreviewModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
            <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden">
                <!-- Header with color accent -->
                <div class="relative px-6 pt-6 pb-4">
                    <div class="absolute top-0 left-0 right-0 h-1" :style="'background:' + previewStageColor(previewClient.estado)"></div>
                    <button @click="showPreviewModal = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div class="flex items-center gap-4">
                        <template x-if="previewClient.foto">
                            <img :src="'uploads/clientes/' + previewClient.foto" class="w-14 h-14 rounded-xl object-cover border dark:border-white/10 border-gray-200">
                        </template>
                        <template x-if="!previewClient.foto">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-nexo-500/30 to-purple-600/30 flex items-center justify-center border dark:border-white/10 border-gray-200">
                                <span class="text-xl font-bold dark:text-nexo-300 text-nexo-600" x-text="(previewClient.nombre||'?')[0].toUpperCase()"></span>
                            </div>
                        </template>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-bold truncate" x-text="previewClient.nombre"></h3>
                            <p x-show="previewClient.empresa" class="text-sm dark:text-white/40 text-gray-500 truncate" x-text="previewClient.empresa"></p>
                        </div>
                    </div>
                </div>

                <!-- Info body -->
                <div class="px-6 pb-6 space-y-4">
                    <!-- Stage badge -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border" :class="stageColor(previewClient.estado)" x-text="stageLabel(previewClient.estado)"></span>
                        <span class="text-[10px] dark:text-white/25 text-gray-300" x-text="'Creado ' + timeAgo(previewClient.creado_en)"></span>
                    </div>

                    <!-- Contact details -->
                    <div class="space-y-2.5">
                        <div x-show="previewClient.email" class="flex items-center gap-3 group/item">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] dark:text-white/30 text-gray-400 mb-0.5">Email</p>
                                <p class="text-sm dark:text-white/80 text-gray-700 truncate" x-text="previewClient.email"></p>
                            </div>
                        </div>
                        <div x-show="previewClient.telefono" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] dark:text-white/30 text-gray-400 mb-0.5">Teléfono</p>
                                <p class="text-sm dark:text-white/80 text-gray-700" x-text="previewClient.telefono"></p>
                            </div>
                        </div>
                        <div x-show="previewClient.asignado" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] dark:text-white/30 text-gray-400 mb-0.5">Asignado a</p>
                                <p class="text-sm dark:text-white/80 text-gray-700" x-text="previewClient.asignado"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div x-show="previewClient.notas">
                        <p class="text-[10px] dark:text-white/30 text-gray-400 uppercase tracking-wider mb-1.5">Notas</p>
                        <div class="px-3 py-2.5 rounded-xl dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100">
                            <p class="text-sm dark:text-white/50 text-gray-600 whitespace-pre-line leading-relaxed" x-text="previewClient.notas"></p>
                        </div>
                    </div>

                    <!-- Actions footer -->
                    <div class="flex items-center gap-2 pt-2">
                        <a :href="'cliente_detalle.php?id='+previewClient.id" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-center dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                            Ver Perfil Completo
                        </a>
                        <button @click="showPreviewModal = false" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white text-center">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Toast notification -->
    <div x-show="toast" x-transition.opacity.duration.300ms class="fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-xl text-sm font-medium shadow-lg"
         :class="toastType === 'error' ? 'bg-red-600 text-white' : 'bg-emerald-600 text-white'" x-text="toast"></div>
</div>
</main>

<script>
function pipelineApp() {
    const stageMap = {nuevo:'Nuevo',contactado:'Contactado',negociando:'Negociando',propuesta:'Propuesta',ganado:'Ganado',perdido:'Perdido'};
    const reverseMap = {};
    Object.entries(stageMap).forEach(([k,v]) => reverseMap[v.toLowerCase()] = k);

    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    const currentUserId = <?php echo (int)$userId; ?>;
    const usuarios = <?php echo json_encode($usuarios); ?>;

    return {
        clientes: <?php echo json_encode($clientes); ?>,
        search: '',
        filterUser: '',
        viewTab: 'pipeline',
        archiveFilter: '',
        draggedId: null,
        dragTarget: null,

        // Modals
        showAddModal: false,
        showAssignModal: false,
        showNoteModal: false,
        showDeleteModal: false,
        showPreviewModal: false,
        previewClient: null,
        addingClient: false,
        assigning: false,
        savingNote: false,
        deleting: false,

        // Form data
        newClient: { nombre:'', email:'', telefono:'', empresa:'', notas:'', asignado_a:'' },
        assignTarget: null,
        assignUserId: '',
        noteTarget: null,
        noteText: '',
        deleteTarget: null,

        // Toast
        toast: '',
        toastType: 'ok',

        showToast(msg, type='ok') {
            this.toast = msg;
            this.toastType = type;
            setTimeout(() => this.toast = '', 3000);
        },

        // Stage helpers
        stageLabel(key) {
            return stageMap[key] || key;
        },
        stageColor(key) {
            const colors = {nuevo:'dark:bg-blue-400/10 dark:text-blue-400 bg-blue-50 text-blue-600 border-blue-200 dark:border-blue-400/20',contactado:'dark:bg-amber-400/10 dark:text-amber-400 bg-amber-50 text-amber-600 border-amber-200 dark:border-amber-400/20',negociando:'dark:bg-purple-400/10 dark:text-purple-400 bg-purple-50 text-purple-600 border-purple-200 dark:border-purple-400/20',propuesta:'dark:bg-cyan-400/10 dark:text-cyan-400 bg-cyan-50 text-cyan-600 border-cyan-200 dark:border-cyan-400/20',ganado:'dark:bg-emerald-400/10 dark:text-emerald-400 bg-emerald-50 text-emerald-600 border-emerald-200 dark:border-emerald-400/20',perdido:'dark:bg-red-400/10 dark:text-red-400 bg-red-50 text-red-600 border-red-200 dark:border-red-400/20'};
            return colors[key] || 'dark:bg-gray-400/10 dark:text-gray-400 bg-gray-50 text-gray-600 border-gray-200';
        },

        // Active (non-archived) clients
        getActiveClientes() {
            return this.clientes.filter(c => !parseInt(c.archivado));
        },
        getFilteredClientes() {
            let list = this.getActiveClientes();
            if (this.filterUser === 'unassigned') {
                list = list.filter(c => !c.asignado_a);
            } else if (this.filterUser) {
                list = list.filter(c => String(c.asignado_a) === String(this.filterUser));
            }
            if (this.search) {
                const s = this.search.toLowerCase();
                list = list.filter(c => c.nombre.toLowerCase().includes(s) || (c.empresa||'').toLowerCase().includes(s) || (c.email||'').toLowerCase().includes(s) || (c.asignado||'').toLowerCase().includes(s));
            }
            return list;
        },
        filteredCount() {
            return this.getFilteredClientes().length;
        },
        clientesByStage(stage) {
            const key = reverseMap[stage.toLowerCase()] || stage.toLowerCase();
            return this.getFilteredClientes().filter(c => c.estado === key);
        },
        countByStage(stage) {
            return this.clientesByStage(stage).length;
        },
        totalPipeline() {
            return this.getActiveClientes().filter(c => !['ganado','perdido'].includes(c.estado)).length;
        },

        // Archived clients
        getArchivedClientes() {
            let list = this.clientes.filter(c => parseInt(c.archivado));
            if (this.search) {
                const s = this.search.toLowerCase();
                list = list.filter(c => c.nombre.toLowerCase().includes(s) || (c.empresa||'').toLowerCase().includes(s));
            }
            return list;
        },
        getFilteredArchivedClientes() {
            let list = this.getArchivedClientes();
            if (this.archiveFilter) {
                list = list.filter(c => c.estado === this.archiveFilter);
            }
            return list;
        },
        archivedCount() {
            return this.clientes.filter(c => parseInt(c.archivado)).length;
        },
        archivedByStage(stage) {
            const key = reverseMap[stage] || stage;
            return this.clientes.filter(c => parseInt(c.archivado) && c.estado === key).length;
        },

        // --- Drag & Drop ---
        dragStart(e, id) {
            this.draggedId = id;
            e.target.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        },
        dragEnd(e) {
            e.target.style.opacity = '1';
            this.dragTarget = null;
        },
        onDragOver(e, stage) {
            e.dataTransfer.dropEffect = 'move';
            this.dragTarget = stage;
        },
        onDragLeave(e, stage) {
            if (!e.currentTarget.contains(e.relatedTarget)) this.dragTarget = null;
        },
        async drop(e, stageName) {
            this.dragTarget = null;
            if (!this.draggedId) return;
            const newEstado = reverseMap[stageName.toLowerCase()] || stageName.toLowerCase();
            const cli = this.clientes.find(c => c.id == this.draggedId);
            if (!cli || cli.estado === newEstado) { this.draggedId = null; return; }

            const oldEstado = cli.estado;
            cli.estado = newEstado;

            const fd = new FormData();
            fd.append('action', 'update_estado');
            fd.append('id', this.draggedId);
            fd.append('estado', newEstado);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (!d.ok) { cli.estado = oldEstado; this.showToast(d.error || 'Error', 'error'); }
                else { this.showToast('Movido a ' + stageName); }
            } catch(err) { cli.estado = oldEstado; this.showToast('Error de red', 'error'); }
            this.draggedId = null;
        },

        // --- Create Client ---
        async createClient() {
            if (!this.newClient.nombre.trim()) return;
            this.addingClient = true;
            const fd = new FormData();
            fd.append('action', 'create');
            fd.append('nombre', this.newClient.nombre.trim());
            fd.append('email', this.newClient.email.trim());
            fd.append('telefono', this.newClient.telefono.trim());
            fd.append('empresa', this.newClient.empresa.trim());
            fd.append('notas', this.newClient.notas.trim());
            if (this.newClient.asignado_a) fd.append('asignado_a', this.newClient.asignado_a);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    const assignedUser = this.newClient.asignado_a ? usuarios.find(u => u.id == this.newClient.asignado_a) : null;
                    this.clientes.push({
                        id: d.id, nombre: this.newClient.nombre.trim(), email: this.newClient.email.trim(),
                        telefono: this.newClient.telefono.trim(), empresa: this.newClient.empresa.trim(),
                        notas: this.newClient.notas.trim(), estado: 'nuevo', creado_en: new Date().toISOString(),
                        asignado_a: this.newClient.asignado_a || null,
                        asignado: assignedUser ? assignedUser.nombre : null,
                        asignado_avatar: assignedUser ? assignedUser.avatar : null,
                        foto: null, archivado: 0
                    });
                    this.newClient = { nombre:'', email:'', telefono:'', empresa:'', notas:'', asignado_a:'' };
                    this.showAddModal = false;
                    this.showToast('Deal creado');
                } else { this.showToast(d.error || 'Error', 'error'); }
            } catch(err) { this.showToast('Error de red', 'error'); }
            this.addingClient = false;
        },

        // --- Assign ---
        openAssign(cli) {
            this.assignTarget = cli;
            this.assignUserId = cli.asignado_a || '';
            this.showAssignModal = true;
        },
        async doAssign() {
            if (!this.assignTarget) return;
            this.assigning = true;
            const fd = new FormData();
            fd.append('action', 'assign');
            fd.append('id', this.assignTarget.id);
            fd.append('asignado_a', this.assignUserId);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    const cli = this.clientes.find(c => c.id == this.assignTarget.id);
                    if (cli) {
                        cli.asignado_a = this.assignUserId || null;
                        const u = usuarios.find(u => u.id == this.assignUserId);
                        cli.asignado = u ? u.nombre : null;
                        cli.asignado_avatar = u ? u.avatar : null;
                    }
                    this.showAssignModal = false;
                    this.showToast('Cliente reasignado');
                } else { this.showToast(d.error || 'Error', 'error'); }
            } catch(err) { this.showToast('Error de red', 'error'); }
            this.assigning = false;
        },

        // --- Quick Note ---
        openAddNote(cli) {
            this.noteTarget = cli;
            this.noteText = cli.notas || '';
            this.showNoteModal = true;
        },
        async saveNote() {
            if (!this.noteTarget) return;
            this.savingNote = true;
            const fd = new FormData();
            fd.append('action', 'update_note');
            fd.append('id', this.noteTarget.id);
            fd.append('notas', this.noteText);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    const cli = this.clientes.find(c => c.id == this.noteTarget.id);
                    if (cli) cli.notas = this.noteText;
                    this.showNoteModal = false;
                    this.showToast('Nota guardada');
                } else { this.showToast(d.error || 'Error', 'error'); }
            } catch(err) { this.showToast('Error de red', 'error'); }
            this.savingNote = false;
        },

        // --- Archive / Unarchive ---
        async archiveClient(cli) {
            const fd = new FormData();
            fd.append('action', 'archive');
            fd.append('id', cli.id);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    const c = this.clientes.find(c => c.id == cli.id);
                    if (c) c.archivado = 1;
                    this.showToast('Archivado');
                } else { this.showToast(d.error || 'Error', 'error'); }
            } catch(err) { this.showToast('Error de red', 'error'); }
        },
        async unarchiveClient(cli) {
            const fd = new FormData();
            fd.append('action', 'unarchive');
            fd.append('id', cli.id);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    const c = this.clientes.find(c => c.id == cli.id);
                    if (c) c.archivado = 0;
                    this.showToast('Restaurado al pipeline');
                } else { this.showToast(d.error || 'Error', 'error'); }
            } catch(err) { this.showToast('Error de red', 'error'); }
        },

        // --- Preview ---
        openPreview(cli) {
            this.previewClient = cli;
            this.showPreviewModal = true;
        },
        previewStageColor(key) {
            const map = {nuevo:'#3b82f6',contactado:'#f59e0b',negociando:'#8b5cf6',propuesta:'#ec4899',ganado:'#22c55e',perdido:'#ef4444'};
            return map[key] || '#6b7280';
        },

        // --- Delete ---
        confirmDelete(cli) {
            this.deleteTarget = cli;
            this.showDeleteModal = true;
        },
        async doDelete() {
            if (!this.deleteTarget) return;
            this.deleting = true;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.deleteTarget.id);
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.clientes = this.clientes.filter(c => c.id != this.deleteTarget.id);
                    this.showDeleteModal = false;
                    this.showToast('Deal eliminado');
                } else { this.showToast(d.error || 'Error', 'error'); }
            } catch(err) { this.showToast('Error de red', 'error'); }
            this.deleting = false;
        },

        timeAgo(d) {
            if (!d) return '';
            const diff = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
            if (diff < 3600) return Math.max(1, Math.floor(diff/60)) + 'min';
            if (diff < 86400) return Math.floor(diff/3600) + 'h';
            const days = Math.floor(diff / 86400);
            if (days === 1) return 'Ayer';
            if (days < 30) return days + 'd';
            return Math.floor(days / 30) + 'M';
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
