<!-- Mobile overlay -->
<div x-show="sidebarMobile" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarMobile = false" class="fixed inset-0 bg-black/60 z-30 lg:hidden" x-cloak></div>

<!-- Sidebar -->
<aside :class="[sidebarOpen ? 'w-64' : 'w-20', sidebarMobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
       class="fixed lg:sticky top-0 left-0 h-screen z-40 sidebar-transition flex flex-col dark:bg-dark-900 bg-white border-r dark:border-white/[0.06] border-gray-200 overflow-hidden">
    
    <!-- Logo -->
    <div class="flex items-center h-16 px-4 border-b dark:border-white/[0.06] border-gray-200">
        <div class="flex items-center gap-2.5 min-w-0">
            <?php if (!empty($_empresaLogo) && file_exists(__DIR__ . '/../' . $_empresaLogo)): ?>
            <img src="<?php echo htmlspecialchars($_empresaLogo); ?>" alt="Logo" class="w-9 h-9 rounded-full object-contain shrink-0 border-2 border-nexo-500/60 bg-nexo-600/10 p-0.5">
            <?php else: ?>
            <div class="w-9 h-9 rounded-full border-2 border-nexo-500/60 flex items-center justify-center shrink-0 bg-nexo-600/10">
                <span class="text-[11px] font-bold text-nexo-400 tracking-tight"><?php echo mb_strtoupper(mb_substr($_empresaNombre, 0, 2)); ?></span>
            </div>
            <?php endif; ?>
            <span x-show="sidebarOpen" x-transition class="text-sm font-bold tracking-wider whitespace-nowrap dark:text-white text-gray-900"><?php echo htmlspecialchars($_empresaNombre); ?></span>
        </div>
        <button @click="sidebarOpen = !sidebarOpen" class="ml-auto hidden lg:flex items-center justify-center w-7 h-7 rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 transition-colors">
            <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-4 h-4 dark:text-white/40 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>

    <!-- Nav links -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <?php
        $menuItems = [
            ['id'=>'dashboard','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6','label'=>__('nav_inicio'),'href'=>'dashboard.php'],
            ['id'=>'clientes','icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z','label'=>__('nav_clientes'),'href'=>'clientes.php'],
            ['id'=>'facturas','icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','label'=>__('nav_facturas'),'href'=>'facturas.php'],
            ['id'=>'finanzas','icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','label'=>__('nav_finanzas'),'href'=>'finanzas.php'],
            ['id'=>'calendario','icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','label'=>__('nav_calendario'),'href'=>'calendario.php'],
            ['id'=>'chat','icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z','label'=>__('nav_chat'),'href'=>'chat.php'],
            ['id'=>'usuarios','icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z','label'=>__('nav_usuarios'),'href'=>'usuarios.php'],
            ['id'=>'servicios','icon'=>'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z','label'=>__('nav_servicios'),'href'=>'servicios.php'],
            ['id'=>'avisos','icon'=>'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z','label'=>__('nav_avisos'),'href'=>'avisos.php'],
            ['id'=>'pipeline','icon'=>'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2','label'=>__('nav_pipeline'),'href'=>'pipeline.php'],
            ['id'=>'leadscraper','icon'=>'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z','label'=>__('nav_leadscraper'),'href'=>'leadscraper.php'],
            ['id'=>'scripts','icon'=>'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z','label'=>__('nav_scripts'),'href'=>'scripts.php'],
        ];
        if (($_SESSION['usuario_rol'] ?? $_SESSION['user_role'] ?? '') === 'admin') {
            $menuItems[] = ['id'=>'boveda','icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','label'=>__('nav_boveda'),'href'=>'boveda.php'];
            $menuItems[] = ['id'=>'permisos','icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z','label'=>__('nav_permisos'),'href'=>'permisos.php'];
            $menuItems[] = ['id'=>'actividad','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01','label'=>__('nav_actividad'),'href'=>'actividad.php'];
            $menuItems[] = ['id'=>'backup','icon'=>'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10','label'=>__('nav_backup'),'href'=>'backup.php'];
            $menuItems[] = ['id'=>'ajustes','icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z','label'=>__('nav_ajustes'),'href'=>'ajustes.php'];
        }
        // Filter by permissions
        global $_permisos;
        $userRole = $_SESSION['usuario_rol'] ?? $_SESSION['user_role'] ?? '';
        foreach ($menuItems as $item):
            $modId = $item['id'];
            if ($userRole !== 'admin' && isset($_permisos[$modId]) && !$_permisos[$modId]['puede_ver']) continue;
            $isActive = $currentPage === $item['id'];
        ?>
        <a href="<?php echo $item['href']; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $isActive ? 'active' : 'dark:text-white/50 text-gray-500 dark:hover:text-white/80 hover:text-gray-700'; ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php echo $item['icon']; ?>"/></svg>
            <span x-show="sidebarOpen" x-transition class="whitespace-nowrap"><?php echo $item['label']; ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="p-3 border-t dark:border-white/[0.06] border-gray-200">
        <a href="auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium dark:text-white/40 text-gray-400 hover:text-red-400 dark:hover:text-red-400 transition-colors">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span x-show="sidebarOpen" x-transition class="whitespace-nowrap"><?php echo __('nav_cerrar_sesion'); ?></span>
        </a>
    </div>
</aside>
