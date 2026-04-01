<?php
require_once 'includes/auth_check.php';
$pageTitle = 'Bóveda';
$currentPage = 'boveda';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-5" x-data="bovedaApp()" x-init="load()">

    <!-- Toast -->
    <div x-show="toast" x-transition.opacity class="fixed top-4 right-4 z-[60] px-4 py-2.5 rounded-xl text-sm font-medium text-white shadow-lg" :class="toastType === 'error' ? 'bg-red-600' : 'bg-emerald-600'" x-text="toast" x-cloak></div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-nexo-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-nexo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                Bóveda Segura
            </h2>
            <p class="text-xs dark:text-white/40 text-gray-400 mt-1">Almacenamiento cifrado de credenciales, archivos y enlaces</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showCatManager = true" class="px-3 py-2 rounded-xl text-xs font-medium dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Categorías
            </button>
            <button @click="openNew()" class="btn-purple px-4 py-2 rounded-xl text-xs font-medium text-white flex items-center gap-2 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Item
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 cursor-pointer transition-all" :class="filtroTipo === '' ? 'ring-2 ring-nexo-500/50' : 'hover:dark:border-white/10 hover:border-gray-300'" @click="filtroTipo = ''">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-nexo-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-nexo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider">Total</span>
            </div>
            <p class="text-2xl font-bold" x-text="items.length"></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5">items guardados</p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 cursor-pointer transition-all" :class="filtroTipo === 'password' ? 'ring-2 ring-amber-500/50' : 'hover:dark:border-white/10 hover:border-gray-300'" @click="filtroTipo = filtroTipo === 'password' ? '' : 'password'">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider">Claves</span>
            </div>
            <p class="text-2xl font-bold text-amber-500" x-text="items.filter(i=>i.tipo==='password').length"></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5">contraseñas</p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 cursor-pointer transition-all" :class="filtroTipo === 'file' ? 'ring-2 ring-blue-500/50' : 'hover:dark:border-white/10 hover:border-gray-300'" @click="filtroTipo = filtroTipo === 'file' ? '' : 'file'">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider">Archivos</span>
            </div>
            <p class="text-2xl font-bold text-blue-500" x-text="items.filter(i=>i.tipo==='file').length"></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5">documentos</p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 cursor-pointer transition-all" :class="filtroTipo === 'link' ? 'ring-2 ring-emerald-500/50' : 'hover:dark:border-white/10 hover:border-gray-300'" @click="filtroTipo = filtroTipo === 'link' ? '' : 'link'">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider">Links</span>
            </div>
            <p class="text-2xl font-bold text-emerald-500" x-text="items.filter(i=>i.tipo==='link').length"></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5">enlaces</p>
        </div>
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-3.5 cursor-pointer transition-all" :class="filtroTipo === 'note' ? 'ring-2 ring-purple-500/50' : 'hover:dark:border-white/10 hover:border-gray-300'" @click="filtroTipo = filtroTipo === 'note' ? '' : 'note'">
            <div class="flex items-center justify-between mb-2">
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center"><svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></div>
                <span class="text-[10px] dark:text-white/25 text-gray-300 uppercase tracking-wider">Notas</span>
            </div>
            <p class="text-2xl font-bold text-purple-500" x-text="items.filter(i=>i.tipo==='note').length"></p>
            <p class="text-[10px] dark:text-white/30 text-gray-400 mt-0.5">notas seguras</p>
        </div>
    </div>

    <!-- Search + Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="relative flex-1 max-w-sm">
            <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 dark:text-white/25 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="Buscar por título, URL, usuario..." class="w-full pl-9 pr-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
        </div>
        <select x-model="filtroCat" class="px-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
            <option value="">Todas las categorías</option>
            <template x-for="cat in categorias" :key="cat.id">
                <option :value="cat.id" x-text="cat.nombre + ' (' + cat.total + ')'"></option>
            </template>
        </select>
        <template x-if="filtroTipo || filtroCat || search">
            <button @click="filtroTipo = ''; filtroCat = ''; search = ''" class="px-3 py-2 text-xs rounded-lg dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200 transition-colors flex items-center gap-1.5">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Limpiar filtros
            </button>
        </template>
        <span class="text-[10px] dark:text-white/25 text-gray-400 ml-auto tabular-nums" x-text="filtered().length + ' de ' + items.length + ' items'"></span>
    </div>

    <!-- Items grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
        <template x-for="item in filtered()" :key="item.id">
        <div class="dark:bg-dark-800 bg-white rounded-xl border dark:border-white/[0.06] border-gray-200 p-4 space-y-3 hover:dark:border-white/10 hover:border-gray-300 transition-all group">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="tipoStyle(item.tipo).bg">
                        <svg class="w-4 h-4" :class="tipoStyle(item.tipo).text" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-html="tipoStyle(item.tipo).icon"></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-sm truncate" x-text="item.titulo"></p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-md" :style="'background:'+((item.cat_color||'#6b7280')+'15')+';color:'+(item.cat_color||'#6b7280')" x-text="item.cat_nombre || 'Sin categoría'"></span>
                            <span class="text-[10px] dark:text-white/25 text-gray-400" x-text="tipoLabel(item.tipo)"></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openEdit(item)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 dark:text-white/40 text-gray-400 hover:text-blue-500 transition-colors" title="Editar"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                    <button @click="confirmDel(item.id, item.titulo)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 dark:text-white/40 text-gray-400 hover:text-red-500 transition-colors" title="Eliminar"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </div>
            </div>

            <!-- Content based on type -->
            <!-- Password fields -->
            <template x-if="item.tipo === 'password'">
                <div class="space-y-2 dark:bg-white/[0.02] bg-gray-50/50 rounded-lg p-3">
                    <div x-show="item.usuario_campo" class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 dark:text-white/25 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span class="text-xs dark:text-white/60 text-gray-600 truncate flex-1 font-mono" x-text="item.usuario_campo"></span>
                        <button @click="copyText(item.usuario_campo)" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-md dark:hover:bg-white/10 hover:bg-gray-200 transition-colors" title="Copiar usuario"><svg class="w-3 h-3 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                    </div>
                    <div x-show="item.password_dec" class="flex items-center gap-2" x-data="{show:false}">
                        <svg class="w-3.5 h-3.5 dark:text-white/25 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span class="text-xs dark:text-white/60 text-gray-600 truncate flex-1 font-mono" x-text="show ? item.password_dec : '••••••••••••'"></span>
                        <button @click="show = !show" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-md dark:hover:bg-white/10 hover:bg-gray-200 transition-colors" title="Ver/Ocultar"><svg class="w-3 h-3 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                        <button @click="copyText(item.password_dec)" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-md dark:hover:bg-white/10 hover:bg-gray-200 transition-colors" title="Copiar clave"><svg class="w-3 h-3 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                    </div>
                    <div x-show="item.url" class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 dark:text-white/25 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        <a :href="item.url" target="_blank" class="text-xs text-nexo-400 hover:underline truncate flex-1" x-text="item.url"></a>
                    </div>
                </div>
            </template>

            <!-- File -->
            <template x-if="item.tipo === 'file'">
                <div x-show="item.archivo_nombre" class="flex items-center gap-3 dark:bg-white/[0.02] bg-gray-50/50 rounded-lg p-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium truncate" x-text="item.archivo_nombre"></p>
                        <p class="text-[10px] dark:text-white/25 text-gray-400" x-text="formatSize(item.archivo_size)"></p>
                    </div>
                    <a :href="'api/boveda.php?action=download&id='+item.id" class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-nexo-600/10 text-nexo-400 hover:bg-nexo-600/20 transition-colors" title="Descargar">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </template>

            <!-- Link -->
            <template x-if="item.tipo === 'link'">
                <div class="dark:bg-white/[0.02] bg-gray-50/50 rounded-lg p-3">
                    <a :href="item.url" target="_blank" class="flex items-center gap-2 text-xs text-nexo-400 hover:text-nexo-300 transition-colors group/link">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span class="truncate group-hover/link:underline" x-text="item.url"></span>
                    </a>
                </div>
            </template>

            <!-- Note -->
            <template x-if="item.tipo === 'note'">
                <div class="dark:bg-white/[0.02] bg-gray-50/50 rounded-lg p-3">
                    <p class="text-xs dark:text-white/50 text-gray-500 line-clamp-3 whitespace-pre-line" x-text="item.notas"></p>
                </div>
            </template>

            <!-- Notas (all types except note) -->
            <p x-show="item.notas && item.tipo !== 'note'" class="text-[11px] dark:text-white/25 text-gray-400 truncate italic" x-text="item.notas"></p>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-2 border-t dark:border-white/[0.04] border-gray-100">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3 h-3 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-[10px] dark:text-white/20 text-gray-300" x-text="item.creador"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3 h-3 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-[10px] dark:text-white/20 text-gray-300" x-text="timeAgo(item.actualizado_en)"></span>
                </div>
            </div>
        </div>
        </template>

        <template x-if="filtered().length === 0">
            <div class="col-span-full text-center py-20">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl dark:bg-white/5 bg-gray-50 flex items-center justify-center">
                    <svg class="w-7 h-7 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <p class="text-sm dark:text-white/30 text-gray-400 mb-1">No se encontraron items</p>
                <p class="text-xs dark:text-white/20 text-gray-300" x-show="search || filtroTipo || filtroCat">Prueba ajustando los filtros de búsqueda</p>
                <button x-show="!search && !filtroTipo && !filtroCat" @click="openNew()" class="mt-3 px-4 py-2 rounded-lg text-xs font-medium bg-nexo-600 text-white hover:bg-nexo-700 transition-colors">Crear primer item</button>
            </div>
        </template>
    </div>

    <!-- ========== MODAL NEW/EDIT ========== -->
    <div x-show="showModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl max-h-[90vh] overflow-y-auto" @click.outside="showModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-lg bg-nexo-500/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-nexo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold" x-text="editId ? 'Editar Item' : 'Nuevo Item'"></h3>
                        <p class="text-[11px] dark:text-white/30 text-gray-400" x-text="editId ? 'Modifica los campos necesarios' : 'Añade un nuevo elemento a la bóveda'"></p>
                    </div>
                </div>
                <form @submit.prevent="saveItem()" class="space-y-4">
                    <!-- Type selector (only for new) -->
                    <div x-show="!editId">
                        <label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-2 block uppercase tracking-wider">Tipo</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" @click="form.tipo = 'password'" class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl text-xs font-medium border-2 transition-all" :class="form.tipo === 'password' ? 'border-amber-500/50 dark:bg-amber-500/5 bg-amber-50' : 'border-transparent dark:bg-white/5 bg-gray-50 opacity-60'">
                                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Clave
                            </button>
                            <button type="button" @click="form.tipo = 'file'" class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl text-xs font-medium border-2 transition-all" :class="form.tipo === 'file' ? 'border-blue-500/50 dark:bg-blue-500/5 bg-blue-50' : 'border-transparent dark:bg-white/5 bg-gray-50 opacity-60'">
                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                Archivo
                            </button>
                            <button type="button" @click="form.tipo = 'link'" class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl text-xs font-medium border-2 transition-all" :class="form.tipo === 'link' ? 'border-emerald-500/50 dark:bg-emerald-500/5 bg-emerald-50' : 'border-transparent dark:bg-white/5 bg-gray-50 opacity-60'">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Link
                            </button>
                            <button type="button" @click="form.tipo = 'note'" class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl text-xs font-medium border-2 transition-all" :class="form.tipo === 'note' ? 'border-purple-500/50 dark:bg-purple-500/5 bg-purple-50' : 'border-transparent dark:bg-white/5 bg-gray-50 opacity-60'">
                                <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Nota
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">Título *</label>
                            <input type="text" x-model="form.titulo" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">Categoría</label>
                            <select x-model="form.categoria_id" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                                <option value="">Sin categoría</option>
                                <template x-for="cat in categorias" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.nombre"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Password fields -->
                    <template x-if="form.tipo === 'password'">
                        <div class="space-y-3">
                            <div><label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">Usuario / Email</label>
                                <input type="text" x-model="form.usuario_campo" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors" autocomplete="off"></div>
                            <div class="relative">
                                <label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block" x-text="editId ? 'Nueva Contraseña (vacío = mantener)' : 'Contraseña'"></label>
                                <input :type="showPass ? 'text' : 'password'" x-model="form.password_raw" class="w-full px-3 py-2.5 pr-20 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 font-mono transition-colors" autocomplete="new-password">
                                <div class="absolute right-2 top-[26px] flex gap-0.5">
                                    <button type="button" @click="showPass = !showPass" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>
                                    <button type="button" @click="form.password_raw = genPassword()" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-200 transition-colors" title="Generar"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                                </div>
                            </div>
                            <div><label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">URL / Sitio</label>
                                <input type="text" x-model="form.url" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors"></div>
                        </div>
                    </template>

                    <!-- File upload -->
                    <template x-if="form.tipo === 'file'">
                        <div>
                            <label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">Archivo</label>
                            <div class="border-2 border-dashed dark:border-white/10 border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-nexo-500/50 transition-colors" @click="$refs.fileInput.click()" @dragover.prevent @drop.prevent="handleDrop($event)">
                                <svg class="w-8 h-8 mx-auto mb-2 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <p class="text-xs dark:text-white/40 text-gray-400" x-text="fileName || 'Click o arrastra un archivo aquí'"></p>
                            </div>
                            <input type="file" x-ref="fileInput" @change="handleFile($event)" class="hidden">
                        </div>
                    </template>

                    <!-- Link -->
                    <template x-if="form.tipo === 'link'">
                        <div><label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">URL *</label>
                            <input type="url" x-model="form.url" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors" placeholder="https://..."></div>
                    </template>

                    <!-- Notes -->
                    <div><label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">Notas</label>
                        <textarea x-model="form.notas" rows="3" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none transition-colors"></textarea></div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                        <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white" x-text="saving ? 'Guardando...' : (editId ? 'Guardar Cambios' : 'Crear Item')" :disabled="saving"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL DELETE ========== -->
    <div x-show="showDel" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDel = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-red-500/10 flex items-center justify-center"><svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
            <h3 class="text-base font-bold mb-1">Eliminar Item</h3>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-5">¿Eliminar <strong x-text="delName"></strong>? Esta acción no se puede deshacer.</p>
            <div class="flex gap-3">
                <button @click="showDel = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                <button @click="deleteItem()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition-colors">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- ========== MODAL CATEGORY MANAGER ========== -->
    <div x-show="showCatManager" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showCatManager = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl" @click.outside="showCatManager = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-lg bg-nexo-500/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-nexo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Gestionar Categorías</h3>
                        <p class="text-[11px] dark:text-white/30 text-gray-400">Crea, organiza y elimina categorías</p>
                    </div>
                </div>

                <!-- New category form -->
                <form @submit.prevent="saveCat()" class="flex items-end gap-2 mb-5">
                    <div class="flex-1">
                        <label class="text-[11px] font-medium dark:text-white/40 text-gray-500 mb-1 block">Nueva categoría</label>
                        <input type="text" x-model="catForm.nombre" required placeholder="Nombre de la categoría..." class="w-full px-3 py-2 text-sm rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                    </div>
                    <div class="flex items-center gap-1 pb-0.5">
                        <template x-for="c in ['#7c3aed','#10b981','#3b82f6','#f59e0b','#ef4444','#ec4899','#6b7280','#06b6d4']" :key="c">
                            <button type="button" @click="catForm.color = c" class="w-6 h-6 rounded-full border-2 transition-all shrink-0" :style="'background:'+c" :class="catForm.color === c ? 'border-white scale-110 shadow-lg' : 'border-transparent opacity-50 hover:opacity-80'"></button>
                        </template>
                    </div>
                    <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium bg-nexo-600 text-white hover:bg-nexo-700 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </form>

                <!-- Category list -->
                <div class="space-y-1 max-h-64 overflow-y-auto">
                    <template x-if="categorias.length === 0">
                        <div class="text-center py-6">
                            <p class="text-xs dark:text-white/30 text-gray-400">Sin categorías creadas</p>
                        </div>
                    </template>
                    <template x-for="cat in categorias" :key="cat.id">
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg dark:hover:bg-white/[0.03] hover:bg-gray-50 transition-colors group/cat">
                            <span class="w-3 h-3 rounded-full shrink-0" :style="'background:'+cat.color"></span>
                            <span class="text-sm font-medium flex-1" x-text="cat.nombre"></span>
                            <span class="text-[10px] dark:text-white/25 text-gray-400 tabular-nums" x-text="cat.total + ' items'"></span>
                            <button @click="deleteCat(cat.id, cat.nombre, cat.total)" class="w-6 h-6 flex items-center justify-center rounded-md opacity-0 group-hover/cat:opacity-100 dark:hover:bg-white/10 hover:bg-gray-200 dark:text-white/30 text-gray-400 hover:text-red-500 transition-all" title="Eliminar categoría">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-5 pt-4 border-t dark:border-white/[0.06] border-gray-100">
                    <button @click="showCatManager = false" class="w-full px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script>
function bovedaApp() {
    return {
        items: [], categorias: [],
        showModal: false, showDel: false, showCatManager: false,
        editId: null, delId: null, delName: '',
        saving: false, showPass: false,
        search: '', filtroTipo: '', filtroCat: '',
        fileName: '', fileObj: null,
        toast: '', toastType: 'success', toastTimer: null,
        form: { tipo:'password', titulo:'', categoria_id:'', usuario_campo:'', password_raw:'', url:'', notas:'' },
        catForm: { nombre:'', color:'#7c3aed' },

        showToast(msg, type = 'success') {
            this.toast = msg;
            this.toastType = type;
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => this.toast = '', 2500);
        },

        async load() {
            const r = await fetch('api/boveda.php?action=list');
            const d = await r.json();
            if (d.ok) { this.items = d.items; this.categorias = d.categorias; }
        },

        filtered() {
            let list = this.items;
            if (this.filtroTipo) list = list.filter(i => i.tipo === this.filtroTipo);
            if (this.filtroCat) list = list.filter(i => String(i.categoria_id) === String(this.filtroCat));
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(i => (i.titulo||'').toLowerCase().includes(q) || (i.url||'').toLowerCase().includes(q) || (i.notas||'').toLowerCase().includes(q) || (i.usuario_campo||'').toLowerCase().includes(q));
            }
            return list;
        },

        tipoStyle(t) {
            const m = {
                password: { bg:'bg-amber-500/10', text:'text-amber-500', icon:'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>' },
                file: { bg:'bg-blue-500/10', text:'text-blue-500', icon:'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>' },
                link: { bg:'bg-emerald-500/10', text:'text-emerald-500', icon:'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>' },
                note: { bg:'bg-purple-500/10', text:'text-purple-500', icon:'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>' }
            };
            return m[t] || m.note;
        },
        tipoLabel(t) {
            const m = {password:'Contraseña',file:'Archivo',link:'Enlace',note:'Nota'};
            return m[t] || t;
        },

        formatSize(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
            if (bytes < 1073741824) return (bytes/1048576).toFixed(1) + ' MB';
            return (bytes/1073741824).toFixed(2) + ' GB';
        },

        timeAgo(d) {
            if (!d) return '';
            const diff = Math.floor((Date.now() - new Date(d).getTime())/1000);
            if (diff < 60) return 'Justo ahora';
            if (diff < 3600) return Math.floor(diff/60) + ' min';
            if (diff < 86400) return Math.floor(diff/3600) + 'h';
            const days = Math.floor(diff/86400);
            if (days === 1) return 'Ayer';
            if (days < 30) return 'Hace ' + days + ' días';
            return new Date(d).toLocaleDateString('es');
        },

        genPassword() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*_-+=';
            const arr = new Uint8Array(20);
            crypto.getRandomValues(arr);
            return Array.from(arr, b => chars[b % chars.length]).join('');
        },

        copyText(text) {
            navigator.clipboard.writeText(text);
            this.showToast('Copiado al portapapeles');
        },

        handleFile(e) {
            this.fileObj = e.target.files[0] || null;
            this.fileName = this.fileObj ? this.fileObj.name + ' (' + this.formatSize(this.fileObj.size) + ')' : '';
        },
        handleDrop(e) {
            this.fileObj = e.dataTransfer.files[0] || null;
            this.fileName = this.fileObj ? this.fileObj.name + ' (' + this.formatSize(this.fileObj.size) + ')' : '';
        },

        openNew() {
            this.editId = null;
            this.form = { tipo:'password', titulo:'', categoria_id:'', usuario_campo:'', password_raw:'', url:'', notas:'' };
            this.fileObj = null; this.fileName = ''; this.showPass = false;
            this.showModal = true;
        },

        openEdit(item) {
            this.editId = item.id;
            this.form = {
                tipo: item.tipo, titulo: item.titulo, categoria_id: item.categoria_id || '',
                usuario_campo: item.usuario_campo || '', password_raw: '',
                url: item.url || '', notas: item.notas || ''
            };
            this.fileObj = null; this.fileName = item.archivo_nombre ? item.archivo_nombre + ' (existente)' : '';
            this.showPass = false;
            this.showModal = true;
        },

        async saveItem() {
            if (!this.form.titulo.trim()) return;
            this.saving = true;
            const fd = new FormData();
            fd.append('action', this.editId ? 'update' : 'create');
            if (this.editId) fd.append('id', this.editId);
            Object.keys(this.form).forEach(k => fd.append(k, this.form[k]));
            if (this.fileObj) fd.append('archivo', this.fileObj);
            try {
                const r = await fetch('api/boveda.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok || d.id) {
                    this.showModal = false;
                    await this.load();
                    this.showToast(this.editId ? 'Item actualizado' : 'Item creado');
                } else {
                    this.showToast(d.error || 'Error al guardar', 'error');
                }
            } catch(e) { this.showToast('Error de conexión', 'error'); }
            this.saving = false;
        },

        confirmDel(id, name) { this.delId = id; this.delName = name; this.showDel = true; },

        async deleteItem() {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.delId);
            const r = await fetch('api/boveda.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) { this.showDel = false; await this.load(); this.showToast('Item eliminado'); }
        },

        async saveCat() {
            if (!this.catForm.nombre.trim()) return;
            const fd = new FormData();
            fd.append('action', 'create_cat');
            fd.append('nombre', this.catForm.nombre);
            fd.append('color', this.catForm.color);
            const r = await fetch('api/boveda.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) {
                this.catForm = {nombre:'',color:'#7c3aed'};
                await this.load();
                this.showToast('Categoría creada');
            }
        },

        async deleteCat(id, name, total) {
            if (!confirm('¿Eliminar la categoría "' + name + '"?' + (total > 0 ? ' Los ' + total + ' items asociados quedarán sin categoría.' : ''))) return;
            const fd = new FormData();
            fd.append('action', 'delete_cat');
            fd.append('id', id);
            const r = await fetch('api/boveda.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) { await this.load(); this.showToast('Categoría eliminada'); }
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
