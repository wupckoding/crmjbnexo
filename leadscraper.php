<?php
require_once 'includes/auth_check.php';
$pageTitle = 'LeadScraper';
$currentPage = 'leadscraper';

$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="leadScraperApp()" x-init="loadLeads()">

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-nexo-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                <div><p class="text-2xl font-bold" x-text="leads.length"></p><p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('ls_total'); ?></p></div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></div>
                <div><p class="text-2xl font-bold" x-text="leads.filter(l=>l.estado==='nuevo').length"></p><p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('ls_nuevos'); ?></p></div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
                <div><p class="text-2xl font-bold" x-text="leads.filter(l=>l.estado==='contactado').length"></p><p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('ls_contactados'); ?></p></div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center"><svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div><p class="text-2xl font-bold" x-text="leads.filter(l=>l.estado==='convertido').length"></p><p class="text-[11px] dark:text-white/40 text-gray-400"><?php echo __('ls_convertidos'); ?></p></div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-1 p-1 dark:bg-dark-900/60 bg-gray-100 rounded-2xl w-fit">
        <button @click="activeTab='buscar'" class="px-5 py-2 rounded-xl text-sm font-medium transition-all" :class="activeTab==='buscar' ? 'bg-nexo-500 text-white shadow-lg shadow-nexo-500/25' : 'dark:text-white/50 text-gray-500 dark:hover:text-white/80 hover:text-gray-700'">
            <span class="flex items-center gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> <?php echo __('ls_buscar_leads'); ?></span>
        </button>
        <button @click="activeTab='leads'" class="px-5 py-2 rounded-xl text-sm font-medium transition-all" :class="activeTab==='leads' ? 'bg-nexo-500 text-white shadow-lg shadow-nexo-500/25' : 'dark:text-white/50 text-gray-500 dark:hover:text-white/80 hover:text-gray-700'">
            <span class="flex items-center gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg> <?php echo __('ls_mis_leads'); ?> <span class="ml-1 text-xs opacity-70" x-text="'('+leads.length+')'"></span></span>
        </button>
    </div>

    <!-- ═══════ BUSCAR LEADS TAB ═══════ -->
    <div x-show="activeTab === 'buscar'" x-cloak class="space-y-4">
        <!-- Search Bar -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="sm:w-56">
                    <label class="text-[10px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider mb-1 block"><?php echo __('ls_nicho'); ?></label>
                    <select x-model="searchNicho" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                        <option value=""><?php echo __('ls_seleccionar_nicho'); ?></option>
                        <option>Restaurantes</option><option>Inmobiliarias</option><option>Clínicas</option><option>Abogados</option><option>Gimnasios</option><option>Hoteles</option><option>Dentistas</option><option>Salones de belleza</option><option>Veterinarias</option><option>Spas</option><option>Constructoras</option><option>Escuelas</option><option>Talleres mecánicos</option><option>Contadores</option><option>Agencias de viaje</option><option>Arquitectos</option><option>Tiendas de ropa</option><option>Concesionarios</option><option>Fotografía y eventos</option><option>Agencias de marketing</option><option>Consultorías</option><option>Farmacias</option><option>Joyerías</option><option>Floristerías</option><option>Panaderías</option><option>Cafeterías</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="text-[10px] font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider mb-1 block"><?php echo __('ls_ubicacion'); ?></label>
                    <input type="text" x-model="searchLocation" placeholder="<?php echo __('ls_ubicacion_ph'); ?> (ej: Madrid, España)" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50" @keydown.enter="doSearch()">
                </div>
                <div class="flex items-end">
                    <button @click="doSearch()" :disabled="!searchNicho || searching" class="btn-purple px-6 py-2.5 rounded-xl text-sm font-medium text-white flex items-center gap-2 disabled:opacity-50 whitespace-nowrap">
                        <svg x-show="!searching" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <svg x-show="searching" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="searching ? _t.buscando : _t.buscar"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div x-show="searchResults.length > 0">
            <!-- Results header with selection controls -->
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <button @click="toggleSelectAll()" class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer select-none" :class="allSelected ? 'dark:bg-nexo-500/15 bg-nexo-50 text-nexo-400 border border-nexo-500/30' : 'dark:bg-white/5 bg-gray-100 dark:text-white/50 text-gray-500 border dark:border-white/[0.06] border-gray-200 hover:border-nexo-500/30'">
                        <div class="w-5 h-5 rounded-md flex items-center justify-center transition-all" :class="allSelected ? 'bg-nexo-500 text-white' : 'dark:bg-white/10 bg-gray-200'">
                            <svg x-show="allSelected" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span x-text="allSelected ? _t.deseleccionar : _t.seleccionar_todos"></span>
                    </button>
                    <p class="text-sm dark:text-white/50 text-gray-500"><span x-text="visibleResults.length" class="font-bold text-nexo-400"></span> <?php echo __('ls_resultados_para'); ?> &ldquo;<span x-text="lastQuery" class="italic"></span>&rdquo;</p>
                </div>
                <div class="flex items-center gap-2">
                    <span x-show="selectedCount > 0" class="text-xs font-medium px-2.5 py-1 rounded-lg bg-nexo-500/10 text-nexo-400" x-text="selectedCount + ' ' + (selectedCount > 1 ? _t.seleccionados : _t.seleccionado)"></span>
                    <span x-show="searchPage > 0" class="text-[10px] dark:text-white/30 text-gray-400"><?php echo __('ls_pagina'); ?> <span x-text="searchPage + 1"></span></span>
                </div>
            </div>

            <!-- Bulk action bar -->
            <div x-show="selectedCount > 0" x-transition class="mb-4 p-3 dark:bg-nexo-500/10 bg-purple-50 rounded-2xl border dark:border-nexo-500/20 border-purple-200 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="flex items-center gap-2 flex-1">
                    <div class="w-8 h-8 rounded-lg bg-nexo-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="text-sm font-medium dark:text-nexo-300 text-nexo-600" x-text="selectedCount + ' ' + (selectedCount > 1 ? _t.leads : _t.lead) + ' ' + (selectedCount > 1 ? _t.seleccionados : _t.seleccionado)"></span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <select x-model="bulkAssignUserId" class="px-3 py-2 text-xs rounded-lg dark:bg-white/10 bg-white border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 min-w-[160px]">
                        <option value=""><?php echo __('ls_asignar_a'); ?></option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select x-model="bulkEtapa" class="px-3 py-2 text-xs rounded-lg dark:bg-white/10 bg-white border dark:border-white/10 border-gray-200 outline-none min-w-[120px]">
                        <option value="nuevo"><?php echo __('ls_nuevo'); ?></option>
                        <option value="contactado"><?php echo __('ls_contactado'); ?></option>
                        <option value="negociando"><?php echo __('ls_negociando'); ?></option>
                    </select>
                    <button @click="bulkAssign()" :disabled="!bulkAssignUserId || bulkAssigning" class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-nexo-500 hover:bg-nexo-600 transition-colors disabled:opacity-50 flex items-center gap-1.5">
                        <svg x-show="!bulkAssigning" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <svg x-show="bulkAssigning" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="bulkAssigning ? _t.asignando : _t.asignar_sel"></span>
                    </button>
                    <button @click="bulkDismiss()" class="px-3 py-2 rounded-lg text-xs font-medium dark:text-white/40 text-gray-500 dark:hover:bg-white/5 hover:bg-gray-100 transition-colors"><?php echo __('ls_descartar'); ?></button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(sr, idx) in searchResults" :key="idx">
                    <div x-show="!sr.dismissed" class="dark:bg-dark-800 bg-white rounded-2xl border transition-all relative" :class="sr.selected ? 'dark:border-nexo-500/40 border-nexo-400 shadow-lg shadow-nexo-500/10' : 'dark:border-white/[0.06] border-gray-200 hover:shadow-lg hover:shadow-nexo-500/5'">
                        <!-- Top accent bar -->
                        <div class="h-1 rounded-t-2xl" :class="sr.assigned ? 'bg-emerald-500' : sr.selected ? 'bg-nexo-500' : 'bg-gradient-to-r from-blue-500 to-nexo-500'"></div>
                        <!-- Checkbox + Dismiss row -->
                        <div class="flex items-center justify-between px-4 pt-3">
                            <button @click="if(!sr.assigned) sr.selected = !sr.selected" class="flex items-center gap-2 cursor-pointer select-none group/sel" :class="sr.assigned && 'opacity-50 cursor-not-allowed'">
                                <div class="w-5 h-5 rounded-md flex items-center justify-center transition-all border" :class="sr.selected ? 'bg-nexo-500 border-nexo-500 text-white' : sr.assigned ? 'dark:bg-white/5 bg-gray-100 dark:border-white/10 border-gray-200' : 'dark:bg-white/5 bg-gray-100 dark:border-white/10 border-gray-200 group-hover/sel:border-nexo-500/50'">
                                    <svg x-show="sr.selected || sr.assigned" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </button>
                            <button @click="sr.dismissed = true" class="w-6 h-6 flex items-center justify-center rounded-full dark:bg-white/5 bg-gray-100 dark:hover:bg-red-500/20 hover:bg-red-50 transition-colors group z-10" title="Descartar">
                                <svg class="w-3 h-3 dark:text-white/30 text-gray-400 group-hover:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <!-- Duplicate badge -->
                        <div x-show="sr.isDuplicate" class="mx-4 mt-2 mb-0 px-2.5 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            <span class="text-[10px] text-amber-400 font-medium"><?php echo __('ls_ya_existe'); ?> <span x-text="sr.duplicateInfo" class="font-bold"></span></span>
                        </div>
                        <!-- Assigned badge -->
                        <div x-show="sr.assigned" class="mx-4 mt-2 mb-0 px-2.5 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-[10px] text-emerald-400 font-medium"><?php echo __('ls_asignado_a'); ?> <span x-text="sr.assignedTo" class="font-bold"></span></span>
                        </div>
                        <div class="p-4 pt-2">
                            <h3 class="font-bold text-sm leading-tight mb-1 line-clamp-2" x-text="sr.nombre"></h3>
                            <a :href="sr.sitio_web" target="_blank" rel="noopener" class="text-[11px] text-blue-400 hover:underline truncate block mb-2" x-text="sr.sitio_web.replace(/^https?:\/\//, '').replace(/\/$/, '').substring(0,50)"></a>
                            <p class="text-[11px] dark:text-white/40 text-gray-400 mb-3 line-clamp-3" x-text="sr.snippet"></p>

                            <!-- Contact person -->
                            <div x-show="sr.contactName" class="mb-2 px-2.5 py-1.5 rounded-lg dark:bg-nexo-500/5 bg-purple-50 border dark:border-nexo-500/10 border-purple-100">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-nexo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="text-xs font-medium dark:text-nexo-300 text-nexo-600" x-text="sr.contactName"></span>
                                    <span x-show="sr.contactRole" class="text-[10px] dark:text-white/30 text-gray-400" x-text="'(' + sr.contactRole + ')'"></span>
                                </div>
                            </div>

                            <!-- Basic contact info -->
                            <div class="space-y-1 mb-3">
                                <div x-show="sr.telefono" class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span class="text-xs dark:text-white/70 text-gray-600" x-text="sr.telefono"></span>
                                </div>
                                <div x-show="sr.email" class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span class="text-xs text-amber-400" x-text="sr.email"></span>
                                </div>
                                <div x-show="sr.whatsapp" class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-green-400 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                    <span class="text-xs text-green-400" x-text="sr.whatsapp"></span>
                                </div>
                                <div x-show="sr.address" class="flex items-start gap-2">
                                    <svg class="w-3 h-3 dark:text-white/30 text-gray-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="text-[11px] dark:text-white/40 text-gray-400 leading-tight" x-text="sr.address"></span>
                                </div>
                            </div>

                            <!-- Enriched data -->
                            <div x-show="sr.enriched" class="mb-3 p-2.5 rounded-lg dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100 space-y-2">
                                <p class="text-[10px] font-semibold dark:text-white/40 text-gray-400 uppercase mb-1"><?php echo __('ls_datos_web'); ?></p>
                                <template x-for="(em, ei) in (sr.allEmails||[])" :key="'e'+ei">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span class="text-[11px] text-amber-400" x-text="em"></span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded dark:bg-white/5 bg-gray-200 dark:text-white/30 text-gray-400" x-text="classifyEmail(em)"></span>
                                    </div>
                                </template>
                                <template x-for="(ph, pi) in (sr.allPhones||[])" :key="'p'+pi">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span class="text-[11px] dark:text-white/70 text-gray-600" x-text="ph"></span>
                                        <span x-show="pi===0" class="text-[9px] px-1.5 py-0.5 rounded dark:bg-emerald-500/10 bg-emerald-50 text-emerald-400"><?php echo __('ls_principal'); ?></span>
                                    </div>
                                </template>
                                <template x-for="s in (sr.social||[])" :key="s">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        <a :href="s" target="_blank" rel="noopener" class="text-[11px] text-blue-400 hover:underline truncate" x-text="s.replace(/^https?:\/\/(?:www\.)?/,'').substring(0,40)"></a>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded dark:bg-blue-500/10 bg-blue-50 text-blue-400" x-text="classifySocial(s)"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-1.5 pt-3 border-t dark:border-white/[0.06] border-gray-100">
                                <button @click="enrichResult(sr)" :disabled="sr.enriching||sr.enriched" class="flex-1 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-colors flex items-center justify-center gap-1" :class="sr.enriched?'bg-emerald-500/10 text-emerald-400':sr.enriching?'bg-amber-500/10 text-amber-400':'dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200'">
                                    <svg x-show="!sr.enriching&&!sr.enriched" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <svg x-show="sr.enriching" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg x-show="sr.enriched" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="sr.enriched?_t.escaneado:sr.enriching?_t.escaneando:_t.escanear_web"></span>
                                </button>
                                <button @click="openAssignFromSearch(sr)" class="flex-1 px-2 py-1.5 rounded-lg text-[10px] font-medium bg-nexo-500/10 text-nexo-400 hover:bg-nexo-500/20 transition-colors flex items-center justify-center gap-1" :class="sr.assigned&&'opacity-50 cursor-not-allowed'" :disabled="sr.assigned">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    <span x-text="sr.assigned?_t.asignado:_t.asignar"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Load More button -->
            <div class="flex justify-center mt-6">
                <button @click="loadMore()" :disabled="loadingMore" class="px-6 py-3 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-2 border dark:border-white/[0.06] border-gray-200">
                    <svg x-show="!loadingMore" class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <svg x-show="loadingMore" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="loadingMore ? _t.cargando_mas : _t.cargar_mas"></span>
                </button>
            </div>
        </div>

        <!-- Empty search states -->
        <div x-show="searchResults.length===0 && !searching && !searchDone" class="text-center py-16">
            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-500/10 to-nexo-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 dark:text-nexo-400/40 text-nexo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <h3 class="font-bold mb-1 dark:text-white/60 text-gray-600"><?php echo __('ls_busca_desc'); ?></h3>
            <p class="text-sm dark:text-white/30 text-gray-400 max-w-md mx-auto"><?php echo __('ls_busca_desc2'); ?> <?php echo __('ls_escanea_desc'); ?></p>
        </div>
        <div x-show="searchResults.length===0 && searchDone && !searching" class="text-center py-16">
            <h3 class="font-bold mb-1 dark:text-white/60 text-gray-600"><?php echo __('ls_no_resultados'); ?></h3>
            <p class="text-sm dark:text-white/30 text-gray-400"><?php echo __('ls_otro_nicho'); ?></p>
        </div>
    </div>

    <!-- ═══════ MIS LEADS TAB ═══════ -->
    <div x-show="activeTab === 'leads'" x-cloak class="space-y-4">

    <!-- Toolbar: Search + Filters + Actions -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="<?php echo __('ls_buscar_ph'); ?>" class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
            </div>
            <select x-model="filterNicho" class="px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none sm:w-44">
                <option value=""><?php echo __('ls_todos_nichos'); ?></option>
                <template x-for="n in nichos" :key="n"><option :value="n" x-text="n"></option></template>
            </select>
            <select x-model="filterEstado" class="px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none sm:w-36">
                <option value=""><?php echo __('ls_todos'); ?></option>
                <option value="nuevo"><?php echo __('ls_nuevos'); ?></option>
                <option value="contactado"><?php echo __('ls_contactados'); ?></option>
                <option value="convertido"><?php echo __('ls_convertidos'); ?></option>
                <option value="descartado"><?php echo __('ls_descartados'); ?></option>
            </select>
            <div class="flex gap-2 shrink-0">
                <button @click="showImportModal = true" class="px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="hidden sm:inline"><?php echo __('ls_importar_csv'); ?></span>
                </button>
                <button @click="openNewLead()" class="btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <?php echo __('ls_nuevo_lead'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Leads Grid -->
    <div x-show="filteredLeads.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="lead in filteredLeads" :key="lead.id">
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden hover:shadow-lg hover:shadow-nexo-500/5 transition-all group" :class="lead.estado === 'descartado' ? 'opacity-50' : ''">
                <div class="h-1" :style="'background:' + estadoColor(lead.estado)"></div>
                <div class="p-4">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-sm leading-tight" x-text="lead.nombre_empresa"></h3>
                            <p x-show="lead.nombre_contacto" class="text-xs dark:text-nexo-300 text-nexo-600 font-medium mt-0.5" x-text="lead.nombre_contacto + (lead.cargo ? ' — ' + lead.cargo : '')"></p>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0 ml-2" :style="'background:' + estadoColor(lead.estado) + '18; color:' + estadoColor(lead.estado)" x-text="lead.estado.charAt(0).toUpperCase() + lead.estado.slice(1)"></span>
                    </div>

                    <p x-show="lead.nicho" class="text-[10px] dark:text-white/30 text-gray-400 mb-2" x-text="_t.nicho_prefix + ' ' + lead.nicho"></p>

                    <!-- Contact info -->
                    <div class="space-y-1.5 mb-3">
                        <div x-show="lead.telefono" class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <a :href="'tel:'+lead.telefono" class="text-xs dark:text-white/70 text-gray-600 font-medium hover:text-nexo-400" x-text="lead.telefono"></a>
                        </div>
                        <div x-show="lead.whatsapp" class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-green-400 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.616l4.556-1.468A11.956 11.956 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.37 0-4.567-.82-6.29-2.19l-.44-.36-2.715.875.862-2.646-.378-.463A9.955 9.955 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                            <a :href="'https://wa.me/'+lead.whatsapp.replace(/[^0-9]/g,'')" target="_blank" class="text-xs text-green-400 hover:underline" x-text="lead.whatsapp"></a>
                        </div>
                        <div x-show="lead.email" class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <a :href="'mailto:'+lead.email" class="text-xs text-amber-400 hover:underline truncate" x-text="lead.email"></a>
                        </div>
                        <div x-show="lead.sitio_web" class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            <a :href="lead.sitio_web" target="_blank" class="text-xs text-blue-400 hover:underline truncate" x-text="lead.sitio_web.replace(/^https?:\/\//, '').replace(/\/$/, '')"></a>
                        </div>
                        <div x-show="lead.direccion" class="flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 dark:text-white/30 text-gray-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-[11px] dark:text-white/40 text-gray-400 leading-tight" x-text="lead.direccion"></span>
                        </div>
                    </div>

                    <!-- Assigned + Description -->
                    <div x-show="lead.asignado_nombre" class="mb-3 px-2.5 py-1.5 rounded-lg dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100">
                        <span class="text-[10px] dark:text-white/30 text-gray-400"><?php echo __('ls_asignado_a'); ?>: </span>
                        <span class="text-xs font-medium" x-text="lead.asignado_nombre"></span>
                    </div>
                    <p x-show="lead.descripcion" class="text-[11px] dark:text-white/40 text-gray-400 mb-3 line-clamp-2" x-text="lead.descripcion"></p>

                    <!-- Actions -->
                    <div class="flex gap-1.5 pt-3 border-t dark:border-white/[0.06] border-gray-100">
                        <button @click="openEdit(lead)" class="flex-1 px-2 py-1.5 rounded-lg text-[10px] font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors flex items-center justify-center gap-1" title="Editar lead">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <?php echo __('ls_editar'); ?>
                        </button>
                        <button @click="openAssign(lead)" class="flex-1 px-2 py-1.5 rounded-lg text-[10px] font-medium bg-nexo-500/10 text-nexo-400 hover:bg-nexo-500/20 transition-colors flex items-center justify-center gap-1" title="<?php echo __('ls_asignar'); ?>">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?php echo __('ls_asignar'); ?>
                        </button>
                        <button @click="sendToPipeline(lead)" class="flex-1 px-2 py-1.5 rounded-lg text-[10px] font-medium bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors flex items-center justify-center gap-1" x-show="lead.estado !== 'convertido'" title="Enviar al pipeline">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <?php echo __('ls_pipeline'); ?>
                        </button>
                        <span x-show="lead.estado === 'convertido'" class="flex-1 px-2 py-1.5 rounded-lg text-[10px] font-medium bg-emerald-500/10 text-emerald-400 flex items-center justify-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?php echo __('ls_convertido'); ?>
                        </span>
                        <button @click="deleteLead(lead)" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-500/10 transition-colors shrink-0" title="<?php echo __('ls_eliminar'); ?>">
                            <svg class="w-3.5 h-3.5 dark:text-white/30 text-gray-400 hover:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty state -->
    <div x-show="filteredLeads.length === 0" class="text-center py-20">
        <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-nexo-500/10 to-purple-600/10 flex items-center justify-center">
            <svg class="w-10 h-10 dark:text-nexo-400/40 text-nexo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <h3 class="font-bold mb-1 dark:text-white/60 text-gray-600" x-text="leads.length === 0 ? _t.sin_leads : _t.sin_resultados"></h3>
        <p class="text-sm dark:text-white/30 text-gray-400 max-w-md mx-auto" x-text="leads.length === 0 ? _t.sin_leads_desc : _t.sin_resultados_filtro"></p>
        <div x-show="leads.length === 0" class="flex gap-3 justify-center mt-5">
            <button @click="openNewLead()" class="btn-purple px-5 py-2.5 rounded-xl text-sm font-medium text-white flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <?php echo __('ls_crear_lead'); ?>
            </button>
            <button @click="showImportModal = true" class="px-5 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <?php echo __('ls_importar_csv'); ?>
            </button>
        </div>
    </div>
    </div><!-- end leads tab -->

    <!-- New / Edit Lead Modal -->
    <template x-if="showLeadModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showLeadModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showLeadModal = false"></div>
            <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="h-1 bg-nexo-500"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-bold" x-text="editingLead ? _t.editar_lead : _t.nuevo_lead"></h3>
                        <button @click="showLeadModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100"><svg class="w-5 h-5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="saveLead()" class="space-y-4">
                        <!-- Business info -->
                        <div class="space-y-3">
                            <p class="text-xs font-semibold dark:text-white/50 text-gray-400 uppercase tracking-wider"><?php echo __('ls_empresa'); ?></p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2"><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_nombre_empresa'); ?> *</label><input type="text" x-model="leadForm.nombre_empresa" required placeholder="Acme Corp" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_nicho_sector'); ?></label>
                                    <select x-model="leadForm.nicho" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                        <option value=""><?php echo __('ls_seleccionar_opt'); ?></option>
                                        <option>Restaurantes</option><option>Inmobiliarias</option><option>Clínicas</option><option>Abogados</option><option>Gimnasios</option><option>Hoteles</option><option>Tiendas de ropa</option><option>Salones de belleza</option><option>Dentistas</option><option>Arquitectos</option><option>Talleres mecánicos</option><option>Escuelas</option><option>Veterinarias</option><option>Spas</option><option>Constructoras</option><option>Contadores</option><option>Concesionarios</option><option>Agencias de marketing</option><option>Tecnología</option><option>Otro</option>
                                    </select>
                                </div>
                                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_sitio_web'); ?></label><input type="url" x-model="leadForm.sitio_web" placeholder="https://..." class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                            </div>
                            <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_direccion'); ?></label><input type="text" x-model="leadForm.direccion" placeholder="Ciudad, País" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                        </div>
                        <!-- Contact info -->
                        <div class="space-y-3">
                            <p class="text-xs font-semibold dark:text-white/50 text-gray-400 uppercase tracking-wider"><?php echo __('ls_contacto'); ?></p>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_nombre_contacto'); ?></label><input type="text" x-model="leadForm.nombre_contacto" placeholder="Juan Pérez" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_cargo'); ?></label><input type="text" x-model="leadForm.cargo" placeholder="CEO, Director..." class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_email'); ?></label><input type="email" x-model="leadForm.email" placeholder="contacto@empresa.com" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_telefono'); ?></label><input type="text" x-model="leadForm.telefono" placeholder="+55 11 9999-9999" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                                <div class="col-span-2"><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_whatsapp'); ?></label><input type="text" x-model="leadForm.whatsapp" placeholder="+55 11 99999-9999" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                            </div>
                        </div>
                        <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_descripcion'); ?></label><textarea x-model="leadForm.descripcion" rows="2" placeholder="<?php echo __('ls_desc_ph'); ?>" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none"></textarea></div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showLeadModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                            <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white">
                                <span x-text="editingLead ? _t.guardar_cambios : _t.crear_lead"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- Import CSV Modal -->
    <template x-if="showImportModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showImportModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showImportModal = false"></div>
            <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden">
                <div class="h-1 bg-nexo-500"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold"><?php echo __('ls_importar_desde_csv'); ?></h3>
                        <button @click="showImportModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100"><svg class="w-5 h-5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <div class="dark:bg-white/[0.03] bg-gray-50 rounded-xl border dark:border-white/[0.06] border-gray-200 p-4 mb-4">
                        <p class="text-xs dark:text-white/50 text-gray-500 font-medium mb-2"><?php echo __('ls_columnas_csv'); ?></p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">nombre_empresa*</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">nombre_contacto</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">cargo</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">email</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">telefono</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">whatsapp</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">sitio_web</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">direccion</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">nicho</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-md dark:bg-white/[0.06] bg-gray-200 font-mono">descripcion</span>
                        </div>
                        <p class="text-[10px] dark:text-white/30 text-gray-400 mt-2"><?php echo __('ls_acepta_tb'); ?></p>
                    </div>

                    <form @submit.prevent="importCSV()" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_nicho_defecto'); ?></label>
                            <select x-model="importNicho" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                <option value=""><?php echo __('ls_sin_nicho'); ?></option>
                                <option>Restaurantes</option><option>Inmobiliarias</option><option>Clínicas</option><option>Abogados</option><option>Gimnasios</option><option>Hoteles</option><option>Tiendas de ropa</option><option>Salones de belleza</option><option>Dentistas</option><option>Arquitectos</option><option>Talleres mecánicos</option><option>Escuelas</option><option>Veterinarias</option><option>Spas</option><option>Constructoras</option><option>Contadores</option><option>Concesionarios</option><option>Agencias de marketing</option><option>Tecnología</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed dark:border-white/10 border-gray-300 rounded-xl cursor-pointer dark:hover:border-nexo-500/30 hover:border-nexo-500/50 transition-colors"
                                   :class="csvFile ? 'dark:border-nexo-500/40 border-nexo-500' : ''">
                                <div class="flex flex-col items-center">
                                    <svg x-show="!csvFile" class="w-8 h-8 mb-1 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <svg x-show="csvFile" class="w-8 h-8 mb-1 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-xs dark:text-white/40 text-gray-400" x-text="csvFile ? csvFile.name : _t.click_csv"></p>
                                </div>
                                <input type="file" accept=".csv,.txt" class="hidden" @change="csvFile = $event.target.files[0]">
                            </label>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="showImportModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                            <button type="submit" :disabled="!csvFile || importing" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white disabled:opacity-50">
                                <span x-show="!importing"><?php echo __('ls_importar_btn'); ?></span>
                                <span x-show="importing"><?php echo __('ls_importando'); ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- Assign Modal (for Mis Leads) -->
    <template x-if="showAssignModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showAssignModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAssignModal = false"></div>
            <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden">
                <div class="h-1 bg-nexo-500"></div>
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-1"><?php echo __('ls_asignar_lead'); ?></h3>
                    <p class="text-xs dark:text-white/40 text-gray-400 mb-4" x-text="'<?php echo __('ls_asignar'); ?> ' + (assignTarget?.nombre_empresa || '') + ' <?php echo __('ls_a_un_funcionario'); ?>'"></p>
                    <select x-model="assignUserId" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 mb-4">
                        <option value=""><?php echo __('ls_seleccionar_func'); ?></option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="flex gap-3">
                        <button @click="showAssignModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                        <button @click="doAssign()" :disabled="!assignUserId" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white disabled:opacity-50"><?php echo __('ls_asignar'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Assign from Search Modal (teleported to fixed viewport center) -->
    <div x-show="showSearchAssignModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="position:fixed;top:0;left:0;right:0;bottom:0;" @keydown.escape.window="showSearchAssignModal = false">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showSearchAssignModal = false"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden" @click.stop>
            <div class="h-1 bg-nexo-500"></div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold"><?php echo __('ls_asignar_cliente'); ?></h3>
                        <p class="text-xs dark:text-white/40 text-gray-400 mt-1" x-text="(searchAssignTarget?.nombre || '')"></p>
                    </div>
                    <button @click="showSearchAssignModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors"><svg class="w-5 h-5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="space-y-3 mb-5">
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_funcionario'); ?> *</label>
                        <select x-model="searchAssignUserId" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value=""><?php echo __('ls_seleccionar_func'); ?></option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_etapa'); ?></label>
                        <select x-model="searchAssignEtapa" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                            <option value="nuevo"><?php echo __('ls_nuevo'); ?></option>
                            <option value="contactado"><?php echo __('ls_contactado'); ?></option>
                            <option value="negociando"><?php echo __('ls_negociando'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="showSearchAssignModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button @click="doAssignFromSearch()" :disabled="!searchAssignUserId" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white disabled:opacity-50"><?php echo __('ls_asignar'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Modal -->
    <template x-if="showPipelineModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showPipelineModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPipelineModal = false"></div>
            <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden">
                <div class="h-1 bg-emerald-500"></div>
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-1"><?php echo __('ls_enviar_pipeline'); ?></h3>
                    <p class="text-xs dark:text-white/40 text-gray-400 mb-4" x-text="pipelineTarget?.nombre_empresa || ''"></p>
                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_asignar'); ?></label>
                            <select x-model="pipelineUserId" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                <option value=""><?php echo __('ls_sin_asignar'); ?></option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('ls_etapa'); ?></label>
                            <select x-model="pipelineEtapa" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                <option value="nuevo"><?php echo __('ls_nuevo'); ?></option>
                                <option value="contactado"><?php echo __('ls_contactado'); ?></option>
                                <option value="negociando"><?php echo __('ls_negociando'); ?></option>
                                <option value="propuesta"><?php echo __('ls_propuesta'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showPipelineModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                        <button @click="doPipeline()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 transition-colors"><?php echo __('ls_enviar'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Toast -->
    <div x-show="toast" x-transition.opacity class="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl text-sm font-medium text-white shadow-lg"
         :class="toastType === 'ok' ? 'bg-emerald-500' : 'bg-red-500'" x-text="toast"
         x-init="$watch('toast', v => { if(v) setTimeout(()=> toast = '', 3500) })">
    </div>

</div>
</main>

<script>
const _t = {
    buscando: <?php echo json_encode(__('ls_buscando')); ?>,
    buscar: <?php echo json_encode(__('ls_buscar')); ?>,
    deseleccionar: <?php echo json_encode(__('ls_deseleccionar')); ?>,
    seleccionar_todos: <?php echo json_encode(__('ls_seleccionar_todos')); ?>,
    resultados_para: <?php echo json_encode(__('ls_resultados_para')); ?>,
    seleccionado: <?php echo json_encode(__('ls_seleccionado')); ?>,
    seleccionados: <?php echo json_encode(__('ls_seleccionados')); ?>,
    pagina: <?php echo json_encode(__('ls_pagina')); ?>,
    asignando: <?php echo json_encode(__('ls_asignando')); ?>,
    asignar_sel: <?php echo json_encode(__('ls_asignar_sel')); ?>,
    escaneado: <?php echo json_encode(__('ls_escaneado')); ?>,
    escaneando: <?php echo json_encode(__('ls_escaneando')); ?>,
    escanear_web: <?php echo json_encode(__('ls_escanear_web')); ?>,
    asignado: <?php echo json_encode(__('ls_asignado')); ?>,
    asignar: <?php echo json_encode(__('ls_asignar')); ?>,
    cargando_mas: <?php echo json_encode(__('ls_cargando_mas')); ?>,
    cargar_mas: <?php echo json_encode(__('ls_cargar_mas')); ?>,
    editar_lead: <?php echo json_encode(__('ls_editar_lead')); ?>,
    nuevo_lead: <?php echo json_encode(__('ls_nuevo_lead')); ?>,
    guardar_cambios: <?php echo json_encode(__('ls_guardar_cambios')); ?>,
    crear_lead: <?php echo json_encode(__('ls_crear_lead')); ?>,
    click_csv: <?php echo json_encode(__('ls_click_csv')); ?>,
    sin_leads: <?php echo json_encode(__('ls_sin_leads')); ?>,
    sin_resultados: <?php echo json_encode(__('ls_no_resultados')); ?>,
    sin_leads_desc: <?php echo json_encode(__('ls_sin_leads_desc')); ?>,
    sin_resultados_filtro: <?php echo json_encode(__('ls_sin_resultados_filtro')); ?>,
    lead_actualizado: <?php echo json_encode(__('ls_lead_actualizado')); ?>,
    lead_creado: <?php echo json_encode(__('ls_lead_creado')); ?>,
    error_conexion: <?php echo json_encode(__('ls_error_conexion')); ?>,
    leads_importados: <?php echo json_encode(__('ls_leads_importados')); ?>,
    omitidos: <?php echo json_encode(__('ls_omitidos')); ?>,
    error_importacion: <?php echo json_encode(__('ls_error_importacion')); ?>,
    lead_asignado: <?php echo json_encode(__('ls_lead_asignado')); ?>,
    lead_pipeline: <?php echo json_encode(__('ls_lead_pipeline')); ?>,
    lead_eliminado: <?php echo json_encode(__('ls_lead_eliminado')); ?>,
    confirmar_eliminar: <?php echo json_encode(__('ls_confirmar_eliminar')); ?>,
    no_escanear: <?php echo json_encode(__('ls_no_escanear')); ?>,
    error_escanear: <?php echo json_encode(__('ls_error_escanear')); ?>,
    cliente_asignado: <?php echo json_encode(__('ls_cliente_asignado')); ?>,
    selecciona_lead: <?php echo json_encode(__('ls_selecciona_lead')); ?>,
    clientes_asignados: <?php echo json_encode(__('ls_clientes_asignados')); ?>,
    mas_cargados: <?php echo json_encode(__('ls_mas_cargados')); ?>,
    no_mas: <?php echo json_encode(__('ls_no_mas')); ?>,
    error_busqueda: <?php echo json_encode(__('ls_error_busqueda')); ?>,
    nicho_prefix: <?php echo json_encode(__('ls_nicho_prefix')); ?>,
    lead: 'lead',
    leads: 'leads',
};
function leadScraperApp() {
    return {
        leads: [],
        search: '',
        filterNicho: '',
        filterEstado: '',
        // Modals
        showLeadModal: false,
        showImportModal: false,
        showAssignModal: false,
        showPipelineModal: false,
        showSearchAssignModal: false,
        editingLead: null,
        leadForm: {},
        // Import
        csvFile: null,
        importNicho: '',
        importing: false,
        // Assign (Mis Leads)
        assignTarget: null,
        assignUserId: '',
        // Assign from search
        searchAssignTarget: null,
        searchAssignUserId: '',
        searchAssignEtapa: 'nuevo',
        // Bulk assign
        bulkAssignUserId: '',
        bulkEtapa: 'nuevo',
        bulkAssigning: false,
        // Pipeline
        pipelineTarget: null,
        pipelineUserId: '',
        pipelineEtapa: 'nuevo',
        // Toast
        toast: '',
        toastType: 'ok',
        // Search
        activeTab: 'buscar',
        searchNicho: '',
        searchLocation: '',
        searching: false,
        searchDone: false,
        searchResults: [],
        lastQuery: '',
        searchPage: 0,
        loadingMore: false,

        get nichos() {
            const set = new Set(this.leads.map(l => l.nicho).filter(Boolean));
            return [...set].sort();
        },

        get filteredLeads() {
            return this.leads.filter(l => {
                if (this.filterEstado && l.estado !== this.filterEstado) return false;
                if (this.filterNicho && l.nicho !== this.filterNicho) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (l.nombre_empresa||'').toLowerCase().includes(q) ||
                           (l.nombre_contacto||'').toLowerCase().includes(q) ||
                           (l.email||'').toLowerCase().includes(q) ||
                           (l.telefono||'').toLowerCase().includes(q) ||
                           (l.nicho||'').toLowerCase().includes(q);
                }
                return true;
            });
        },

        get visibleResults() {
            return this.searchResults.filter(r => !r.dismissed);
        },

        get selectedCount() {
            return this.searchResults.filter(r => r.selected && !r.dismissed && !r.assigned).length;
        },

        get allSelected() {
            const visible = this.visibleResults.filter(r => !r.assigned);
            return visible.length > 0 && visible.every(r => r.selected);
        },

        toggleSelectAll() {
            const visible = this.visibleResults.filter(r => !r.assigned);
            const allSel = visible.every(r => r.selected);
            visible.forEach(r => r.selected = !allSel);
        },

        estadoColor(e) {
            const m = { nuevo:'#3b82f6', contactado:'#f59e0b', convertido:'#22c55e', descartado:'#6b7280' };
            return m[e] || '#6b7280';
        },

        emptyForm() {
            return { nombre_empresa:'', nombre_contacto:'', cargo:'', nicho:'', email:'', telefono:'', whatsapp:'', sitio_web:'', direccion:'', descripcion:'' };
        },

        openNewLead() {
            this.editingLead = null;
            this.leadForm = this.emptyForm();
            this.showLeadModal = true;
        },

        openEdit(lead) {
            this.editingLead = lead;
            this.leadForm = {
                nombre_empresa: lead.nombre_empresa||'', nombre_contacto: lead.nombre_contacto||'',
                cargo: lead.cargo||'', nicho: lead.nicho||'', email: lead.email||'',
                telefono: lead.telefono||'', whatsapp: lead.whatsapp||'',
                sitio_web: lead.sitio_web||'', direccion: lead.direccion||'', descripcion: lead.descripcion||''
            };
            this.showLeadModal = true;
        },

        async saveLead() {
            if (!this.leadForm.nombre_empresa) return;
            const fd = new FormData();
            if (this.editingLead) {
                fd.append('action', 'update');
                fd.append('id', this.editingLead.id);
                // Send all fields for update
                Object.entries(this.leadForm).forEach(([k,v]) => fd.append(k, v));
            } else {
                fd.append('action', 'save');
                Object.entries(this.leadForm).forEach(([k,v]) => fd.append(k, v));
            }
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.showLeadModal = false;
                    this.toast = this.editingLead ? _t.lead_actualizado : _t.lead_creado;
                    this.toastType = 'ok';
                    await this.loadLeads();
                } else {
                    this.toast = d.error || 'Error'; this.toastType = 'err';
                }
            } catch(e) { this.toast = _t.error_conexion; this.toastType = 'err'; }
        },

        async importCSV() {
            if (!this.csvFile) return;
            this.importing = true;
            const fd = new FormData();
            fd.append('action', 'import_csv');
            fd.append('csv_file', this.csvFile);
            fd.append('nicho', this.importNicho);
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.showImportModal = false;
                    this.csvFile = null;
                    this.toast = `${d.inserted} ${_t.leads_importados}` + (d.skipped ? `, ${d.skipped} ${_t.omitidos}` : '');
                    this.toastType = 'ok';
                    await this.loadLeads();
                } else {
                    this.toast = d.error || _t.error_importacion; this.toastType = 'err';
                }
            } catch(e) { this.toast = _t.error_conexion; this.toastType = 'err'; }
            this.importing = false;
        },

        async loadLeads() {
            try {
                const r = await fetch('api/leadscraper.php?action=list');
                const d = await r.json();
                if (d.ok) this.leads = d.leads;
            } catch(e) {}
        },

        openAssign(lead) {
            this.assignTarget = lead;
            this.assignUserId = lead.asignado_a || '';
            this.showAssignModal = true;
        },

        async doAssign() {
            if (!this.assignTarget) return;
            const fd = new FormData();
            fd.append('action', 'assign');
            fd.append('id', this.assignTarget.id);
            fd.append('user_id', this.assignUserId);
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.showAssignModal = false;
                    this.toast = _t.lead_asignado;
                    this.toastType = 'ok';
                    await this.loadLeads();
                }
            } catch(e) { this.toast = 'Error'; this.toastType = 'err'; }
        },

        sendToPipeline(lead) {
            this.pipelineTarget = lead;
            this.pipelineUserId = lead.asignado_a || '';
            this.pipelineEtapa = 'nuevo';
            this.showPipelineModal = true;
        },

        async doPipeline() {
            if (!this.pipelineTarget) return;
            const fd = new FormData();
            fd.append('action', 'to_pipeline');
            fd.append('id', this.pipelineTarget.id);
            fd.append('asignado_a', this.pipelineUserId);
            fd.append('etapa', this.pipelineEtapa);
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.showPipelineModal = false;
                    this.toast = _t.lead_pipeline;
                    this.toastType = 'ok';
                    await this.loadLeads();
                } else {
                    this.toast = d.error || 'Error'; this.toastType = 'err';
                }
            } catch(e) { this.toast = _t.error_conexion; this.toastType = 'err'; }
        },

        async deleteLead(lead) {
            if (!confirm(_t.confirmar_eliminar)) return;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', lead.id);
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.leads = this.leads.filter(l => l.id !== lead.id);
                    this.toast = _t.lead_eliminado;
                    this.toastType = 'ok';
                }
            } catch(e) {}
        },

        // ─── Search & Enrichment ───
        classifyEmail(em) {
            const low = em.toLowerCase();
            if (/^(info|contacto|contact|hola|hello|general)@/.test(low)) return 'General';
            if (/^(ventas|sales|comercial)@/.test(low)) return 'Ventas';
            if (/^(admin|administracion|rrhh|hr|contabilidad|finanzas)@/.test(low)) return 'Admin';
            if (/^(soporte|support|ayuda|help)@/.test(low)) return 'Soporte';
            if (/^(ceo|director|gerente|owner|fundador)@/.test(low)) return 'Dueño';
            if (/^(marketing|publicidad|prensa|press|media)@/.test(low)) return 'Marketing';
            return 'Personal';
        },

        classifySocial(url) {
            if (/linkedin\.com/i.test(url)) return 'LinkedIn';
            if (/instagram\.com/i.test(url)) return 'Instagram';
            if (/facebook\.com/i.test(url)) return 'Facebook';
            if (/twitter\.com|x\.com/i.test(url)) return 'Twitter/X';
            return 'Social';
        },

        mapResult(r) {
            return { ...r, enriched:false, enriching:false, allEmails:[], allPhones:[], social:[], whatsapp:'', address:'', contactName:'', contactRole:'', saved:false, savedId:null, dismissed:false, isDuplicate:false, duplicateInfo:'', selected:false, assigned:false, assignedTo:'' };
        },

        async checkDuplicates(results) {
            const checks = results.map((r, i) => ({ idx:i, email:r.email||'', telefono:r.telefono||'', sitio_web:r.sitio_web||'', nombre:r.nombre||'' }));
            try {
                const resp = await fetch('api/leadscraper.php?action=check_duplicates', {
                    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(checks)
                });
                const d = await resp.json();
                if (d.ok && d.duplicates) {
                    d.duplicates.forEach(dup => {
                        const sr = this.searchResults[dup.idx];
                        if (sr) {
                            sr.isDuplicate = true;
                            sr.duplicateInfo = dup.cliente_nombre + (dup.cliente_empresa ? ' (' + dup.cliente_empresa + ')' : '');
                        }
                    });
                }
            } catch(e) {}
        },

        async doSearch() {
            if (!this.searchNicho || this.searching) return;
            this.searching = true;
            this.searchDone = false;
            this.searchResults = [];
            this.searchPage = 0;
            try {
                const params = new URLSearchParams({ action:'search', nicho:this.searchNicho, location:this.searchLocation, page:'0' });
                const r = await fetch('api/leadscraper.php?' + params);
                const d = await r.json();
                if (d.ok) {
                    this.searchResults = d.results.map(r => this.mapResult(r));
                    this.lastQuery = d.query || '';
                    await this.checkDuplicates(this.searchResults);
                } else {
                    this.toast = d.error || _t.error_busqueda; this.toastType = 'err';
                }
            } catch(e) { this.toast = 'Error de conexión'; this.toastType = 'err'; }
            this.searching = false;
            this.searchDone = true;
        },

        async loadMore() {
            if (this.loadingMore || !this.searchNicho) return;
            this.loadingMore = true;
            this.searchPage++;
            try {
                const params = new URLSearchParams({ action:'search', nicho:this.searchNicho, location:this.searchLocation, page: String(this.searchPage) });
                const r = await fetch('api/leadscraper.php?' + params);
                const d = await r.json();
                if (d.ok && d.results.length > 0) {
                    const existingUrls = new Set(this.searchResults.map(s => s.sitio_web));
                    const newResults = d.results.filter(r => !existingUrls.has(r.sitio_web)).map(r => this.mapResult(r));
                    const startIdx = this.searchResults.length;
                    this.searchResults.push(...newResults);
                    // Check duplicates for new results
                    const checks = newResults.map((r, i) => ({ idx: startIdx + i, email:r.email||'', telefono:r.telefono||'', sitio_web:r.sitio_web||'', nombre:r.nombre||'' }));
                    if (checks.length) {
                        try {
                            const resp = await fetch('api/leadscraper.php?action=check_duplicates', {
                                method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(checks)
                            });
                            const dd = await resp.json();
                            if (dd.ok && dd.duplicates) {
                                dd.duplicates.forEach(dup => {
                                    const sr = this.searchResults[dup.idx];
                                    if (sr) { sr.isDuplicate = true; sr.duplicateInfo = dup.cliente_nombre + (dup.cliente_empresa ? ' (' + dup.cliente_empresa + ')' : ''); }
                                });
                            }
                        } catch(e) {}
                    }
                    this.toast = newResults.length + ' ' + _t.mas_cargados; this.toastType = 'ok';
                } else {
                    this.toast = _t.no_mas; this.toastType = 'err';
                    this.searchPage--;
                }
            } catch(e) { this.toast = 'Error de conexión'; this.toastType = 'err'; this.searchPage--; }
            this.loadingMore = false;
        },

        async enrichResult(sr) {
            if (sr.enriching || sr.enriched) return;
            sr.enriching = true;
            try {
                const r = await fetch('api/leadscraper.php?action=enrich_url&url=' + encodeURIComponent(sr.sitio_web));
                const d = await r.json();
                if (d.ok) {
                    sr.allEmails = d.emails || [];
                    sr.allPhones = d.phones || [];
                    sr.social = d.social || [];
                    if (d.whatsapp) sr.whatsapp = d.whatsapp;
                    if (d.address) sr.address = d.address;
                    if (d.contact_name) sr.contactName = d.contact_name;
                    if (d.contact_role) sr.contactRole = d.contact_role;
                    if (!sr.email && d.emails.length) sr.email = d.emails[0];
                    if (!sr.telefono && d.phones.length) sr.telefono = d.phones[0];
                    sr.enriched = true;
                    // Re-check duplicate with enriched data
                    if (!sr.isDuplicate && (sr.email || sr.telefono)) {
                        const idx = this.searchResults.indexOf(sr);
                        const checks = [{ idx, email:sr.email||'', telefono:sr.telefono||'', sitio_web:sr.sitio_web||'', nombre:sr.nombre||'' }];
                        try {
                            const resp = await fetch('api/leadscraper.php?action=check_duplicates', {
                                method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(checks)
                            });
                            const dd = await resp.json();
                            if (dd.ok && dd.duplicates && dd.duplicates.length) {
                                sr.isDuplicate = true;
                                sr.duplicateInfo = dd.duplicates[0].cliente_nombre + (dd.duplicates[0].cliente_empresa ? ' (' + dd.duplicates[0].cliente_empresa + ')' : '');
                            }
                        } catch(e) {}
                    }
                } else {
                    this.toast = d.error || _t.no_escanear; this.toastType = 'err';
                }
            } catch(e) { this.toast = _t.error_escanear; this.toastType = 'err'; }
            sr.enriching = false;
        },

        // ─── Assign from search (single) ───
        openAssignFromSearch(sr) {
            if (sr.assigned) return;
            this.searchAssignTarget = sr;
            this.searchAssignUserId = '';
            this.searchAssignEtapa = 'nuevo';
            this.showSearchAssignModal = true;
        },

        async doAssignFromSearch() {
            const sr = this.searchAssignTarget;
            if (!sr || !this.searchAssignUserId) return;
            const fd = new FormData();
            fd.append('action', 'assign_from_search');
            fd.append('nombre', sr.nombre || '');
            fd.append('email', sr.email || (sr.allEmails && sr.allEmails[0]) || '');
            fd.append('telefono', sr.telefono || (sr.allPhones && sr.allPhones[0]) || '');
            fd.append('sitio_web', sr.sitio_web || '');
            fd.append('whatsapp', sr.whatsapp || '');
            fd.append('direccion', sr.address || '');
            fd.append('nicho', this.searchNicho || '');
            fd.append('descripcion', sr.snippet || '');
            fd.append('nombre_contacto', sr.contactName || '');
            fd.append('asignado_a', this.searchAssignUserId);
            fd.append('etapa', this.searchAssignEtapa);
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    sr.assigned = true;
                    sr.selected = false;
                    const sel = document.querySelector(`select[x-model="searchAssignUserId"]`);
                    const optText = sel ? sel.options[sel.selectedIndex]?.text : '';
                    sr.assignedTo = optText || 'funcionario';
                    this.showSearchAssignModal = false;
                    this.toast = _t.cliente_asignado; this.toastType = 'ok';
                } else {
                    this.toast = d.error || 'Error'; this.toastType = 'err';
                }
            } catch(e) { this.toast = _t.error_conexion; this.toastType = 'err'; }
        },

        // ─── Bulk assign from search ───
        async bulkAssign() {
            if (!this.bulkAssignUserId || this.bulkAssigning) return;
            const selected = this.searchResults.filter(r => r.selected && !r.dismissed && !r.assigned);
            if (!selected.length) { this.toast = _t.selecciona_lead; this.toastType = 'err'; return; }
            this.bulkAssigning = true;
            const items = selected.map(sr => ({
                nombre: sr.nombre || '',
                email: sr.email || (sr.allEmails && sr.allEmails[0]) || '',
                telefono: sr.telefono || (sr.allPhones && sr.allPhones[0]) || '',
                sitio_web: sr.sitio_web || '',
                whatsapp: sr.whatsapp || '',
                direccion: sr.address || '',
                descripcion: sr.snippet || '',
                nombre_contacto: sr.contactName || ''
            }));
            const fd = new FormData();
            fd.append('action', 'bulk_assign_from_search');
            fd.append('items', JSON.stringify(items));
            fd.append('asignado_a', this.bulkAssignUserId);
            fd.append('etapa', this.bulkEtapa);
            fd.append('nicho', this.searchNicho || '');
            try {
                const r = await fetch('api/leadscraper.php', { method:'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    const selOpt = document.querySelector(`select[x-model="bulkAssignUserId"]`);
                    const userName = selOpt ? selOpt.options[selOpt.selectedIndex]?.text : '';
                    selected.forEach(sr => { sr.assigned = true; sr.selected = false; sr.assignedTo = userName || 'funcionario'; });
                    this.toast = d.created + ' ' + _t.clientes_asignados; this.toastType = 'ok';
                } else {
                    this.toast = d.error || 'Error'; this.toastType = 'err';
                }
            } catch(e) { this.toast = _t.error_conexion; this.toastType = 'err'; }
            this.bulkAssigning = false;
        },

        bulkDismiss() {
            this.searchResults.filter(r => r.selected && !r.dismissed && !r.assigned).forEach(r => { r.dismissed = true; r.selected = false; });
        },
    };
}
</script>

<?php include 'includes/footer.php'; ?>
