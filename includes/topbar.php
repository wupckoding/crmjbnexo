<!-- Topbar -->
<header class="sticky top-0 z-20 h-16 flex items-center justify-between px-4 sm:px-6 dark:bg-dark-950/80 bg-white/80 backdrop-blur-xl border-b dark:border-white/[0.06] border-gray-200"
        x-data="topbarApp()" x-init="loadNotifs(); setInterval(()=>loadNotifs(), 30000)">
    
    <!-- Left: Mobile menu + Page title -->
    <div class="flex items-center gap-3">
        <button @click="sidebarMobile = !sidebarMobile" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl dark:hover:bg-white/5 hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold tracking-tight"><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <!-- Right: Actions -->
    <div class="flex items-center gap-2 sm:gap-3">

        <!-- Global Search Trigger -->
        <button @click="openSearch()" class="flex items-center gap-2 px-3 py-1.5 rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors text-sm dark:text-white/40 text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="hidden sm:inline">Buscar...</span>
            <kbd class="hidden sm:inline text-[10px] px-1.5 py-0.5 rounded dark:bg-white/10 bg-gray-200 font-mono">Ctrl K</kbd>
        </button>

        <!-- Dark/Light toggle -->
        <button onclick="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
            <svg class="w-4 h-4 dark:text-yellow-400 text-gray-500 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="w-4 h-4 text-gray-500 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        <!-- Volume control -->
        <div class="relative" x-data="{ volOpen: false, vol: NexoSounds.getVolume() * 100, muted: NexoSounds.isMuted() }">
            <button @click="volOpen = !volOpen" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                <svg x-show="!muted && vol > 50" class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                <svg x-show="!muted && vol > 0 && vol <= 50" class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                <svg x-show="muted || vol == 0" class="w-4 h-4 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
            </button>
            <div x-show="volOpen" x-transition @click.outside="volOpen = false" x-cloak
                 class="absolute right-0 top-12 w-48 dark:bg-dark-800 bg-white rounded-xl shadow-2xl border dark:border-white/10 border-gray-200 p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium dark:text-white/60 text-gray-500">Volumen</span>
                    <button @click="muted = NexoSounds.toggleMute()" class="text-[10px] px-1.5 py-0.5 rounded dark:bg-white/5 bg-gray-100" x-text="muted ? 'Activar' : 'Silenciar'"></button>
                </div>
                <input type="range" min="0" max="100" x-model="vol" 
                       @input="NexoSounds.setVolume(vol/100); if(muted) { muted = NexoSounds.toggleMute(); }"
                       @change="NexoSounds.notification()"
                       class="w-full h-1.5 rounded-full appearance-none cursor-pointer accent-nexo-500 dark:bg-white/10 bg-gray-200">
                <div class="text-center mt-1"><span class="text-[10px] dark:text-white/30 text-gray-400" x-text="vol + '%'"></span></div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="relative">
            <button @click="notifOpen = !notifOpen; if(notifOpen) NexoSounds.notification();" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors relative">
                <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread" class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 bg-nexo-600 rounded-full text-[9px] font-bold text-white flex items-center justify-center px-1"></span>
            </button>
            <div x-show="notifOpen" x-transition @click.outside="notifOpen = false" x-cloak class="absolute right-0 top-12 w-80 sm:w-96 dark:bg-dark-800 bg-white rounded-2xl shadow-2xl border dark:border-white/10 border-gray-200 overflow-hidden">
                <div class="p-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-sm">Notificaciones</h3>
                    <button x-show="unread > 0" @click="readAll()" class="text-[10px] text-nexo-400 hover:text-nexo-300">Marcar todas leídas</button>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <template x-if="notifs.length === 0">
                        <div class="p-6 text-center dark:text-white/30 text-gray-400 text-sm">Sin notificaciones</div>
                    </template>
                    <template x-for="n in notifs" :key="n.id">
                        <div @click="readNotif(n)" class="p-3 hover:dark:bg-white/5 hover:bg-gray-50 transition-colors cursor-pointer border-b dark:border-white/[0.04] border-gray-50 flex gap-3" :class="!parseInt(n.leida) ? 'dark:bg-nexo-900/10 bg-nexo-50/50' : ''">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm" :class="tipoStyle(n.tipo)">
                                <span x-text="tipoIcon(n.tipo)"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium" :class="!parseInt(n.leida) ? '' : 'dark:text-white/60 text-gray-500'" x-text="n.titulo"></p>
                                <p x-show="n.mensaje" class="text-xs dark:text-white/40 text-gray-400 truncate mt-0.5" x-text="n.mensaje"></p>
                                <p class="text-[10px] dark:text-white/25 text-gray-300 mt-1" x-text="timeAgo(n.creado_en)"></p>
                            </div>
                            <button @click.stop="deleteNotif(n.id)" class="shrink-0 w-6 h-6 flex items-center justify-center rounded dark:hover:bg-white/10 hover:bg-gray-200 opacity-0 group-hover:opacity-100 transition-opacity text-xs dark:text-white/30 text-gray-400">&times;</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- User avatar dropdown -->
        <div x-data="{ userOpen: false }" class="relative">
            <button @click="userOpen = !userOpen" class="flex items-center gap-2 pl-3 pr-1 py-1 rounded-xl dark:hover:bg-white/5 hover:bg-gray-100 transition-colors">
                <span class="text-sm font-medium hidden sm:block"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                <?php if (!empty($_SESSION['usuario_avatar']) && file_exists(__DIR__ . '/../uploads/avatars/' . $_SESSION['usuario_avatar'])): ?>
                <img src="uploads/avatars/<?php echo htmlspecialchars($_SESSION['usuario_avatar']); ?>" class="w-8 h-8 rounded-full object-cover">
                <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-nexo-500 to-nexo-700 flex items-center justify-center text-white text-xs font-bold">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)); ?>
                </div>
                <?php endif; ?>
            </button>
            <div x-show="userOpen" x-transition @click.outside="userOpen = false" x-cloak class="absolute right-0 top-12 w-56 dark:bg-dark-800 bg-white rounded-2xl shadow-2xl border dark:border-white/10 border-gray-200 overflow-hidden">
                <div class="p-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center gap-3">
                    <?php if (!empty($_SESSION['usuario_avatar']) && file_exists(__DIR__ . '/../uploads/avatars/' . $_SESSION['usuario_avatar'])): ?>
                    <img src="uploads/avatars/<?php echo htmlspecialchars($_SESSION['usuario_avatar']); ?>" class="w-10 h-10 rounded-full object-cover shrink-0">
                    <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-nexo-500 to-nexo-700 flex items-center justify-center text-white text-sm font-bold shrink-0">
                        <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
                        <p class="text-xs dark:text-white/40 text-gray-400 truncate"><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
                        <span class="inline-block mt-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-nexo-600/20 text-nexo-400 uppercase"><?php echo $_SESSION['usuario_rol']; ?></span>
                    </div>
                </div>
                <div class="p-2">
                    <a href="perfil.php" class="flex items-center gap-2 px-3 py-2 rounded-lg dark:hover:bg-white/5 hover:bg-gray-50 text-sm transition-colors">
                        <svg class="w-4 h-4 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Mi Perfil
                    </a>
                    <a href="auth/logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-red-500/10 text-sm text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== GLOBAL SEARCH MODAL (Ctrl+K) ========== -->
    <div x-show="searchOpen" x-transition x-cloak class="fixed inset-0 z-[70] flex items-start justify-center pt-[15vh] p-4">
        <div @click="searchOpen = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-xl dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden" @click.outside="searchOpen = false" @keydown.escape.window="searchOpen = false">
            <!-- Search input -->
            <div class="flex items-center gap-3 px-5 py-4 border-b dark:border-white/[0.06] border-gray-100">
                <svg class="w-5 h-5 dark:text-white/30 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input x-ref="searchInput" type="text" x-model="searchQuery" @input.debounce.300ms="doSearch()" placeholder="Buscar clientes, facturas, servicios..." class="w-full bg-transparent text-sm outline-none dark:placeholder-white/30 placeholder-gray-400">
                <kbd class="text-[10px] px-1.5 py-0.5 rounded dark:bg-white/10 bg-gray-200 font-mono dark:text-white/40 text-gray-500 shrink-0">ESC</kbd>
            </div>
            <!-- Results -->
            <div class="max-h-80 overflow-y-auto">
                <template x-if="searchLoading">
                    <div class="p-6 text-center"><div class="w-5 h-5 border-2 border-nexo-500 border-t-transparent rounded-full animate-spin mx-auto"></div></div>
                </template>
                <template x-if="!searchLoading && searchResults.length === 0 && searchQuery.length >= 2">
                    <div class="p-6 text-center text-sm dark:text-white/30 text-gray-400">No se encontraron resultados</div>
                </template>
                <template x-if="!searchLoading && searchQuery.length < 2">
                    <div class="p-4 space-y-2">
                        <p class="text-xs dark:text-white/30 text-gray-400 px-2">Accesos rápidos</p>
                        <a href="clientes.php" class="flex items-center gap-3 px-3 py-2 rounded-xl dark:hover:bg-white/5 hover:bg-gray-50 transition-colors text-sm"><span>👤</span> Clientes</a>
                        <a href="facturas.php" class="flex items-center gap-3 px-3 py-2 rounded-xl dark:hover:bg-white/5 hover:bg-gray-50 transition-colors text-sm"><span>📄</span> Facturas</a>
                        <a href="finanzas.php" class="flex items-center gap-3 px-3 py-2 rounded-xl dark:hover:bg-white/5 hover:bg-gray-50 transition-colors text-sm"><span>💰</span> Finanzas</a>
                        <a href="calendario.php" class="flex items-center gap-3 px-3 py-2 rounded-xl dark:hover:bg-white/5 hover:bg-gray-50 transition-colors text-sm"><span>📅</span> Calendario</a>
                        <a href="avisos.php" class="flex items-center gap-3 px-3 py-2 rounded-xl dark:hover:bg-white/5 hover:bg-gray-50 transition-colors text-sm"><span>📢</span> Avisos</a>
                    </div>
                </template>
                <template x-for="(r, i) in searchResults" :key="i">
                    <a :href="r.url" class="flex items-center gap-3 px-5 py-3 dark:hover:bg-white/5 hover:bg-gray-50 transition-colors border-b dark:border-white/[0.03] border-gray-50">
                        <span class="text-lg" x-text="r.icon"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium truncate" x-text="r.titulo"></p>
                            <p class="text-xs dark:text-white/40 text-gray-400 truncate" x-text="r.sub"></p>
                        </div>
                        <span class="text-[10px] dark:text-white/20 text-gray-300 uppercase shrink-0" x-text="r.tipo"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>
</header>

<script>
function topbarApp() {
    return {
        searchOpen: false, searchQuery: '', searchResults: [], searchLoading: false,
        notifOpen: false, notifs: [], unread: 0,

        init() {
            // Ctrl+K global shortcut
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.openSearch();
                }
            });
        },

        openSearch() {
            this.searchOpen = true;
            this.searchQuery = '';
            this.searchResults = [];
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        async doSearch() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            this.searchLoading = true;
            try {
                const r = await fetch('api/buscar.php?q=' + encodeURIComponent(this.searchQuery));
                const d = await r.json();
                this.searchResults = d.results || [];
            } catch(e) { this.searchResults = []; }
            this.searchLoading = false;
        },

        async loadNotifs() {
            try {
                const r = await fetch('api/notificaciones.php?action=list');
                const d = await r.json();
                if (d.ok) { this.notifs = d.notificaciones; this.unread = d.no_leidas; }
            } catch(e) {}
        },

        async readNotif(n) {
            if (!parseInt(n.leida)) {
                const fd = new FormData(); fd.append('action','read'); fd.append('id', n.id);
                await fetch('api/notificaciones.php', {method:'POST', body:fd});
                n.leida = '1';
                this.unread = Math.max(0, this.unread - 1);
            }
            if (n.enlace) window.location.href = n.enlace;
        },

        async readAll() {
            const fd = new FormData(); fd.append('action','read_all');
            await fetch('api/notificaciones.php', {method:'POST', body:fd});
            this.notifs.forEach(n => n.leida = '1');
            this.unread = 0;
        },

        async deleteNotif(id) {
            const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
            await fetch('api/notificaciones.php', {method:'POST', body:fd});
            this.notifs = this.notifs.filter(n => n.id != id);
            await this.loadNotifs();
        },

        tipoIcon(t) { return {info:'ℹ️',exito:'✅',aviso:'⚠️',error:'❌',cliente:'👤',factura:'📄',chat:'💬'}[t] || 'ℹ️'; },
        tipoStyle(t) { return {info:'bg-blue-500/10',exito:'bg-emerald-500/10',aviso:'bg-amber-500/10',error:'bg-red-500/10',cliente:'bg-nexo-500/10',factura:'bg-blue-500/10',chat:'bg-cyan-500/10'}[t] || 'bg-gray-500/10'; },

        timeAgo(d) {
            if (!d) return '';
            const diff = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
            if (diff < 60) return 'Justo ahora';
            if (diff < 3600) return Math.floor(diff/60) + ' min';
            if (diff < 86400) return Math.floor(diff/3600) + 'h';
            const days = Math.floor(diff/86400);
            if (days === 1) return 'Ayer';
            if (days < 30) return 'Hace ' + days + ' días';
            return new Date(d).toLocaleDateString('es');
        }
    };
}

function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    html.classList.toggle('dark');
    if (isDark) { NexoSounds.lightOn(); } else { NexoSounds.darkOn(); }
    fetch('api/toggle_theme.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({tema: isDark ? 'light' : 'dark'}) });
    document.querySelector('meta[name="theme-color"]').setAttribute('content', isDark ? '#f8fafc' : '#09090b');
}
</script>
