<?php
require_once 'includes/auth_check.php';
$pageTitle = __('fac_titulo');
$currentPage = 'facturas';

$facturas = $pdo->query("SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email, c.empresa as cliente_empresa FROM facturas f JOIN clientes c ON f.cliente_id = c.id ORDER BY f.creado_en DESC")->fetchAll();
$clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll();
$servicios = $pdo->query("SELECT id, nombre, precio FROM servicios WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Next invoice number
$lastNum = $pdo->query("SELECT numero FROM facturas ORDER BY id DESC LIMIT 1")->fetchColumn();
$nextNum = $lastNum ? 'INV-2026-' . str_pad((int)substr($lastNum, -3) + 1, 3, '0', STR_PAD_LEFT) : 'INV-2026-001';

// KPI data
$totalFacturas = count($facturas);
$totalPagadas = 0; $totalPendiente = 0; $totalVencidas = 0;
foreach ($facturas as $f) {
    if ($f['estado'] === 'pagada') $totalPagadas += $f['total'];
    if (in_array($f['estado'], ['enviada','borrador'])) $totalPendiente += $f['total'];
    if ($f['estado'] === 'vencida') $totalVencidas++;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="facturasApp()">

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <p class="text-xs dark:text-white/40 text-gray-400 mb-1"><?php echo __('fac_total_facturas'); ?></p>
            <p class="text-2xl font-bold"><?php echo $totalFacturas; ?></p>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <p class="text-xs dark:text-white/40 text-gray-400 mb-1"><?php echo __('fac_cobrado'); ?></p>
            <p class="text-2xl font-bold dark:text-emerald-400 text-emerald-600">$<?php echo number_format($totalPagadas, 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <p class="text-xs dark:text-white/40 text-gray-400 mb-1"><?php echo __('fac_pendiente'); ?></p>
            <p class="text-2xl font-bold dark:text-amber-400 text-amber-600">$<?php echo number_format($totalPendiente, 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <p class="text-xs dark:text-white/40 text-gray-400 mb-1"><?php echo __('fac_vencidas'); ?></p>
            <p class="text-2xl font-bold dark:text-red-400 text-red-600"><?php echo $totalVencidas; ?></p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="flex gap-1.5 flex-wrap">
            <button @click="filterEstado = ''" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="filterEstado === '' ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200'"><?php echo __('fac_todas'); ?></button>
            <button @click="filterEstado = 'borrador'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="filterEstado === 'borrador' ? 'bg-gray-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200'"><?php echo __('fac_borrador'); ?></button>
            <button @click="filterEstado = 'enviada'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="filterEstado === 'enviada' ? 'bg-blue-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200'"><?php echo __('fac_enviada'); ?></button>
            <button @click="filterEstado = 'pagada'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="filterEstado === 'pagada' ? 'bg-emerald-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200'"><?php echo __('fac_pagada'); ?></button>
            <button @click="filterEstado = 'vencida'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="filterEstado === 'vencida' ? 'bg-red-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200'"><?php echo __('fac_vencidas'); ?></button>
        </div>
        <div class="flex items-center gap-2 sm:ml-auto">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="<?php echo __('fac_buscar'); ?>" class="w-44 pl-8 pr-3 py-1.5 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
            </div>
            <select x-model="sortBy" class="px-2 py-1.5 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                <option value="fecha_desc"><?php echo __('fac_mas_reciente'); ?></option>
                <option value="fecha_asc"><?php echo __('fac_mas_antigua'); ?></option>
                <option value="total_desc"><?php echo __('fac_mayor_monto'); ?></option>
                <option value="total_asc"><?php echo __('fac_menor_monto'); ?></option>
            </select>
            <button @click="viewMode = viewMode === 'cards' ? 'table' : 'cards'" class="w-8 h-8 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                <svg x-show="viewMode === 'cards'" class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <svg x-show="viewMode === 'table'" class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </button>
            <button @click="exportCSV()" class="px-3 py-1.5 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:hover:bg-white/10 hover:bg-gray-100 transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                CSV
            </button>
            <button @click="showModal = true" class="btn-purple px-4 py-1.5 rounded-xl text-xs font-medium text-white flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?php echo __('fac_nueva'); ?>
            </button>
        </div>
    </div>

    <p class="text-xs dark:text-white/30 text-gray-400" x-text="filtered().length + ' facturas'"></p>

    <!-- Cards View -->
    <div x-show="viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="f in filtered()" :key="f.id">
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 flex flex-col hover:shadow-lg hover:dark:border-white/[0.12] hover:border-gray-300 transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-mono dark:text-white/40 text-gray-400" x-text="f.numero"></span>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="text-[10px] font-medium px-2 py-0.5 rounded-full border cursor-pointer" :class="statusClass(f.estado)" x-text="f.estado.charAt(0).toUpperCase() + f.estado.slice(1)"></button>
                        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 top-6 z-10 dark:bg-dark-700 bg-white rounded-xl border dark:border-white/10 border-gray-200 shadow-xl overflow-hidden min-w-[120px]">
                            <template x-for="st in ['borrador','enviada','pagada','vencida','cancelada']" :key="st">
                                <button @click="changeStatus(f, st); open = false" class="block w-full text-left px-3 py-1.5 text-xs dark:hover:bg-white/5 hover:bg-gray-50 transition-colors" :class="f.estado === st ? 'font-bold dark:text-nexo-400 text-nexo-600' : ''" x-text="st.charAt(0).toUpperCase() + st.slice(1)"></button>
                            </template>
                        </div>
                    </div>
                </div>
                <p class="font-semibold text-sm mb-0.5" x-text="f.cliente_nombre"></p>
                <p class="text-[10px] dark:text-white/30 text-gray-300 truncate mb-1" x-text="f.cliente_email"></p>
                <p class="text-xs dark:text-white/40 text-gray-400 mb-3" x-text="formatDate(f.fecha_emision) + (f.fecha_vencimiento ? ' → ' + formatDate(f.fecha_vencimiento) : '')"></p>
                <div class="mt-auto pt-3 border-t dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                    <span class="text-xl font-bold" x-text="'$' + Number(f.total).toLocaleString()"></span>
                    <div class="flex items-center gap-1">
                        <a :href="'factura_pdf.php?id='+f.id" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="PDF">
                            <svg class="w-3.5 h-3.5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </a>
                        <a :href="'factura_detalle.php?id='+f.id" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Ver">
                            <svg class="w-3.5 h-3.5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                        <button @click="confirmDeleteFactura(f)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Eliminar">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="filtered().length === 0">
            <div class="col-span-full text-center py-16">
                <svg class="w-16 h-16 mx-auto dark:text-white/10 text-gray-200 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="dark:text-white/30 text-gray-400 text-sm"><?php echo __('fac_sin_facturas'); ?></p>
                <button @click="showModal = true" class="mt-3 text-xs text-nexo-400 hover:text-nexo-300 font-medium">+ <?php echo __('fac_crear_primera'); ?></button>
            </div>
        </template>
    </div>

    <!-- Table View -->
    <div x-show="viewMode === 'table'" class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="border-b dark:border-white/[0.06] border-gray-100">
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('fac_numero'); ?></th>
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('fac_cliente'); ?></th>
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('fac_emision'); ?></th>
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('fac_vencimiento'); ?></th>
                <th class="text-right px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('fac_total'); ?></th>
                <th class="text-center px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('fac_estado'); ?></th>
                <th class="text-center px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('tabla_acciones'); ?></th>
            </tr></thead>
            <tbody>
            <template x-for="f in filtered()" :key="f.id">
            <tr class="border-b dark:border-white/[0.04] border-gray-50 hover:dark:bg-white/[0.02] hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 font-mono text-xs dark:text-white/60 text-gray-500" x-text="f.numero"></td>
                <td class="px-4 py-3">
                    <p class="font-medium text-sm" x-text="f.cliente_nombre"></p>
                    <p class="text-[10px] dark:text-white/30 text-gray-400" x-text="f.cliente_empresa || f.cliente_email"></p>
                </td>
                <td class="px-4 py-3 text-xs dark:text-white/50 text-gray-500" x-text="formatDate(f.fecha_emision)"></td>
                <td class="px-4 py-3 text-xs" :class="isOverdue(f) ? 'dark:text-red-400 text-red-600 font-medium' : 'dark:text-white/50 text-gray-500'" x-text="f.fecha_vencimiento ? formatDate(f.fecha_vencimiento) : '—'"></td>
                <td class="px-4 py-3 text-right font-bold" x-text="'$' + Number(f.total).toLocaleString()"></td>
                <td class="px-4 py-3 text-center">
                    <div class="relative inline-block" x-data="{ open: false }">
                        <button @click="open = !open" class="text-[10px] font-medium px-2 py-0.5 rounded-full border cursor-pointer" :class="statusClass(f.estado)" x-text="f.estado.charAt(0).toUpperCase() + f.estado.slice(1)"></button>
                        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 top-6 z-10 dark:bg-dark-700 bg-white rounded-xl border dark:border-white/10 border-gray-200 shadow-xl overflow-hidden min-w-[120px]">
                            <template x-for="st in ['borrador','enviada','pagada','vencida','cancelada']" :key="st">
                                <button @click="changeStatus(f, st); open = false" class="block w-full text-left px-3 py-1.5 text-xs dark:hover:bg-white/5 hover:bg-gray-50 transition-colors" :class="f.estado === st ? 'font-bold dark:text-nexo-400 text-nexo-600' : ''" x-text="st.charAt(0).toUpperCase() + st.slice(1)"></button>
                            </template>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a :href="'factura_pdf.php?id='+f.id" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="PDF">
                            <svg class="w-3.5 h-3.5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </a>
                        <a :href="'factura_detalle.php?id='+f.id" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Ver">
                            <svg class="w-3.5 h-3.5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        <button @click="confirmDeleteFactura(f)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Eliminar">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            </template>
            </tbody>
        </table>
    </div>

    <!-- Delete Confirm Modal -->
    <div x-show="showDeleteConfirm" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDeleteConfirm = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 text-center">
            <div class="w-14 h-14 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-500/15">
                <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-bold mb-1"><?php echo __('fac_eliminar_titulo'); ?></h3>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-1" x-text="deleteTarget.numero + ' - ' + deleteTarget.cliente"></p>
            <p class="text-xs text-red-400 mb-4"><?php echo __('fac_eliminar_desc'); ?></p>
            <div class="flex gap-3">
                <button @click="showDeleteConfirm = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                <button @click="doDeleteFactura()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors"><?php echo __('btn_eliminar'); ?></button>
            </div>
        </div>
    </div>

    <!-- Modal nueva factura -->
    <div x-show="showModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showModal = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-2xl dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold mb-4"><?php echo __('fac_nueva'); ?></h3>
            <form method="POST" action="api/facturas.php" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('fac_numero'); ?></label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($nextNum); ?>" readonly class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none opacity-60">
                    </div>
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('fac_cliente'); ?> *</label>
                        <select name="cliente_id" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value=""><?php echo __('fac_seleccionar'); ?></option>
                            <?php foreach ($clientes as $cl): ?><option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['nombre']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('fac_emision'); ?></label><input type="date" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('fac_vencimiento'); ?></label><input type="date" name="fecha_vencimiento" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                </div>

                <!-- Items -->
                <div>
                    <label class="text-xs dark:text-white/50 text-gray-500 mb-2 block font-medium"><?php echo __('fac_servicios_items'); ?></label>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="mb-3 p-3 rounded-xl dark:bg-white/[0.03] bg-gray-50/80 border dark:border-white/[0.04] border-gray-100">
                            <div class="flex gap-2 mb-2">
                                <select @change="selectServicio(index, $event.target.value)" :value="item.servicio_id || ''" class="flex-1 px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-white border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                    <option value=""><?php echo __('fac_sel_servicio'); ?></option>
                                    <template x-for="sv in serviciosDisponibles" :key="sv.id">
                                        <option :value="sv.id" x-text="sv.nombre + ' — $' + Number(sv.precio).toLocaleString('es')"></option>
                                    </template>
                                </select>
                                <button type="button" @click="items.length > 1 && items.splice(index, 1)" class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-red-500/10 text-red-400 shrink-0" title="Quitar">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <input type="hidden" :name="'items['+index+'][servicio_id]'" :value="item.servicio_id || ''">
                                <input type="text" :name="'items['+index+'][desc]'" x-model="item.desc" placeholder="Descripción del item" class="flex-1 px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-white border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs dark:text-white/30 text-gray-400">Cant.</span>
                                    <input type="number" :name="'items['+index+'][qty]'" x-model.number="item.qty" min="1" class="w-20 pl-12 pr-2 py-2 text-sm rounded-xl dark:bg-white/5 bg-white border dark:border-white/10 border-gray-200 outline-none text-center">
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs dark:text-white/30 text-gray-400">$</span>
                                    <input type="number" :name="'items['+index+'][price]'" x-model.number="item.price" min="0" step="0.01" class="w-28 pl-7 pr-2 py-2 text-sm rounded-xl dark:bg-white/5 bg-white border dark:border-white/10 border-gray-200 outline-none">
                                </div>
                            </div>
                            <div class="flex justify-end mt-1">
                                <span class="text-xs dark:text-white/30 text-gray-400">Subtotal: <strong class="dark:text-white/60 text-gray-600" x-text="'$' + (item.qty * item.price).toLocaleString('es')"></strong></span>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="items.push({servicio_id:'',desc:'',qty:1,price:0})" class="text-xs text-nexo-400 hover:text-nexo-300 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?php echo __('fac_agregar_item'); ?>
                    </button>
                </div>

                <div class="flex items-center justify-between pt-3 border-t dark:border-white/[0.06] border-gray-200">
                    <span class="dark:text-white/50 text-gray-500 text-sm">Total:</span>
                    <span class="text-xl font-bold">$<span x-text="subtotal.toLocaleString()">0</span></span>
                </div>

                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('fac_notas'); ?></label><textarea name="notas" rows="2" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none"></textarea></div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white"><?php echo __('fac_crear_factura'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>

<script>
function facturasApp() {
    return {
        facturas: <?php echo json_encode($facturas); ?>,
        serviciosDisponibles: <?php echo json_encode($servicios); ?>,
        items: [{servicio_id:'',desc:'',qty:1,price:0}],
        get subtotal() { return this.items.reduce((s,i) => s + (i.qty * i.price), 0); },

        selectServicio(index, servicioId) {
            if (!servicioId) {
                this.items[index].servicio_id = '';
                return;
            }
            const sv = this.serviciosDisponibles.find(s => s.id == servicioId);
            if (sv) {
                this.items[index].servicio_id = sv.id;
                this.items[index].desc = sv.nombre;
                this.items[index].price = Number(sv.precio);
            }
        },
        search: '',
        filterEstado: '',
        sortBy: 'fecha_desc',
        viewMode: 'cards',
        showModal: false,
        showDeleteConfirm: false,
        deleteTarget: { id:0, numero:'', cliente:'' },

        filtered() {
            let list = [...this.facturas];
            if (this.filterEstado) list = list.filter(f => f.estado === this.filterEstado);
            if (this.search) {
                const s = this.search.toLowerCase();
                list = list.filter(f => f.numero.toLowerCase().includes(s) || f.cliente_nombre.toLowerCase().includes(s) || (f.cliente_empresa||'').toLowerCase().includes(s));
            }
            list.sort((a,b) => {
                switch(this.sortBy) {
                    case 'fecha_asc': return new Date(a.fecha_emision) - new Date(b.fecha_emision);
                    case 'total_desc': return b.total - a.total;
                    case 'total_asc': return a.total - b.total;
                    default: return new Date(b.fecha_emision) - new Date(a.fecha_emision);
                }
            });
            return list;
        },

        statusClass(s) {
            const m = {
                pagada: 'bg-emerald-400/10 dark:text-emerald-400 text-emerald-600 border-emerald-400/20',
                enviada: 'bg-blue-400/10 dark:text-blue-400 text-blue-600 border-blue-400/20',
                borrador: 'bg-gray-400/10 dark:text-gray-400 text-gray-500 border-gray-400/20',
                vencida: 'bg-red-400/10 dark:text-red-400 text-red-600 border-red-400/20',
                cancelada: 'bg-red-400/10 dark:text-red-400 text-red-600 border-red-400/20'
            };
            return m[s] || m.borrador;
        },

        isOverdue(f) {
            return f.fecha_vencimiento && f.estado !== 'pagada' && f.estado !== 'cancelada' && new Date(f.fecha_vencimiento) < new Date();
        },

        formatDate(d) {
            if (!d) return '';
            const dt = new Date(d + 'T00:00:00');
            return dt.toLocaleDateString('es-CL', { day:'2-digit', month:'short', year:'numeric' });
        },

        async changeStatus(f, newStatus) {
            if (f.estado === newStatus) return;
            const fd = new FormData();
            fd.append('action', 'update_status');
            fd.append('id', f.id);
            fd.append('estado', newStatus);
            const r = await fetch('api/facturas.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) f.estado = newStatus;
        },

        confirmDeleteFactura(f) {
            this.deleteTarget = { id: f.id, numero: f.numero, cliente: f.cliente_nombre };
            this.showDeleteConfirm = true;
        },

        async doDeleteFactura() {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.deleteTarget.id);
            const r = await fetch('api/facturas.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) {
                this.facturas = this.facturas.filter(f => f.id != this.deleteTarget.id);
            }
            this.showDeleteConfirm = false;
        },

        exportCSV() {
            const rows = [['Número','Cliente','Email','Emisión','Vencimiento','Total','Estado']];
            this.filtered().forEach(f => {
                rows.push([f.numero, f.cliente_nombre, f.cliente_email, f.fecha_emision, f.fecha_vencimiento||'', f.total, f.estado]);
            });
            const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g,'""') + '"').join(',')).join('\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'facturas_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
