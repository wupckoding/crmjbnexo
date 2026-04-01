<?php
require_once 'includes/auth_check.php';
$pageTitle = 'Servicios';
$currentPage = 'servicios';

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="serviciosApp()" x-init="load()">

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-nexo-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold" x-text="servicios.length"></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400">Total Servicios</p>
                </div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold" x-text="servicios.filter(s => s.activo == 1).length"></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400">Activos</p>
                </div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold" x-text="totalFacturado"></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400">Veces Facturado</p>
                </div>
            </div>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold">$<span x-text="totalIngresos"></span></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400">Ingresos Generados</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex flex-wrap gap-2 items-center">
            <!-- Search -->
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Buscar servicio..." class="pl-10 pr-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 w-52">
            </div>
            <!-- Category filter -->
            <select x-model="filterCat" class="px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                <option value="">Todas categorías</option>
                <option value="desarrollo_web">Desarrollo Web</option>
                <option value="saas">SaaS</option>
                <option value="marketing">Marketing</option>
                <option value="diseño">Diseño</option>
                <option value="hosting">Hosting</option>
                <option value="mantenimiento">Mantenimiento</option>
            </select>
            <!-- Status filter -->
            <select x-model="filterStatus" class="px-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </div>
        <button @click="openCreate()" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white flex items-center gap-2 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Servicio
        </button>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="s in filtered" :key="s.id">
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden transition-all hover:shadow-lg hover:shadow-nexo-500/5 group"
                 :class="s.activo == 0 ? 'opacity-50' : ''">
                <!-- Color top bar by category -->
                <div class="h-1" :style="'background:' + catColor(s.categoria)"></div>
                <div class="p-5">
                    <!-- Header: icon + actions -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :style="'background:' + catColor(s.categoria) + '15'">
                            <span class="text-lg" x-text="catIcon(s.categoria)"></span>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEdit(s)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Editar">
                                <svg class="w-3.5 h-3.5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="toggleActive(s)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" :title="s.activo == 1 ? 'Desactivar' : 'Activar'">
                                <svg x-show="s.activo == 1" class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="s.activo != 1" class="w-3.5 h-3.5 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                            <button @click="confirmDelete(s)" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-500/10 transition-colors" title="Eliminar">
                                <svg class="w-3.5 h-3.5 dark:text-white/30 text-gray-400 hover:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Category badge -->
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full mb-2 inline-block" :style="'background:' + catColor(s.categoria) + '18; color:' + catColor(s.categoria)" x-text="catLabel(s.categoria)"></span>

                    <!-- Name & description -->
                    <h3 class="font-semibold mb-1 leading-tight" x-text="s.nombre"></h3>
                    <p class="text-xs dark:text-white/40 text-gray-400 mb-3 line-clamp-2 min-h-[2rem]" x-text="s.descripcion || 'Sin descripción'"></p>

                    <!-- Price -->
                    <div class="text-xl font-bold text-nexo-400 mb-3">
                        $<span x-text="Number(s.precio).toLocaleString('es')"></span>
                        <span class="text-xs font-normal dark:text-white/30 text-gray-400">USD</span>
                    </div>

                    <!-- Stats footer -->
                    <div class="flex gap-4 pt-3 border-t dark:border-white/[0.06] border-gray-100 text-center">
                        <div class="flex-1">
                            <p class="text-sm font-bold" x-text="s.veces_facturado"></p>
                            <p class="text-[10px] dark:text-white/30 text-gray-400">Facturas</p>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold" x-text="s.veces_cotizado"></p>
                            <p class="text-[10px] dark:text-white/30 text-gray-400">Cotizaciones</p>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-emerald-400">$<span x-text="Number(s.ingresos).toLocaleString('es')"></span></p>
                            <p class="text-[10px] dark:text-white/30 text-gray-400">Ingresos</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty state -->
        <template x-if="filtered.length === 0">
            <div class="col-span-full py-16 text-center">
                <svg class="w-12 h-12 mx-auto mb-3 dark:text-white/10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <p class="text-sm dark:text-white/30 text-gray-400">No se encontraron servicios</p>
            </div>
        </template>
    </div>

    <!-- Create/Edit Modal -->
    <template x-if="showModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl overflow-hidden">
                <div class="h-1 bg-nexo-500"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-bold" x-text="editing ? 'Editar Servicio' : 'Nuevo Servicio'"></h3>
                        <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="save()" class="space-y-4">
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Nombre *</label>
                            <input type="text" x-model="form.nombre" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Descripción</label>
                            <textarea x-model="form.descripcion" rows="3" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Precio (USD) *</label>
                                <input type="number" x-model="form.precio" step="0.01" min="0" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                            </div>
                            <div>
                                <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Categoría</label>
                                <select x-model="form.categoria" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
                                    <option value="desarrollo_web">Desarrollo Web</option>
                                    <option value="saas">SaaS</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="diseño">Diseño</option>
                                    <option value="hosting">Hosting</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                            <button type="submit" :disabled="saving" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white disabled:opacity-50">
                                <span x-show="!saving" x-text="editing ? 'Guardar Cambios' : 'Crear Servicio'"></span>
                                <span x-show="saving">Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- Delete Confirmation Modal -->
    <template x-if="showDeleteModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showDeleteModal = false">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
            <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.08] border-gray-200 shadow-2xl p-6 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-red-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-lg font-bold mb-1">Eliminar Servicio</h3>
                <p class="text-sm dark:text-white/50 text-gray-500 mb-5">¿Eliminar <strong x-text="deleteTarget?.nombre"></strong>? Esta acción no se puede deshacer.</p>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button @click="doDelete()" :disabled="deleting" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium bg-red-500 hover:bg-red-600 text-white transition-colors disabled:opacity-50">
                        <span x-show="!deleting">Eliminar</span>
                        <span x-show="deleting">Eliminando...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Toast -->
    <div x-show="toast" x-transition.opacity class="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl text-sm font-medium text-white shadow-lg"
         :class="toastType === 'ok' ? 'bg-emerald-500' : 'bg-red-500'" x-text="toast"
         x-init="$watch('toast', v => { if(v) setTimeout(()=> toast = '', 3000) })">
    </div>

</div>
</main>

<script>
function serviciosApp() {
    return {
        servicios: [],
        search: '',
        filterCat: '',
        filterStatus: '',
        showModal: false,
        showDeleteModal: false,
        editing: false,
        saving: false,
        deleting: false,
        deleteTarget: null,
        toast: '',
        toastType: 'ok',
        form: { id: null, nombre: '', descripcion: '', precio: '', categoria: 'desarrollo_web' },

        get filtered() {
            return this.servicios.filter(s => {
                if (this.search) {
                    const q = this.search.toLowerCase();
                    if (!s.nombre.toLowerCase().includes(q) && !(s.descripcion||'').toLowerCase().includes(q)) return false;
                }
                if (this.filterCat && s.categoria !== this.filterCat) return false;
                if (this.filterStatus !== '' && String(s.activo) !== this.filterStatus) return false;
                return true;
            });
        },

        get totalFacturado() {
            return this.servicios.reduce((sum, s) => sum + Number(s.veces_facturado || 0), 0);
        },

        get totalIngresos() {
            return this.servicios.reduce((sum, s) => sum + Number(s.ingresos || 0), 0).toLocaleString('es');
        },

        async load() {
            try {
                const r = await fetch('api/servicios.php?action=list');
                const d = await r.json();
                if (d.ok) this.servicios = d.servicios;
            } catch(e) { console.error(e); }
        },

        catColor(cat) {
            const m = {
                desarrollo_web: '#8b5cf6',
                saas: '#3b82f6',
                marketing: '#f59e0b',
                'diseño': '#ec4899',
                hosting: '#06b6d4',
                mantenimiento: '#22c55e'
            };
            return m[cat] || '#6b7280';
        },

        catIcon(cat) {
            const m = {
                desarrollo_web: '💻',
                saas: '☁️',
                marketing: '📈',
                'diseño': '🎨',
                hosting: '🖥️',
                mantenimiento: '🔧'
            };
            return m[cat] || '📦';
        },

        catLabel(cat) {
            const m = {
                desarrollo_web: 'Desarrollo Web',
                saas: 'SaaS',
                marketing: 'Marketing',
                'diseño': 'Diseño',
                hosting: 'Hosting',
                mantenimiento: 'Mantenimiento'
            };
            return m[cat] || cat;
        },

        openCreate() {
            this.editing = false;
            this.form = { id: null, nombre: '', descripcion: '', precio: '', categoria: 'desarrollo_web' };
            this.showModal = true;
        },

        openEdit(s) {
            this.editing = true;
            this.form = { id: s.id, nombre: s.nombre, descripcion: s.descripcion || '', precio: s.precio, categoria: s.categoria || 'desarrollo_web' };
            this.showModal = true;
        },

        async save() {
            if (!this.form.nombre) return;
            this.saving = true;
            const fd = new FormData();
            fd.append('action', this.editing ? 'update' : 'create');
            fd.append('nombre', this.form.nombre);
            fd.append('descripcion', this.form.descripcion);
            fd.append('precio', this.form.precio);
            fd.append('categoria', this.form.categoria);
            if (this.form.id) fd.append('id', this.form.id);
            try {
                const r = await fetch('api/servicios.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.showModal = false;
                    this.toast = this.editing ? 'Servicio actualizado' : 'Servicio creado';
                    this.toastType = 'ok';
                    await this.load();
                } else {
                    this.toast = d.error || 'Error al guardar';
                    this.toastType = 'err';
                }
            } catch(e) {
                this.toast = 'Error de conexión';
                this.toastType = 'err';
            }
            this.saving = false;
        },

        async toggleActive(s) {
            const fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('id', s.id);
            try {
                const r = await fetch('api/servicios.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    s.activo = d.activo;
                    this.toast = d.activo ? 'Servicio activado' : 'Servicio desactivado';
                    this.toastType = 'ok';
                }
            } catch(e) { console.error(e); }
        },

        confirmDelete(s) {
            this.deleteTarget = s;
            this.showDeleteModal = true;
        },

        async doDelete() {
            if (!this.deleteTarget) return;
            this.deleting = true;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.deleteTarget.id);
            try {
                const r = await fetch('api/servicios.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok) {
                    this.servicios = this.servicios.filter(s => s.id != this.deleteTarget.id);
                    this.showDeleteModal = false;
                    this.toast = 'Servicio eliminado';
                    this.toastType = 'ok';
                } else {
                    this.toast = d.error || 'Error al eliminar';
                    this.toastType = 'err';
                }
            } catch(e) {
                this.toast = 'Error de conexión';
                this.toastType = 'err';
            }
            this.deleting = false;
        }
    };
}
</script>

<?php include 'includes/footer.php'; ?>
