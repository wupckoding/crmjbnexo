<?php
require_once 'includes/auth_check.php';
$pageTitle = __('cli_titulo');
$currentPage = 'clientes';

$isAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';

// Admin sees all clients, others see only their assigned ones
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT c.*, u.nombre as asignado_nombre FROM clientes c LEFT JOIN usuarios u ON c.asignado_a = u.id ORDER BY c.creado_en DESC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT c.*, u.nombre as asignado_nombre FROM clientes c LEFT JOIN usuarios u ON c.asignado_a = u.id WHERE c.asignado_a = :uid ORDER BY c.creado_en DESC");
    $stmt->execute(['uid' => $_SESSION['usuario_id']]);
}
$clientes = $stmt->fetchAll();

// Load users for assignment dropdown
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Load pipeline stages for bulk send
$etapas = $pdo->query("SELECT * FROM pipeline_etapas ORDER BY orden")->fetchAll();

// KPI stats
$totalClientes = count($clientes);
$mesActual = date('Y-m');
$nuevosEsteMes = 0;
$countByStatus = ['nuevo'=>0,'contactado'=>0,'negociando'=>0,'ganado'=>0,'perdido'=>0];
foreach ($clientes as $c) {
    $countByStatus[$c['estado']] = ($countByStatus[$c['estado']] ?? 0) + 1;
    if (substr($c['creado_en'], 0, 7) === $mesActual) $nuevosEsteMes++;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="clientesApp()">

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-nexo-600/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold"><?php echo $totalClientes; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('dash_total_clientes'); ?></p>
                </div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold"><?php echo $nuevosEsteMes; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('dash_nuevos_mes'); ?></p>
                </div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold"><?php echo $countByStatus['ganado']; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('dash_ganados'); ?></p>
                </div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold"><?php echo $countByStatus['negociando']; ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('dash_negociando'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters + Search + Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            <!-- Instant search -->
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="<?php echo __('cli_buscar'); ?>" class="pl-9 pr-3 py-2 text-sm rounded-xl dark:bg-dark-700 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 w-56">
            </div>
            <!-- Filter tabs with counters -->
            <div class="flex gap-1.5 flex-wrap">
                <?php
                $filters = [
                    ''=>[__('filtro_todos'), $totalClientes],
                    'nuevo'=>[__('cli_nuevo_titulo'), $countByStatus['nuevo']],
                    'contactado'=>[__('cli_contactado', 'Contactados'), $countByStatus['contactado']],
                    'negociando'=>[__('cli_negociando'), $countByStatus['negociando']],
                    'ganado'=>[__('cli_ganado', 'Ganados'), $countByStatus['ganado']],
                    'perdido'=>[__('cli_perdido', 'Perdidos'), $countByStatus['perdido']]
                ];
                foreach ($filters as $k=>$v): ?>
                <button @click="filtro = '<?php echo $k; ?>'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="filtro === '<?php echo $k; ?>' ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200'"><?php echo $v[0]; ?> <span class="opacity-60">(<?php echo $v[1]; ?>)</span></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <!-- Export CSV -->
            <button @click="exportCSV()" class="px-3 py-2 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-1.5" title="Exportar CSV">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                CSV
            </button>
            <button @click="openNew()" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white flex items-center gap-2 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?php echo __('cli_nuevo'); ?>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="dark:bg-dark-700/50 bg-gray-50 border-b dark:border-white/[0.06] border-gray-200">
                        <th class="w-10 px-4 py-3 text-center">
                            <input type="checkbox" @change="toggleAll($event.target.checked)" :checked="allSelected()" class="nexo-check">
                        </th>
                        <th @click="sortBy('nombre')" class="text-left px-4 py-3 font-medium dark:text-white/50 text-gray-500 cursor-pointer select-none hover:text-nexo-400 transition-colors">
                            <span class="flex items-center gap-1"><?php echo __('tabla_cliente'); ?> <span x-show="sortCol==='nombre'" x-text="sortDir==='asc'?'↑':'↓'" class="text-nexo-400"></span></span>
                        </th>
                        <th @click="sortBy('empresa')" class="text-left px-4 py-3 font-medium dark:text-white/50 text-gray-500 hidden md:table-cell cursor-pointer select-none hover:text-nexo-400 transition-colors">
                            <span class="flex items-center gap-1"><?php echo __('tabla_empresa'); ?> <span x-show="sortCol==='empresa'" x-text="sortDir==='asc'?'↑':'↓'" class="text-nexo-400"></span></span>
                        </th>
                        <th class="text-left px-4 py-3 font-medium dark:text-white/50 text-gray-500 hidden lg:table-cell"><?php echo __('tabla_contacto'); ?></th>
                        <th @click="sortBy('estado')" class="text-left px-4 py-3 font-medium dark:text-white/50 text-gray-500 cursor-pointer select-none hover:text-nexo-400 transition-colors">
                            <span class="flex items-center gap-1"><?php echo __('tabla_estado'); ?> <span x-show="sortCol==='estado'" x-text="sortDir==='asc'?'↑':'↓'" class="text-nexo-400"></span></span>
                        </th>
                        <th class="text-left px-4 py-3 font-medium dark:text-white/50 text-gray-500 hidden lg:table-cell"><?php echo __('tabla_asignado'); ?></th>
                        <th @click="sortBy('creado_en')" class="text-left px-4 py-3 font-medium dark:text-white/50 text-gray-500 hidden xl:table-cell cursor-pointer select-none hover:text-nexo-400 transition-colors">
                            <span class="flex items-center gap-1"><?php echo __('tabla_creado'); ?> <span x-show="sortCol==='creado_en'" x-text="sortDir==='asc'?'↑':'↓'" class="text-nexo-400"></span></span>
                        </th>
                        <th class="text-right px-4 py-3 font-medium dark:text-white/50 text-gray-500"><?php echo __('tabla_acciones'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-white/[0.04] divide-gray-100">
                    <template x-for="c in filtered()" :key="c.id">
                    <tr class="dark:hover:bg-white/[0.02] hover:bg-gray-50 transition-colors" :class="selected.includes(c.id) ? 'dark:bg-nexo-600/5 bg-nexo-50' : ''">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" :value="c.id" x-model.number="selected" class="nexo-check">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0" :class="gradClass(c.estado)" x-text="initials(c.nombre)"></div>
                                <div>
                                    <p class="font-medium" x-text="c.nombre"></p>
                                    <p class="text-xs dark:text-white/30 text-gray-400 md:hidden" x-text="c.empresa || ''"></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell dark:text-white/60 text-gray-600" x-text="c.empresa || '—'"></td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <p class="dark:text-white/60 text-gray-600 text-xs" x-text="c.email || ''"></p>
                            <p class="dark:text-white/40 text-gray-400 text-xs" x-text="c.telefono || ''"></p>
                        </td>
                        <td class="px-4 py-3"><span class="text-xs font-medium px-2 py-1 rounded-full" :class="badgeClass(c.estado)" x-text="c.estado.charAt(0).toUpperCase()+c.estado.slice(1)"></span></td>
                        <td class="px-4 py-3 hidden lg:table-cell dark:text-white/50 text-gray-500 text-xs" x-text="c.asignado_nombre || '—'"></td>
                        <td class="px-4 py-3 hidden xl:table-cell dark:text-white/40 text-gray-400 text-xs" x-text="timeAgo(c.creado_en)"></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a :href="'cliente_detalle.php?id='+c.id" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 dark:text-white/40 text-gray-400 transition-colors" title="Ver">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button @click="openEdit(c)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-blue-400 transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="confirmDelete(c.id, c.nombre)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-red-400 transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    </template>
                    <template x-if="filtered().length === 0">
                        <tr><td colspan="8" class="text-center py-12 dark:text-white/30 text-gray-400"><?php echo __('cli_sin_resultados'); ?></td></tr>
                    </template>
                </tbody>
            </table>
        </div>
        <!-- Results count -->
        <div class="px-4 py-2.5 border-t dark:border-white/[0.06] border-gray-100 text-xs dark:text-white/30 text-gray-400">
            <?php echo __('tabla_mostrando'); ?> <strong x-text="filtered().length"></strong> <?php echo __('paginacion_de'); ?> <strong><?php echo $totalClientes; ?></strong> <?php echo __('cli_titulo'); ?>
        </div>
    </div>

    <!-- Modal crear/editar cliente -->
    <div x-show="showModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showModal = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6" @click.outside="showModal = false">
            <h3 class="text-lg font-bold mb-4" x-text="editId ? 'Editar Cliente' : 'Nuevo Cliente'"></h3>
            <form @submit.prevent="saveClient()" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_nombre'); ?> *</label><input type="text" x-model="form.nombre" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_empresa'); ?></label><input type="text" x-model="form.empresa" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_email'); ?></label><input type="email" x-model="form.email" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_telefono'); ?></label><input type="text" x-model="form.telefono" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_sitio_web'); ?></label><input type="text" x-model="form.sitio_web" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div x-show="editId">
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_estado'); ?></label>
                        <select x-model="form.estado" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value="nuevo">Nuevo</option>
                            <option value="contactado">Contactado</option>
                            <option value="negociando">Negociando</option>
                            <option value="ganado">Ganado</option>
                            <option value="perdido">Perdido</option>
                        </select>
                    </div>
                </div>
                <?php if ($isAdmin): ?>
                <div>
                    <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_asignado'); ?></label>
                    <select x-model="form.asignado_a" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_direccion'); ?></label><input type="text" x-model="form.direccion" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('cli_notas'); ?></label><textarea x-model="form.notas" rows="2" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none"></textarea></div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white" x-text="saving ? 'Guardando...' : (editId ? 'Guardar Cambios' : 'Crear Cliente')" :disabled="saving"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal confirmar eliminar -->
    <div x-show="showDelete" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDelete = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-red-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-lg font-bold mb-1">Eliminar Cliente</h3>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-4">¿Seguro que deseas eliminar a <strong x-text="deleteName"></strong>? Esta acción no se puede deshacer.</p>
            <div class="flex gap-3">
                <button @click="showDelete = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                <button @click="deleteClient()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition-colors" x-text="deleting ? 'Eliminando...' : 'Eliminar'" :disabled="deleting"></button>
            </div>
        </div>
    </div>

    <!-- ========== FLOATING SELECTION BAR ========== -->
    <div x-show="selected.length > 0" x-transition.opacity x-cloak
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40">
        <div class="flex items-center gap-3 px-5 py-3 rounded-2xl dark:bg-dark-700 bg-white border dark:border-white/10 border-gray-200 shadow-2xl shadow-black/20">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-nexo-600/20 flex items-center justify-center">
                    <span class="text-sm font-bold text-nexo-400" x-text="selected.length"></span>
                </div>
                <span class="text-sm dark:text-white/60 text-gray-600" x-text="selected.length === 1 ? 'cliente seleccionado' : 'clientes seleccionados'"></span>
            </div>
            <div class="w-px h-6 dark:bg-white/10 bg-gray-200"></div>
            <button @click="showPipeline = true" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Enviar a Pipeline
            </button>
            <button @click="selected = []" class="w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 dark:text-white/40 text-gray-400 transition-colors" title="Deseleccionar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- ========== MODAL ENVIAR A PIPELINE ========== -->
    <div x-show="showPipeline" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showPipeline = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6" @click.outside="showPipeline = false">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-nexo-600/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold">Enviar a Pipeline</h3>
                    <p class="text-xs dark:text-white/40 text-gray-400"><span x-text="selected.length"></span> cliente(s) seleccionado(s)</p>
                </div>
            </div>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-4">Selecciona la etapa del pipeline a la cual enviar los clientes:</p>
            <div class="space-y-2 mb-5">
                <?php foreach ($etapas as $et):
                    $etKey = $et['estado_clave'] ?? mb_strtolower($et['nombre']);
                ?>
                <button type="button"
                    @click="pipelineEtapa = '<?php echo htmlspecialchars($etKey, ENT_QUOTES, 'UTF-8'); ?>'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left"
                    :class="pipelineEtapa === '<?php echo htmlspecialchars($etKey, ENT_QUOTES, 'UTF-8'); ?>' ? 'border-nexo-500/50 dark:bg-white/5 bg-gray-50' : 'border-transparent dark:bg-white/[0.03] bg-gray-50 dark:hover:bg-white/5 hover:bg-gray-100'">
                    <span class="w-3 h-3 rounded-full shrink-0" style="background: <?php echo htmlspecialchars($et['color'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($et['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showPipeline = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                <button type="button" @click="sendToPipeline()" :disabled="!pipelineEtapa || sendingPipeline" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white disabled:opacity-50" x-text="sendingPipeline ? 'Enviando...' : 'Enviar'"></button>
            </div>
        </div>
    </div>
</div>
</main>

<script>
function clientesApp() {
    return {
        showModal: false,
        showDelete: false,
        editId: null,
        deleteId: null,
        deleteName: '',
        saving: false,
        deleting: false,
        search: '',
        filtro: '',
        sortCol: 'creado_en',
        sortDir: 'desc',
        selected: [],
        showPipeline: false,
        pipelineEtapa: '',
        sendingPipeline: false,
        clientes: <?php echo json_encode($clientes, JSON_HEX_APOS | JSON_HEX_TAG); ?>,
        form: { nombre:'', empresa:'', email:'', telefono:'', sitio_web:'', estado:'nuevo', notas:'', direccion:'', asignado_a: '<?php echo $userId ?? $_SESSION['usuario_id']; ?>' },

        filtered() {
            let list = this.clientes;
            // Filter by status
            if (this.filtro) list = list.filter(c => c.estado === this.filtro);
            // Search
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(c => (c.nombre||'').toLowerCase().includes(q) || (c.email||'').toLowerCase().includes(q) || (c.empresa||'').toLowerCase().includes(q) || (c.telefono||'').includes(q));
            }
            // Sort
            const col = this.sortCol;
            const dir = this.sortDir === 'asc' ? 1 : -1;
            list = [...list].sort((a, b) => {
                const va = (a[col] || '').toLowerCase();
                const vb = (b[col] || '').toLowerCase();
                return va < vb ? -dir : va > vb ? dir : 0;
            });
            return list;
        },

        sortBy(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = col;
                this.sortDir = 'asc';
            }
        },

        initials(name) {
            if (!name) return '?';
            const parts = name.trim().split(' ');
            return (parts[0][0] + (parts.length > 1 ? parts[parts.length-1][0] : '')).toUpperCase();
        },

        gradClass(estado) {
            const m = {nuevo:'bg-gradient-to-br from-blue-500 to-cyan-500',contactado:'bg-gradient-to-br from-amber-500 to-orange-500',negociando:'bg-gradient-to-br from-nexo-500 to-nexo-700',ganado:'bg-gradient-to-br from-emerald-500 to-green-500',perdido:'bg-gradient-to-br from-red-500 to-rose-500'};
            return m[estado] || 'bg-gradient-to-br from-gray-500 to-gray-600';
        },

        badgeClass(estado) {
            const m = {nuevo:'bg-blue-400/10 text-blue-400',contactado:'bg-amber-400/10 text-amber-400',negociando:'bg-nexo-500/15 text-nexo-400',ganado:'bg-emerald-400/10 text-emerald-400',perdido:'bg-red-400/10 text-red-400'};
            return m[estado] || 'bg-gray-400/10 text-gray-400';
        },

        timeAgo(dateStr) {
            if (!dateStr) return '—';
            const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
            if (diff < 60) return 'Justo ahora';
            if (diff < 3600) return Math.floor(diff/60) + ' min';
            if (diff < 86400) return Math.floor(diff/3600) + 'h';
            const days = Math.floor(diff/86400);
            if (days === 1) return 'Ayer';
            if (days < 30) return 'Hace ' + days + ' días';
            if (days < 365) return 'Hace ' + Math.floor(days/30) + ' mes' + (Math.floor(days/30)>1?'es':'');
            return new Date(dateStr).toLocaleDateString('es');
        },

        exportCSV() {
            const rows = this.filtered();
            const header = ['Nombre','Empresa','Email','Teléfono','Estado','Asignado','Creado'];
            const csv = [header.join(','), ...rows.map(c =>
                [c.nombre, c.empresa||'', c.email||'', c.telefono||'', c.estado, c.asignado_nombre||'', c.creado_en||'']
                .map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',')
            )].join('\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'clientes_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
        },

        openNew() {
            this.editId = null;
            this.form = { nombre:'', empresa:'', email:'', telefono:'', sitio_web:'', estado:'nuevo', notas:'', direccion:'', asignado_a: '<?php echo $userId ?? $_SESSION['usuario_id']; ?>' };
            this.showModal = true;
        },

        openEdit(c) {
            this.editId = c.id;
            this.form = { nombre: c.nombre, empresa: c.empresa||'', email: c.email||'', telefono: c.telefono||'', sitio_web: c.sitio_web||'', estado: c.estado, notas: c.notas||'', direccion: c.direccion||'', asignado_a: c.asignado_a || '<?php echo $userId ?? $_SESSION['usuario_id']; ?>' };
            this.showModal = true;
        },

        async saveClient() {
            if (!this.form.nombre.trim()) return;
            this.saving = true;
            const fd = new FormData();
            fd.append('action', this.editId ? 'update' : 'create');
            if (this.editId) fd.append('id', this.editId);
            Object.keys(this.form).forEach(k => fd.append(k, this.form[k]));
            try {
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok || data.id) location.reload();
            } catch(e) { console.error(e); }
            this.saving = false;
        },

        confirmDelete(id, name) {
            this.deleteId = id;
            this.deleteName = name;
            this.showDelete = true;
        },

        async deleteClient() {
            this.deleting = true;
            try {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', this.deleteId);
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) location.reload();
            } catch(e) { console.error(e); }
            this.deleting = false;
        },

        toggleAll(checked) {
            if (checked) {
                this.selected = this.filtered().map(c => parseInt(c.id));
            } else {
                this.selected = [];
            }
        },

        allSelected() {
            const ids = this.filtered().map(c => parseInt(c.id));
            return ids.length > 0 && ids.every(id => this.selected.includes(id));
        },

        async sendToPipeline() {
            if (!this.pipelineEtapa || this.selected.length === 0) return;
            this.sendingPipeline = true;
            try {
                const fd = new FormData();
                fd.append('action', 'bulk_to_pipeline');
                fd.append('ids', this.selected.join(','));
                fd.append('etapa', this.pipelineEtapa);
                const r = await fetch('api/clientes.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) location.reload();
            } catch(e) { console.error(e); }
            this.sendingPipeline = false;
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
