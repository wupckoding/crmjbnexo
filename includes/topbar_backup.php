<!-- Topbar -->
<header class="sticky top-0 z-20 h-16 flex items-center justify-between px-4 sm:px-6 dark:bg-dark-950/80 bg-white/80 backdrop-blur-xl border-b dark:border-white/[0.06] border-gray-200">
    
    <!-- Left: Mobile menu + Page title -->
    <div class="flex items-center gap-3">
        <button @click="sidebarMobile = !sidebarMobile" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl dark:hover:bg-white/5 hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold tracking-tight"><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <!-- Right: Actions -->
    <div class="flex items-center gap-2 sm:gap-3">
        
        <!-- Search -->
        <div x-data="{ searchOpen: false }" class="relative">
            <button @click="searchOpen = !searchOpen" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
            <div x-show="searchOpen" x-transition @click.outside="searchOpen = false" x-cloak class="absolute right-0 top-12 w-72 dark:bg-dark-800 bg-white rounded-2xl shadow-2xl border dark:border-white/10 border-gray-200 p-3">
                <input type="text" placeholder="Buscar clientes, facturas..." class="w-full bg-transparent dark:bg-white/5 bg-gray-50 rounded-xl px-3 py-2.5 text-sm outline-none dark:placeholder-white/30 placeholder-gray-400 border dark:border-white/10 border-gray-200 focus:border-nexo-500/50">
            </div>
        </div>

        <!-- Dark/Light toggle -->
        <button onclick="toggleTheme()" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">
            <!-- Sun (shown in dark mode) -->
            <svg class="w-4 h-4 dark:text-yellow-400 text-gray-500 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <!-- Moon (shown in light mode) -->
            <svg class="w-4 h-4 text-gray-500 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        <!-- Notifications -->
        <div x-data="{ notifOpen: false }" class="relative">
            <button @click="notifOpen = !notifOpen; if(notifOpen) NexoSounds.notification();" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors relative">
                <svg class="w-4 h-4 dark:text-white/60 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-nexo-600 rounded-full text-[9px] font-bold text-white flex items-center justify-center">3</span>
            </button>
            <div x-show="notifOpen" x-transition @click.outside="notifOpen = false" x-cloak class="absolute right-0 top-12 w-80 dark:bg-dark-800 bg-white rounded-2xl shadow-2xl border dark:border-white/10 border-gray-200 overflow-hidden">
                <div class="p-4 border-b dark:border-white/[0.06] border-gray-100">
                    <h3 class="font-semibold text-sm">Notificaciones</h3>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <div class="p-3 hover:dark:bg-white/5 hover:bg-gray-50 transition-colors cursor-pointer border-b dark:border-white/[0.04] border-gray-50">
                        <p class="text-sm dark:text-white/80 text-gray-700">Nueva cotización aceptada por <strong>María López</strong></p>
                        <p class="text-xs dark:text-white/30 text-gray-400 mt-1">Hace 2 horas</p>
                    </div>
                    <div class="p-3 hover:dark:bg-white/5 hover:bg-gray-50 transition-colors cursor-pointer border-b dark:border-white/[0.04] border-gray-50">
                        <p class="text-sm dark:text-white/80 text-gray-700">Pago recibido de <strong>PowerGym MX</strong> - $399</p>
                        <p class="text-xs dark:text-white/30 text-gray-400 mt-1">Hace 5 horas</p>
                    </div>
                    <div class="p-3 hover:dark:bg-white/5 hover:bg-gray-50 transition-colors cursor-pointer">
                        <p class="text-sm dark:text-white/80 text-gray-700">Factura <strong>INV-2026-003</strong> vence en 10 días</p>
                        <p class="text-xs dark:text-white/30 text-gray-400 mt-1">Hace 1 día</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User avatar dropdown -->
        <div x-data="{ userOpen: false }" class="relative">
            <button @click="userOpen = !userOpen" class="flex items-center gap-2 pl-3 pr-1 py-1 rounded-xl dark:hover:bg-white/5 hover:bg-gray-100 transition-colors">
                <span class="text-sm font-medium hidden sm:block"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-nexo-500 to-nexo-700 flex items-center justify-center text-white text-xs font-bold">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)); ?>
                </div>
            </button>
            <div x-show="userOpen" x-transition @click.outside="userOpen = false" x-cloak class="absolute right-0 top-12 w-56 dark:bg-dark-800 bg-white rounded-2xl shadow-2xl border dark:border-white/10 border-gray-200 overflow-hidden">
                <div class="p-4 border-b dark:border-white/[0.06] border-gray-100">
                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
                    <p class="text-xs dark:text-white/40 text-gray-400"><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
                    <span class="inline-block mt-1.5 text-[10px] font-medium px-2 py-0.5 rounded-full bg-nexo-600/20 text-nexo-400 uppercase"><?php echo $_SESSION['usuario_rol']; ?></span>
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
</header>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    html.classList.toggle('dark');
    if (isDark) { NexoSounds.lightOn(); } else { NexoSounds.darkOn(); }
    fetch('api/toggle_theme.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({tema: isDark ? 'light' : 'dark'}) });
    document.querySelector('meta[name="theme-color"]').setAttribute('content', isDark ? '#f8fafc' : '#09090b');
}
</script>
